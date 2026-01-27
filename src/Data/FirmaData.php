<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\FirmaNameMapper;

#[MapOutputName(FirmaNameMapper::class)]
class FirmaData extends Data
{
    public function __construct(
        public int $cui,
        public ?string $registration_number,
        public ?string $name,
        public ?CompanyProfileData $profile = null
    ) {}
}
