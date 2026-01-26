<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use DateTimeImmutable;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\CompanyProfileNameMapper;

#[MapOutputName(CompanyProfileNameMapper::class)]
class CompanyProfileData extends Data
{
    public function __construct(
        public ?DateTimeImmutable $registration_date,
        public ?string $registration_status,
        public ?string $fiscal_office,
        public ?string $ownership_form,
        public ?bool $e_invoice_status,
        public ?DateTimeImmutable $e_invoice_registration_date,
        public ?string $iban
    ) {}
}
