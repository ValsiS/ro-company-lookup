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
        public ?string $raw_mf,
        public ?string $raw_recom,
        public ?string $country,
        public ?string $county,
        public ?string $city,
        public ?string $sub_locality,
        public ?string $sector,
        public ?string $street,
        public ?string $street_type,
        public ?string $number,
        public ?string $building,
        public ?string $entrance,
        public ?string $floor,
        public ?string $apartment,
        public ?string $postal_code,
        public ?string $siruta_code,
        public ?string $source,
        public ?AddressExpirationData $expiration
    ) {}
}
