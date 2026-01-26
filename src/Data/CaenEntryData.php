<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\CaenEntryNameMapper;

#[MapOutputName(CaenEntryNameMapper::class)]
class CaenEntryData extends Data
{
    public function __construct(
        public ?string $code,
        public ?string $label,
        public ?int $version
    ) {}
}
