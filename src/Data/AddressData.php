<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\AddressNameMapper;

#[MapOutputName(AddressNameMapper::class)]
class AddressData extends Data
{
    public function __construct(
        public ?string $formatted,
        public ?string $raw,
        public ?string $country,
        public ?string $county,
        public ?string $county_code,
        public ?string $county_auto_code,
        public ?string $city,
        public ?string $locality_code,
        public ?string $street,
        public ?string $number,
        public ?string $postal_code,
        public ?string $details
    ) {}
}
