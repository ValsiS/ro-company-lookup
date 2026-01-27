<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Drivers;

use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\DataCollection;
use Valsis\RoCompanyLookup\Contracts\RoCompanyLookupDriver;
use Valsis\RoCompanyLookup\Data\AddressData;
use Valsis\RoCompanyLookup\Data\AddressExpirationData;
use Valsis\RoCompanyLookup\Data\AddressSetData;
use Valsis\RoCompanyLookup\Data\CaenEntryData;
use Valsis\RoCompanyLookup\Data\CaenSetData;
use Valsis\RoCompanyLookup\Data\CompanyProfileData;
use Valsis\RoCompanyLookup\Data\CompanySimpleData;
use Valsis\RoCompanyLookup\Data\ContactData;
use Valsis\RoCompanyLookup\Data\FirmaData;
use Valsis\RoCompanyLookup\Data\InactiveStatusData;
use Valsis\RoCompanyLookup\Data\LegalData;
use Valsis\RoCompanyLookup\Data\LegalHistoryEntryData;
use Valsis\RoCompanyLookup\Data\MetaData;
use Valsis\RoCompanyLookup\Data\SplitVatData;
use Valsis\RoCompanyLookup\Data\VatCollectionData;
use Valsis\RoCompanyLookup\Data\VatStatusData;
use Valsis\RoCompanyLookup\Data\VatStatusEntryData;
use Valsis\RoCompanyLookup\Exceptions\LookupFailedException;
use Valsis\RoCompanyLookup\Support\DateHelper;

class AnafDriver implements RoCompanyLookupDriver
{
    public function lookup(int $cui, DateTimeInterface $date): DriverResponse
    {
        $responses = $this->batch([$cui], $date);

        return $responses[0] ?? new DriverResponse($this->emptyCompanyData($cui));
    }

    public function batch(array $cuis, DateTimeInterface $date): array
    {
        if (count($cuis) === 0) {
            return [];
        }

        $maxBatchSize = (int) config('ro-company-lookup.batch_max_size', 100);
        if (count($cuis) > $maxBatchSize) {
            throw new LookupFailedException(sprintf('ANAF batch limit exceeded. Maximum is %d CUIs.', $maxBatchSize));
        }

        /** @var list<array{cui: int, data: string}> $payload */
        $payload = array_values(array_map(fn (int $cui) => [
            'cui' => $cui,
            'data' => DateHelper::formatDate($date),
        ], $cuis));

        $response = $this->postWithRetries($payload);

        if ($response->status() === 429 || $response->serverError()) {
            throw new LookupFailedException('ANAF service error after retries.', $response->status());
        }

        if ($response->clientError()) {
            throw new LookupFailedException('ANAF request rejected: '.$response->status(), $response->status());
        }

        $raw = $response->json();
        $entries = $this->extractEntries($raw);

        $responses = [];
        foreach ($cuis as $cui) {
            $entry = $this->findEntryForCui($entries, $cui);
            $data = $entry ? $this->mapEntry($entry, $cui) : $this->emptyCompanyData($cui);
            $responses[] = new DriverResponse($data, $raw);
        }

        return $responses;
    }

    protected function request(): PendingRequest
    {
        $config = config('ro-company-lookup.anaf');

        $retries = (int) ($config['retries'] ?? 3);
        $backoff = (int) ($config['backoff_ms'] ?? 250);

        return Http::baseUrl((string) ($config['base_url'] ?? ''))
            ->withUserAgent((string) ($config['user_agent'] ?? 'valsis/ro-company-lookup'))
            ->timeout((int) ($config['timeout'] ?? 10))
            ->connectTimeout((int) ($config['connect_timeout'] ?? 5))
            ->retry($retries, $backoff, function ($exception) {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                if (! method_exists($exception, 'getResponse')) {
                    return false;
                }

                $response = $exception->getResponse();
                if (! $response) {
                    return false;
                }

                return $response->status() === 429 || $response->serverError();
            }, false)
            ->asJson()
            ->acceptJson();
    }

    protected function endpoint(): string
    {
        return (string) config('ro-company-lookup.anaf.endpoint');
    }

    /**
     * @param  array<int, array{cui: int, data: string}>  $payload
     */
    protected function postWithRetries(array $payload): \Illuminate\Http\Client\Response
    {
        $config = config('ro-company-lookup.anaf');
        $retries = (int) ($config['retries'] ?? 3);
        $backoff = (int) ($config['backoff_ms'] ?? 250);

        $attempt = 0;
        $delay = $backoff;

        do {
            $attempt++;

            try {
                /** @var array<int<0, max>|string, mixed> $payloadForRequest */
                $payloadForRequest = $payload;
                $response = $this->request()->post($this->endpoint(), $payloadForRequest);
            } catch (ConnectionException $exception) {
                if ($attempt > $retries) {
                    throw new LookupFailedException('ANAF connection failed after retries.', previous: $exception);
                }

                usleep($delay * 1000);
                $delay *= 2;

                continue;
            }

            if ($response->status() === 429 || $response->serverError()) {
                if ($attempt > $retries) {
                    return $response;
                }

                usleep($delay * 1000);
                $delay *= 2;

                continue;
            }

            return $response;
        } while (true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractEntries(mixed $raw): array
    {
        if (is_array($raw) && array_is_list($raw)) {
            return $raw;
        }

        if (is_array($raw)) {
            if (isset($raw['found']) && is_array($raw['found'])) {
                return $raw['found'];
            }
        }

        return [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<string, mixed>|null
     */
    protected function findEntryForCui(array $entries, int $cui): ?array
    {
        foreach ($entries as $entry) {
            $general = is_array($entry['date_generale'] ?? null) ? $entry['date_generale'] : null;
            $entryCui = (int) (
                $entry['cui']
                ?? $general['cui']
                ?? 0
            );
            if ($entryCui === $cui) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function mapEntry(array $entry, int $cui): CompanySimpleData
    {
        $general = is_array($entry['date_generale'] ?? null) ? $entry['date_generale'] : null;
        $name = $this->firstValue($general ?? [], ['denumire']);
        $nrRegCom = $this->firstValue($general ?? [], ['nrRegCom']);
        $caen = $this->firstValue($general ?? [], ['cod_CAEN']);

        $profile = $this->mapProfile($general);

        $company = new FirmaData(
            cui: $cui,
            registration_number: $nrRegCom,
            name_mfinante: $name,
            name_recom: $name,
            profile: $profile
        );

        $caenEntry = $caen ? new CaenEntryData(code: $caen, label: null, version: null) : null;
        $caenSet = new CaenSetData(
            principal_mfinante: $caenEntry,
            principal_recom: $caenEntry
        );

        $contact = new ContactData(
            phones: array_filter([
                $this->firstValue($general ?? [], ['telefon']),
                $this->firstValue($general ?? [], ['fax']),
            ], static fn ($value) => $value !== null && $value !== ''),
            emails: array_filter([
                $this->firstValue($general ?? [], ['email']),
            ], static fn ($value) => $value !== null && $value !== '')
        );

        $vatData = Arr::get($entry, 'inregistrare_scop_Tva');
        $queriedAt = DateHelper::parseDate($this->firstValue($general ?? [], ['data']));
        $this->auditEntry($entry, $cui, $queriedAt);
        $vatCollection = $this->mapVatCollection(Arr::get($entry, 'inregistrare_RTVAI'));
        $inactiveStatus = $this->mapInactiveStatus(Arr::get($entry, 'stare_inactiv'));
        $splitVat = $this->mapSplitVat(Arr::get($entry, 'inregistrare_SplitTVA'));
        $vat = $this->mapVat($vatData, $queriedAt);

        $domiciliuRaw = Arr::get($entry, 'adresa_domiciliu_fiscal')
            ?? Arr::get($general ?? [], 'adresa');
        $sediuRaw = Arr::get($entry, 'adresa_sediu_social');

        $addresses = new AddressSetData(
            anaf: $this->mapAddress($domiciliuRaw),
            registered_office: $this->mapAddress($sediuRaw)
        );

        $legal = $this->mapLegal($general ?? []);

        return new CompanySimpleData(
            address: $addresses,
            caen: $caenSet,
            contact: $contact,
            company: $company,
            vat_collection: $vatCollection,
            inactive_status: $inactiveStatus,
            split_vat: $splitVat,
            legal: $legal,
            vat: $vat,
            meta: MetaData::blank()
        );
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function auditEntry(array $entry, int $cui, ?\DateTimeImmutable $queriedAt): void
    {
        if (! config('ro-company-lookup.schema_audit.enabled', false)) {
            return;
        }

        $schema = $this->schemaDefinition();
        $unknown = $this->collectUnknownKeys($entry, $schema);
        if (count($unknown) === 0) {
            return;
        }

        $channel = config('ro-company-lookup.schema_audit.channel');
        $level = (string) config('ro-company-lookup.schema_audit.level', 'warning');
        $logger = $channel ? Log::channel($channel) : Log::getFacadeRoot();

        if ($logger !== null) {
            $logger->log($level, 'ANAF payload contains unknown keys.', [
                'cui' => $cui,
                'queried_at' => $queriedAt?->format(DATE_ATOM),
                'unknown' => $unknown,
            ]);
        }

        $snapshotPath = config('ro-company-lookup.schema_audit.snapshot_path');
        if (is_string($snapshotPath) && $snapshotPath !== '') {
            $payload = [
                'cui' => $cui,
                'queried_at' => $queriedAt?->format(DATE_ATOM),
                'unknown' => $unknown,
                'entry' => $entry,
            ];

            File::ensureDirectoryExists($snapshotPath);

            $filename = sprintf(
                '%s/anaf-schema-%s-%s.json',
                rtrim($snapshotPath, '/'),
                $cui,
                DateHelper::now()->format('YmdHis')
            );

            $snapshot = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            File::put($filename, $snapshot ?: '');
        }

        if (config('ro-company-lookup.schema_audit.fail_on_unknown', false)) {
            throw new LookupFailedException('ANAF payload contains unknown keys.');
        }
    }

    /**
     * @param  array<string, mixed>|null  $general
     */
    protected function mapProfile(?array $general): ?CompanyProfileData
    {
        if (! is_array($general)) {
            return null;
        }

        $registrationDate = DateHelper::parseDate($this->firstValue($general, ['data_inregistrare']));
        $registrationStatus = $this->firstValue($general, ['stare_inregistrare']);
        $fiscalOffice = $this->firstValue($general, ['organFiscalCompetent']);
        $ownershipForm = $this->firstValue($general, ['forma_de_proprietate']);
        $eInvoiceStatus = $this->normalizeBool($general['statusRO_e_Factura'] ?? null);
        $eInvoiceRegistrationDate = DateHelper::parseDate($this->firstValue($general, ['data_inreg_Reg_RO_e_Factura']));
        $iban = $this->firstValue($general, ['iban']);

        if (
            $registrationDate === null
            && $registrationStatus === null
            && $fiscalOffice === null
            && $ownershipForm === null
            && $eInvoiceStatus === null
            && $eInvoiceRegistrationDate === null
            && $iban === null
        ) {
            return null;
        }

        return new CompanyProfileData(
            registration_date: $registrationDate,
            registration_status: $registrationStatus,
            fiscal_office: $fiscalOffice,
            ownership_form: $ownershipForm,
            e_invoice_status: $eInvoiceStatus,
            e_invoice_registration_date: $eInvoiceRegistrationDate,
            iban: $iban
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function schemaDefinition(): array
    {
        return $this->normalizeSchema([
            'cui',
            'date_generale' => [
                'data',
                'cui',
                'denumire',
                'adresa',
                'telefon',
                'fax',
                'codPostal',
                'act',
                'stare_inregistrare',
                'data_inreg_Reg_RO_e_Factura',
                'organFiscalCompetent',
                'forma_de_proprietate',
                'forma_organizare',
                'forma_juridica',
                'statusRO_e_Factura',
                'data_inregistrare',
                'nrRegCom',
                'cod_CAEN',
                'iban',
            ],
            'inregistrare_scop_Tva' => [
                'scpTVA',
                'perioade_TVA' => [
                    'data_inceput_ScpTVA',
                    'data_sfarsit_ScpTVA',
                    'data_anul_imp_ScpTVA',
                    'mesaj_ScpTVA',
                ],
            ],
            'inregistrare_RTVAI' => [
                'dataActualizareTvaInc',
                'dataPublicareTvaInc',
                'dataInceputTvaInc',
                'dataSfarsitTvaInc',
                'tipActTvaInc',
                'statusTvaIncasare',
            ],
            'stare_inactiv' => [
                'dataInactivare',
                'dataReactivare',
                'dataPublicare',
                'dataRadiere',
                'statusInactivi',
            ],
            'inregistrare_SplitTVA' => [
                'statusSplitTVA',
                'dataInceputSplitTVA',
                'dataAnulareSplitTVA',
            ],
            'adresa_sediu_social' => [
                'stara',
                'sdenumire_Localitate',
                'sdenumire_Strada',
                'snumar_Strada',
                'scod_Localitate',
                'sdenumire_Judet',
                'scod_Judet',
                'scod_JudetAuto',
                'sdetalii_Adresa',
                'scod_Postal',
            ],
            'adresa_domiciliu_fiscal' => [
                'dtara',
                'ddenumire_Localitate',
                'ddenumire_Strada',
                'dnumar_Strada',
                'dcod_Localitate',
                'ddenumire_Judet',
                'dcod_Judet',
                'dcod_JudetAuto',
                'ddetalii_Adresa',
                'dcod_Postal',
            ],
        ]);
    }

    /**
     * @param  array<int|string, mixed>  $schema
     * @return array<string, mixed>
     */
    protected function normalizeSchema(array $schema): array
    {
        $normalized = [];
        foreach ($schema as $key => $value) {
            if (is_int($key)) {
                $normalized[(string) $value] = true;

                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeSchema($value);

                continue;
            }

            $normalized[$key] = true;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $schema
     * @param  array<int, array<string, mixed>>  $unknown
     * @return array<int, array<string, mixed>>
     */
    protected function collectUnknownKeys(array $data, array $schema, string $path = '', array $unknown = []): array
    {
        foreach ($data as $key => $value) {
            $key = (string) $key;
            $nextPath = $path === '' ? $key : $path.'.'.$key;

            if (! array_key_exists($key, $schema)) {
                $unknown[] = [
                    'path' => $nextPath,
                    'type' => gettype($value),
                ];

                continue;
            }

            if (is_array($schema[$key]) && is_array($value)) {
                if (array_is_list($value)) {
                    foreach ($value as $index => $item) {
                        if (! is_array($item)) {
                            continue;
                        }

                        $unknown = $this->collectUnknownKeys(
                            $item,
                            $schema[$key],
                            $nextPath.'['.$index.']',
                            $unknown
                        );
                    }
                } else {
                    $unknown = $this->collectUnknownKeys($value, $schema[$key], $nextPath, $unknown);
                }
            }
        }

        return $unknown;
    }

    protected function mapVat(mixed $vatData, ?\DateTimeImmutable $queriedAt = null): VatStatusData
    {
        $current = null;
        $historyEntries = [];

        $isVatPayer = null;

        if (is_array($vatData)) {
            if (isset($vatData['perioade_TVA']) && is_array($vatData['perioade_TVA'])) {
                $periods = $vatData['perioade_TVA'];
                foreach ($periods as $period) {
                    if (! is_array($period)) {
                        continue;
                    }

                    $entryStart = DateHelper::parseDate($this->firstValue($period, ['data_inceput_ScpTVA']));
                    $entryEnd = DateHelper::parseDate($this->firstValue($period, ['data_sfarsit_ScpTVA']));
                    $entryVat = true;

                    $entryData = $this->vatEntryFromStatus($entryVat, $entryStart, $entryEnd, $queriedAt);
                    if ($entryData !== null) {
                        $historyEntries[] = $entryData;
                    }
                }
            }

            $isVatPayer = $this->normalizeBool($this->firstValue($vatData, ['scpTVA']));
            $current = $this->vatEntryFromStatus($isVatPayer, null, null, $queriedAt);
        }

        if (count($historyEntries) > 0 && $isVatPayer !== false) {
            $current = $this->selectCurrentVatEntry($historyEntries) ?? $current;
        }

        return new VatStatusData(
            current: $current,
            history: new DataCollection(VatStatusEntryData::class, $historyEntries)
        );
    }

    /**
     * @param  list<VatStatusEntryData>  $entries
     */
    protected function selectCurrentVatEntry(array $entries): ?VatStatusEntryData
    {
        $openEnded = array_values(array_filter($entries, static fn (VatStatusEntryData $entry) => $entry->vat_cancel_date === null));
        if (count($openEnded) > 0) {
            return $this->selectLatestVatEntry($openEnded);
        }

        return $this->selectLatestVatEntry($entries);
    }

    /**
     * @param  list<VatStatusEntryData>  $entries
     */
    protected function selectLatestVatEntry(array $entries): ?VatStatusEntryData
    {
        $latest = null;
        foreach ($entries as $entry) {
            if ($latest === null) {
                $latest = $entry;

                continue;
            }

            $latestStart = $latest->vat_start_date;
            $entryStart = $entry->vat_start_date;

            if ($latestStart === null) {
                $latest = $entry;

                continue;
            }

            if ($entryStart !== null && $entryStart > $latestStart) {
                $latest = $entry;
            }
        }

        return $latest;
    }

    protected function vatEntryFromStatus(?bool $isVatPayer, ?\DateTimeImmutable $startDate, ?\DateTimeImmutable $endDate, ?\DateTimeImmutable $queriedAt): ?VatStatusEntryData
    {
        if ($isVatPayer === null && $startDate === null && $endDate === null) {
            return null;
        }

        $code = null;
        $label = null;

        if ($isVatPayer !== null) {
            $code = $isVatPayer ? 2 : 1;
            $label = $isVatPayer ? 'Plătitor TVA' : 'Neplătitor TVA';
        }

        return new VatStatusEntryData(
            code: $code,
            label: $label,
            vat_start_date: $startDate,
            vat_cancel_date: $endDate,
            vat_cancel_operation_date: null,
            queried_at: $queriedAt
        );
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function mapLegal(array $entry): LegalData
    {
        $forma = $this->firstValue($entry, ['forma_juridica']);
        $organizare = $this->firstValue($entry, ['forma_organizare']);
        $current = null;
        if ($forma !== null || $organizare !== null) {
            $current = new LegalHistoryEntryData(
                updated_at: null,
                name: $forma,
                organization: $organizare
            );
        }

        return new LegalData(
            current: $current,
            history: new DataCollection(LegalHistoryEntryData::class, [])
        );
    }

    protected function mapAddress(mixed $address): ?AddressData
    {
        if (! is_array($address)) {
            if (is_string($address)) {
                return new AddressData(
                    formatted: $address,
                    raw: $address,
                    raw_mf: null,
                    raw_recom: null,
                    country: null,
                    county: null,
                    city: null,
                    sub_locality: null,
                    sector: null,
                    street: null,
                    street_type: null,
                    number: null,
                    building: null,
                    entrance: null,
                    floor: null,
                    apartment: null,
                    postal_code: null,
                    siruta_code: null,
                    source: null,
                    expiration: null
                );
            }

            return null;
        }

        $formatted = $this->firstValue($address, ['adresa']);
        $raw = null;
        $rawMf = null;
        $rawRecom = null;
        $expiration = $this->mapExpiration($address['expirare'] ?? null);

        $country = $this->firstValue($address, ['dtara', 'stara']);
        $county = $this->firstValue($address, ['ddenumire_Judet', 'sdenumire_Judet']);
        $city = $this->firstValue($address, ['ddenumire_Localitate', 'sdenumire_Localitate']);
        $subLocality = $this->firstValue($address, ['ddetalii_Adresa', 'sdetalii_Adresa']);
        $sector = null;
        $street = $this->firstValue($address, ['ddenumire_Strada', 'sdenumire_Strada']);
        $streetType = null;
        $number = $this->firstValue($address, ['dnumar_Strada', 'snumar_Strada']);
        $building = null;
        $entrance = null;
        $floor = null;
        $apartment = null;
        $postalCode = $this->firstValue($address, ['dcod_Postal', 'scod_Postal']);
        $sirutaCode = $this->firstValue($address, ['dcod_Localitate', 'scod_Localitate']);
        $source = null;

        if ($formatted === null) {
            $formatted = $this->formatAddressFromParts(
                country: $country,
                county: $county,
                city: $city,
                subLocality: $subLocality,
                sector: $sector,
                street: $street,
                streetType: $streetType,
                number: $number,
                building: $building,
                entrance: $entrance,
                floor: $floor,
                apartment: $apartment,
                postalCode: $postalCode
            );
        }

        return new AddressData(
            formatted: $formatted,
            raw: $raw,
            raw_mf: $rawMf,
            raw_recom: $rawRecom,
            country: $country,
            county: $county,
            city: $city,
            sub_locality: $subLocality,
            sector: $sector,
            street: $street,
            street_type: $streetType,
            number: $number,
            building: $building,
            entrance: $entrance,
            floor: $floor,
            apartment: $apartment,
            postal_code: $postalCode,
            siruta_code: $sirutaCode,
            source: $source,
            expiration: $expiration
        );
    }

    protected function formatAddressFromParts(
        ?string $country,
        ?string $county,
        ?string $city,
        ?string $subLocality,
        ?string $sector,
        ?string $street,
        ?string $streetType,
        ?string $number,
        ?string $building,
        ?string $entrance,
        ?string $floor,
        ?string $apartment,
        ?string $postalCode
    ): ?string {
        $parts = [];

        if ($street !== null) {
            $streetLabel = $streetType ? trim($streetType.' '.$street) : $street;
            $parts[] = $streetLabel;
        }

        if ($number !== null) {
            $parts[] = 'Nr. '.$number;
        }

        if ($building !== null) {
            $parts[] = 'Bloc '.$building;
        }

        if ($entrance !== null) {
            $parts[] = 'Sc. '.$entrance;
        }

        if ($floor !== null) {
            $parts[] = 'Et. '.$floor;
        }

        if ($apartment !== null) {
            $parts[] = 'Ap. '.$apartment;
        }

        if ($sector !== null) {
            $parts[] = 'Sector '.$sector;
        }

        if ($city !== null) {
            $parts[] = $city;
        }

        if ($subLocality !== null) {
            $parts[] = $subLocality;
        }

        if ($county !== null) {
            $parts[] = 'Judet '.$county;
        }

        if ($postalCode !== null) {
            $parts[] = $postalCode;
        }

        if ($country !== null) {
            $parts[] = $country;
        }

        $parts = array_values(array_filter($parts, static fn (?string $value) => $value !== null && $value !== ''));

        return count($parts) > 0 ? implode(', ', $parts) : null;
    }

    protected function mapExpiration(mixed $expiration): ?AddressExpirationData
    {
        if (! is_array($expiration)) {
            return null;
        }

        return new AddressExpirationData(
            updated_at: DateHelper::parseDate($this->firstValue($expiration, ['data_actualizare', 'updated_at'])),
            expires_at: DateHelper::parseDate($this->firstValue($expiration, ['data_expirare', 'expires_at'])),
            is_expired: $this->normalizeBool($this->firstValue($expiration, ['este_expirat', 'is_expired']))
        );
    }

    protected function mapVatCollection(mixed $data): ?VatCollectionData
    {
        if (! is_array($data)) {
            return null;
        }

        $enabled = $this->normalizeBool($data['statusTvaIncasare'] ?? null);
        $startDate = DateHelper::parseDate($this->firstValue($data, ['dataInceputTvaInc']));
        $endDate = DateHelper::parseDate($this->firstValue($data, ['dataSfarsitTvaInc']));
        $publishedAt = DateHelper::parseDate($this->firstValue($data, ['dataPublicareTvaInc']));
        $updatedAt = DateHelper::parseDate($this->firstValue($data, ['dataActualizareTvaInc']));
        $actType = $this->firstValue($data, ['tipActTvaInc']);

        if ($enabled === null && $startDate === null && $endDate === null && $publishedAt === null && $updatedAt === null && $actType === null) {
            return null;
        }

        return new VatCollectionData(
            enabled: $enabled,
            start_date: $startDate,
            end_date: $endDate,
            published_at: $publishedAt,
            updated_at: $updatedAt,
            act_type: $actType
        );
    }

    protected function mapInactiveStatus(mixed $data): ?InactiveStatusData
    {
        if (! is_array($data)) {
            return null;
        }

        $status = $this->normalizeBool($data['statusInactivi'] ?? null);
        $inactivatedAt = DateHelper::parseDate($this->firstValue($data, ['dataInactivare']));
        $reactivatedAt = DateHelper::parseDate($this->firstValue($data, ['dataReactivare']));
        $publishedAt = DateHelper::parseDate($this->firstValue($data, ['dataPublicare']));
        $removedAt = DateHelper::parseDate($this->firstValue($data, ['dataRadiere']));

        if ($status === null && $inactivatedAt === null && $reactivatedAt === null && $publishedAt === null && $removedAt === null) {
            return null;
        }

        return new InactiveStatusData(
            is_inactive: $status,
            inactivated_at: $inactivatedAt,
            reactivated_at: $reactivatedAt,
            published_at: $publishedAt,
            removed_at: $removedAt
        );
    }

    protected function mapSplitVat(mixed $data): ?SplitVatData
    {
        if (! is_array($data)) {
            return null;
        }

        $enabled = $this->normalizeBool($data['statusSplitTVA'] ?? null);
        $startDate = DateHelper::parseDate($this->firstValue($data, ['dataInceputSplitTVA']));
        $cancelledAt = DateHelper::parseDate($this->firstValue($data, ['dataAnulareSplitTVA']));

        if ($enabled === null && $startDate === null && $cancelledAt === null) {
            return null;
        }

        return new SplitVatData(
            enabled: $enabled,
            start_date: $startDate,
            cancelled_at: $cancelledAt
        );
    }

    protected function emptyCompanyData(int $cui): CompanySimpleData
    {
        $emptyCaen = new CaenSetData(null, null);
        $emptyContact = new ContactData([], []);
        $emptyCompany = new FirmaData($cui, null, null, null);
        $emptyLegal = new LegalData(
            current: null,
            history: new DataCollection(LegalHistoryEntryData::class, [])
        );
        $emptyVat = new VatStatusData(
            current: null,
            history: new DataCollection(VatStatusEntryData::class, [])
        );
        $emptyAddress = new AddressSetData(null, null);

        return new CompanySimpleData(
            address: $emptyAddress,
            caen: $emptyCaen,
            contact: $emptyContact,
            company: $emptyCompany,
            vat_collection: null,
            inactive_status: null,
            split_vat: null,
            legal: $emptyLegal,
            vat: $emptyVat,
            meta: MetaData::blank()
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    protected function firstValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);
            if ($value === null || $value === '') {
                continue;
            }

            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
    }

    protected function normalizeBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower($value);
            if (in_array($normalized, ['da', 'true', '1', 'yes'], true)) {
                return true;
            }
            if (in_array($normalized, ['nu', 'false', '0', 'no'], true)) {
                return false;
            }
        }

        return null;
    }
}
