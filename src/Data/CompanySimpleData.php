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
        public CaenSetData $caen,
        public ContactData $contact,
        public FirmaData $company,
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
}
