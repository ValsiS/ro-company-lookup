<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\CaenSetNameMapper;

#[MapOutputName(CaenSetNameMapper::class)]
class CaenSetData extends Data
{
    public function __construct(
        public ?CaenEntryData $principal_mfinante,
        public ?CaenEntryData $principal_recom
    ) {}
}
