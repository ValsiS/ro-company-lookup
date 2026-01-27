<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use DateTimeImmutable;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\SplitVatNameMapper;

#[MapOutputName(SplitVatNameMapper::class)]
class SplitVatData extends Data
{
    public function __construct(
        public ?bool $enabled,
        public ?DateTimeImmutable $start_date,
        public ?DateTimeImmutable $cancelled_at
    ) {}
}
