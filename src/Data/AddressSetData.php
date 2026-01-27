<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\AddressSetNameMapper;

#[MapOutputName(AddressSetNameMapper::class)]
class AddressSetData extends Data
{
    public function __construct(
        public ?AddressData $fiscal_domicile,
        public ?AddressData $registered_office
    ) {}
}
