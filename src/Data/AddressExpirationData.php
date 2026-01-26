<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use DateTimeImmutable;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\AddressExpirationNameMapper;

#[MapOutputName(AddressExpirationNameMapper::class)]
class AddressExpirationData extends Data
{
    public function __construct(
        public ?DateTimeImmutable $updated_at,
        public ?DateTimeImmutable $expires_at,
        public ?bool $is_expired
    ) {}
}
