<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Drivers;

use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\DataCollection;
use Valsis\RoCompanyLookup\Contracts\RoCompanyLookupDriver;
use Valsis\RoCompanyLookup\Data\AddressData;
use Valsis\RoCompanyLookup\Data\AddressExpirationData;
use Valsis\RoCompanyLookup\Data\AddressSetData;
use Valsis\RoCompanyLookup\Data\CaenEntryData;
use Valsis\RoCompanyLookup\Data\CaenSetData;
use Valsis\RoCompanyLookup\Data\CompanySimpleData;
use Valsis\RoCompanyLookup\Data\ContactData;
use Valsis\RoCompanyLookup\Data\FirmaData;
use Valsis\RoCompanyLookup\Data\LegalData;
use Valsis\RoCompanyLookup\Data\LegalHistoryEntryData;
use Valsis\RoCompanyLookup\Data\MetaData;
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

        $payload = array_map(fn (int $cui) => [
            'cui' => $cui,
            'data' => DateHelper::formatDate($date),
        ], $cuis);

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
                $response = $this->request()->post($this->endpoint(), $payload);
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

            if (isset($raw['data']) && is_array($raw['data'])) {
                return $raw['data'];
            }

            if (isset($raw['date']) && is_array($raw['date'])) {
                return $raw['date'];
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
            $entryCui = (int) ($entry['cui'] ?? $entry['cod'] ?? $entry['codFiscal'] ?? $entry['cod_fiscal'] ?? 0);
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
        $name = $this->firstValue($entry, ['denumire', 'nume', 'name']);
        $nrRegCom = $this->firstValue($entry, ['nrRegCom', 'nr_reg_com', 'nr_reg_comert']);
        $caen = $this->firstValue($entry, ['caen', 'cod_caen']);

        $company = new FirmaData(
            cui: $cui,
            registration_number: $nrRegCom,
            name_mfinante: $name,
            name_recom: $name
        );

        $caenEntry = $caen ? new CaenEntryData(code: $caen, label: null, version: null) : null;
        $caenSet = new CaenSetData(
            principal_mfinante: $caenEntry,
            principal_recom: $caenEntry
        );

        $contact = new ContactData(
            phones: array_filter([
                $this->firstValue($entry, ['telefon', 'phone', 'tel']),
            ], static fn ($value) => $value !== null && $value !== ''),
            emails: array_filter([
                $this->firstValue($entry, ['email', 'emailAddress']),
            ], static fn ($value) => $value !== null && $value !== '')
        );

        $vatData = Arr::get($entry, 'tva') ?? Arr::get($entry, 'scpTVA') ?? Arr::get($entry, 'vat');
        $vat = $this->mapVat($vatData);

        $domiciliuRaw = Arr::get($entry, 'domiciliu_fiscal') ?? Arr::get($entry, 'domiciliuFiscal') ?? Arr::get($entry, 'adresa');
        $sediuRaw = Arr::get($entry, 'sediu_social') ?? Arr::get($entry, 'sediuSocial');

        $addresses = new AddressSetData(
            anaf: $this->mapAddress($domiciliuRaw),
            registered_office: $this->mapAddress($sediuRaw)
        );

        $legal = $this->mapLegal($entry);

        return new CompanySimpleData(
            address: $addresses,
            caen: $caenSet,
            contact: $contact,
            company: $company,
            legal: $legal,
            vat: $vat,
            meta: MetaData::blank()
        );
    }

    protected function mapVat(mixed $vatData): VatStatusData
    {
        $current = null;
        $historyEntries = [];

        if (is_array($vatData)) {
            $isVatPayer = $this->normalizeBool($this->firstValue($vatData, ['scpTVA', 'is_vat_payer', 'tva']));
            $startDate = DateHelper::parseDate($this->firstValue($vatData, ['data_inceput', 'start_date', 'data_inceput_tva']));
            $endDate = DateHelper::parseDate($this->firstValue($vatData, ['data_sfarsit', 'end_date', 'data_anulare_tva']));

            $current = $this->vatEntryFromStatus($isVatPayer, $startDate, $endDate);

            $history = $vatData['history'] ?? $vatData['istoric'] ?? [];
            if (is_array($history)) {
                foreach ($history as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }

                    $entryVat = $this->normalizeBool($this->firstValue($entry, ['scpTVA', 'is_vat_payer']));
                    $entryStart = DateHelper::parseDate($this->firstValue($entry, ['data_inceput', 'start_date', 'data_inceput_tva']));
                    $entryEnd = DateHelper::parseDate($this->firstValue($entry, ['data_sfarsit', 'end_date', 'data_anulare_tva']));

                    $entryData = $this->vatEntryFromStatus($entryVat, $entryStart, $entryEnd);
                    if ($entryData !== null) {
                        $historyEntries[] = $entryData;
                    }
                }
            }
        }

        return new VatStatusData(
            current: $current,
            history: new DataCollection(VatStatusEntryData::class, $historyEntries)
        );
    }

    protected function vatEntryFromStatus(?bool $isVatPayer, ?\DateTimeImmutable $startDate, ?\DateTimeImmutable $endDate): ?VatStatusEntryData
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
            queried_at: null
        );
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function mapLegal(array $entry): LegalData
    {
        $forma = $this->firstValue($entry, ['forma_juridica', 'formaJuridica']);
        $organizare = $this->firstValue($entry, ['organizare', 'forma_organizare']);
        $updatedAt = DateHelper::parseDate($this->firstValue($entry, ['data_actualizare', 'updated_at']));

        $current = null;
        if ($forma !== null || $organizare !== null || $updatedAt !== null) {
            $current = new LegalHistoryEntryData(
                updated_at: $updatedAt,
                name: $forma,
                organization: $organizare
            );
        }

        $historyEntries = [];
        $history = $entry['istoric_forme'] ?? $entry['legal_history'] ?? [];
        if (is_array($history)) {
            foreach ($history as $historyEntry) {
                if (! is_array($historyEntry)) {
                    continue;
                }

                $historyEntries[] = new LegalHistoryEntryData(
                    updated_at: DateHelper::parseDate($this->firstValue($historyEntry, ['data_actualizare', 'data', 'effective_date'])),
                    name: $this->firstValue($historyEntry, ['denumire', 'forma_juridica', 'formaJuridica']),
                    organization: $this->firstValue($historyEntry, ['organizare', 'forma_organizare'])
                );
            }
        }

        return new LegalData(
            current: $current,
            history: new DataCollection(LegalHistoryEntryData::class, $historyEntries)
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

        $formatted = $this->firstValue($address, ['formatat', 'adresa', 'address', 'formatted']);
        $raw = $this->firstValue($address, ['neprelucrat', 'raw', 'adresa', 'address']);
        $rawMf = $this->firstValue($address, ['neprelucrat_mf']);
        $rawRecom = $this->firstValue($address, ['neprelucrat_recom']);
        $expiration = $this->mapExpiration($address['expirare'] ?? null);

        return new AddressData(
            formatted: $formatted,
            raw: $raw,
            raw_mf: $rawMf,
            raw_recom: $rawRecom,
            country: $this->firstValue($address, ['tara', 'country']),
            county: $this->firstValue($address, ['judet', 'county']),
            city: $this->firstValue($address, ['localitate', 'oras', 'city']),
            sub_locality: $this->firstValue($address, ['sub_localitate']),
            sector: $this->firstValue($address, ['sector']),
            street: $this->firstValue($address, ['strada', 'street']),
            street_type: $this->firstValue($address, ['tip_strada']),
            number: $this->firstValue($address, ['numar', 'number']),
            building: $this->firstValue($address, ['bloc', 'building']),
            entrance: $this->firstValue($address, ['scara', 'entrance']),
            floor: $this->firstValue($address, ['etaj', 'floor']),
            apartment: $this->firstValue($address, ['apartament', 'apartment']),
            postal_code: $this->firstValue($address, ['cod_postal', 'postal_code']),
            siruta_code: $this->firstValue($address, ['cod_siruta']),
            source: $this->firstValue($address, ['sursa']),
            expiration: $expiration
        );
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
