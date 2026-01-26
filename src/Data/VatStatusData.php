<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Valsis\RoCompanyLookup\Mappers\VatStatusNameMapper;

#[MapOutputName(VatStatusNameMapper::class)]
class VatStatusData extends Data
{
    /**
     * @param  DataCollection<int, VatStatusEntryData>  $history
     */
    public function __construct(
        public ?VatStatusEntryData $current,
        #[DataCollectionOf(VatStatusEntryData::class)]
        public DataCollection $history
    ) {}
}
