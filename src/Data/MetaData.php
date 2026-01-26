<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Data;

use DateTimeImmutable;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Valsis\RoCompanyLookup\Mappers\MetaNameMapper;
use Valsis\RoCompanyLookup\Support\DateHelper;

#[MapOutputName(MetaNameMapper::class)]
class MetaData extends Data
{
    /**
     * @param  array<string, mixed>|Optional  $raw
     */
    public function __construct(
        public string $source,
        public DateTimeImmutable $fetched_at,
        public string $queried_for_date,
        public bool $is_stale,
        public bool $cache_hit,
        public array|Optional $raw = new Optional
    ) {}

    public static function blank(): self
    {
        return new self(
            source: 'unknown',
            fetched_at: DateHelper::now(),
            queried_for_date: '',
            is_stale: false,
            cache_hit: false,
            raw: new Optional
        );
    }
}
