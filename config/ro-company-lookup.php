<?php

declare(strict_types=1);

return [
    'driver' => 'anaf',
    'timezone' => 'Europe/Bucharest',
    'language' => 'ro',

    'anaf' => [
        'base_url' => 'https://webservicesp.anaf.ro',
        'endpoint' => '/PlatitorTvaRest/api/v8/ws/tva',
        'timeout' => 10,
        'connect_timeout' => 5,
        'retries' => 3,
        'backoff_ms' => 250,
        'user_agent' => 'valsis/ro-company-lookup',
    ],

    'cache_store' => null,
    'cache_prefix' => 'ro-company-lookup',
    'cache_ttl_seconds' => 60 * 60 * 24,
    'stale_ttl_seconds' => 60 * 60 * 24 * 7,
    'use_locks' => true,
    'lock_seconds' => 10,
    'lock_wait_seconds' => 5,

    'batch_max_size' => 100,
    'batch_chunk_size' => 100,

    'enable_raw' => false,
];
