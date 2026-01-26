<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Mappers;

final class MetaNameMapper extends AbstractLanguageNameMapper
{
    protected array $ro = [
        'source' => 'sursa',
        'fetched_at' => 'data_interogare',
        'queried_for_date' => 'data_ceruta',
        'is_stale' => 'este_stale',
        'cache_hit' => 'cache_hit',
        'raw' => 'raw',
    ];

    protected array $en = [
        'source' => 'source',
        'fetched_at' => 'fetched_at',
        'queried_for_date' => 'requested_date',
        'is_stale' => 'is_stale',
        'cache_hit' => 'cache_hit',
        'raw' => 'raw',
    ];
}
