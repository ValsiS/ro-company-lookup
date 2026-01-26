<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\ContactNameMapper;

#[MapOutputName(ContactNameMapper::class)]
class ContactData extends Data
{
    public function __construct(
        /** @var array<int, string> */
        public array $phones,
        /** @var array<int, string> */
        public array $emails
    ) {}
}
