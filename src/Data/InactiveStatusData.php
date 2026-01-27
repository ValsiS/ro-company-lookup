<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use DateTimeImmutable;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Valsis\RoCompanyLookup\Mappers\InactiveStatusNameMapper;

#[MapOutputName(InactiveStatusNameMapper::class)]
class InactiveStatusData extends Data
{
    public function __construct(
        public ?bool $is_inactive,
        public ?DateTimeImmutable $inactivated_at,
        public ?DateTimeImmutable $reactivated_at,
        public ?DateTimeImmutable $published_at,
        public ?DateTimeImmutable $removed_at
    ) {}
}
