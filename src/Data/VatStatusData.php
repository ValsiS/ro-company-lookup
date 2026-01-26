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

    public function isPayer(): ?bool
    {
        $code = $this->current?->code;
        if ($code !== null) {
            return $code === 2;
        }

        $label = $this->current?->label;
        if ($label === null) {
            return null;
        }

        $normalized = mb_strtolower($label);

        if (str_contains($normalized, 'neplatitor') || str_contains($normalized, 'neplătitor')) {
            return false;
        }

        if (str_contains($normalized, 'platitor') || str_contains($normalized, 'plătitor')) {
            return true;
        }

        return null;
    }
}
