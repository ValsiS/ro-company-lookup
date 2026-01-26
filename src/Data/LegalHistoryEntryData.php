<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use DateTimeImmutable;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\LegalHistoryEntryNameMapper;

#[MapOutputName(LegalHistoryEntryNameMapper::class)]
class LegalHistoryEntryData extends Data
{
    public function __construct(
        public ?DateTimeImmutable $updated_at,
        public ?string $name,
        public ?string $organization
    ) {}
}
