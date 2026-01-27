<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Valsis\RoCompanyLookup\Data\CompanySimpleData lookup(int|string $cui, ?\DateTimeInterface $date = null, bool $includeRaw = false)
 * @method static \Valsis\RoCompanyLookup\Data\LookupResultData tryLookup(int|string $cui, ?\DateTimeInterface $date = null, bool $includeRaw = false)
 * @method static array<string, mixed> summary(int|string $cui, ?\DateTimeInterface $date = null)
 * @method static \Valsis\RoCompanyLookup\Data\LookupResultData trySummary(int|string $cui, ?\DateTimeInterface $date = null)
 * @method static array<string, mixed>|null summaryOrNull(int|string $cui, ?\DateTimeInterface $date = null)
 * @method static array<string, mixed> summaryOrFail(int|string $cui, ?\DateTimeInterface $date = null)
 * @method static array<int, array<string, mixed>> batchSummary(array<int, int|string> $cuis, ?\DateTimeInterface $date = null)
 * @method static array<string, mixed> summaryOrResult(int|string $cui, ?\DateTimeInterface $date = null)
 * @method static array<int, array<string, mixed>> batchSummaryWithStatus(array<int, int|string> $cuis, ?\DateTimeInterface $date = null)
 * @method static bool exists(int|string $cui, ?\DateTimeInterface $date = null)
 * @method static bool isValidCui(int|string $cui)
 * @method static array<string, mixed> summarySafe(int|string $cui, ?\DateTimeInterface $date = null)
 * @method static array<int|string, array<string, mixed>> batchSummaryMap(array<int, int|string> $cuis, ?\DateTimeInterface $date = null)
 * @method static array<string, mixed> lookupFormatted(int|string $cui, ?\DateTimeInterface $date = null, ?string $format = null, ?string $language = null)
 * @method static array<string, mixed> tryLookupFormatted(int|string $cui, ?\DateTimeInterface $date = null, ?string $format = null, ?string $language = null)
 * @method static int normalizeCui(int|string $cui)
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
