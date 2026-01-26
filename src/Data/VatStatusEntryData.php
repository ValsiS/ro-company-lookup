<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use DateTimeImmutable;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\VatStatusEntryNameMapper;

#[MapOutputName(VatStatusEntryNameMapper::class)]
class VatStatusEntryData extends Data
{
    public function __construct(
        public ?int $code,
        public ?string $label,
        public ?DateTimeImmutable $vat_start_date,
        public ?DateTimeImmutable $vat_cancel_date,
        public ?DateTimeImmutable $vat_cancel_operation_date,
        public ?DateTimeImmutable $queried_at
    ) {}
}
