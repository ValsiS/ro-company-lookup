<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\CompanySimpleNameMapper;

#[MapOutputName(CompanySimpleNameMapper::class)]
class CompanySimpleData extends Data
{
    public function __construct(
        public AddressSetData $address,
        public ?CaenEntryData $caen,
        public ContactData $contact,
        public FirmaData $company,
        public ?VatCollectionData $vat_collection,
        public ?InactiveStatusData $inactive_status,
        public ?SplitVatData $split_vat,
        public LegalData $legal,
        public VatStatusData $vat,
        public MetaData $meta
    ) {}

    public function isVatPayer(): ?bool
    {
        return $this->vat->isPayer();
    }

    public function registrationDate(): ?\DateTimeImmutable
    {
        return $this->company->profile?->registration_date;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'exists' => true,
            'cui' => $this->company->cui,
            'name' => $this->company->name,
            'caen' => $this->caen?->code,
            'registration_date' => \Valsis\RoCompanyLookup\Support\DateHelper::formatOutputDate(
                $this->registrationDate()
            ),
            'vat_payer' => $this->isVatPayer(),
        ];
    }

    public function withSummary(): self
    {
        $this->meta->summary = $this->summary();

        return $this;
    }
}
