<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use DateTimeImmutable;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\VatCollectionNameMapper;

#[MapOutputName(VatCollectionNameMapper::class)]
class VatCollectionData extends Data
{
    public function __construct(
        public ?bool $enabled,
        public ?DateTimeImmutable $start_date,
        public ?DateTimeImmutable $end_date,
        public ?DateTimeImmutable $published_at,
        public ?DateTimeImmutable $updated_at,
        public ?string $act_type
    ) {}
}
