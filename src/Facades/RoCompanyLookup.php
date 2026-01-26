<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Valsis\RoCompanyLookup\Data\CompanySimpleData lookup(int|string $cui, ?\DateTimeInterface $date = null, bool $includeRaw = false)
 * @method static \Valsis\RoCompanyLookup\Batch\BatchLookup batch(array<int, int|string> $cuis, ?\DateTimeInterface $date = null)
 * @method static \Valsis\RoCompanyLookup\RoCompanyLookupManager driver(string|null $name = null)
 */
class RoCompanyLookup extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ro-company-lookup';
    }
}
