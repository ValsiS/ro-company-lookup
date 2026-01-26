<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Valsis\RoCompanyLookup\Mappers\LegalNameMapper;

#[MapOutputName(LegalNameMapper::class)]
class LegalData extends Data
{
    /**
     * @param  DataCollection<int, LegalHistoryEntryData>  $history
     */
    public function __construct(
        public ?LegalHistoryEntryData $current,
        #[DataCollectionOf(LegalHistoryEntryData::class)]
        public DataCollection $history
    ) {}
}
