<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Support;

class CacheKey
{
    public static function forLookup(string $prefix, string $driver, int $cui, string $date): string
    {
        return sprintf('%s:%s:%s:%s', $prefix, $driver, $cui, $date);
    }

    public static function forLock(string $prefix, string $driver, int $cui, string $date): string
    {
        return sprintf('%s:%s:%s:%s:lock', $prefix, $driver, $cui, $date);
    }
}
