<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup;

use DateTimeInterface;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Manager;
use Spatie\LaravelData\Support\Transformation\TransformationContextFactory;
use Valsis\RoCompanyLookup\Batch\BatchLookup;
use Valsis\RoCompanyLookup\Contracts\RoCompanyLookupDriver;
use Valsis\RoCompanyLookup\Data\CompanySimpleData;
use Valsis\RoCompanyLookup\Drivers\AnafDriver;
use Valsis\RoCompanyLookup\Exceptions\LookupFailedException;
use Valsis\RoCompanyLookup\Support\CacheKey;
use Valsis\RoCompanyLookup\Support\DateHelper;
use Valsis\RoCompanyLookup\Support\NormalizeCui;

class RoCompanyLookupManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('ro-company-lookup.driver', 'anaf');
    }

    public function createAnafDriver(): RoCompanyLookupDriver
    {
        return new AnafDriver;
    }

    public function lookup(int|string $cui, ?DateTimeInterface $date = null, bool $includeRaw = false): CompanySimpleData
    {
        $normalizedCui = NormalizeCui::normalize($cui);
        $date = DateHelper::normalizeDate($date);
        $dateString = DateHelper::formatDate($date);

        $driverName = $this->getDefaultDriver();
        $cacheStore = config('ro-company-lookup.cache_store');
        /** @var \Illuminate\Contracts\Cache\Repository $cache */
        $cache = cache()->store($cacheStore);
        $cachePrefix = config('ro-company-lookup.cache_prefix', 'ro-company-lookup');

        $cacheKey = CacheKey::forLookup($cachePrefix, $driverName, $normalizedCui, $dateString);
        $lockKey = CacheKey::forLock($cachePrefix, $driverName, $normalizedCui, $dateString);
        $cacheTtl = (int) config('ro-company-lookup.cache_ttl_seconds', 86400);
        $staleTtl = (int) config('ro-company-lookup.stale_ttl_seconds', 0);
        $useLocks = (bool) config('ro-company-lookup.use_locks', true);

        $cachedEntry = $cache->get($cacheKey);
        if (is_array($cachedEntry)) {
            $cached = $this->hydrateCachedEntry($cachedEntry, $dateString, $includeRaw);
            if ($cached['is_fresh']) {
                return $cached['data'];
            }
        }

        $fetchRemote = function () use (
            $cache,
            $cacheKey,
            $normalizedCui,
            $date,
            $dateString,
            $driverName,
            $cacheTtl,
            $staleTtl,
            $includeRaw,
            $cachedEntry
        ) {
            $cachedEntry = $cache->get($cacheKey);
            if (is_array($cachedEntry)) {
                $cached = $this->hydrateCachedEntry($cachedEntry, $dateString, $includeRaw);
                if ($cached['is_fresh']) {
                    return $cached['data'];
                }
            }

            try {
                $response = $this->driver($driverName)->lookup($normalizedCui, $date);
            } catch (LookupFailedException $exception) {
                if (is_array($cachedEntry)) {
                    $cached = $this->hydrateCachedEntry($cachedEntry, $dateString, $includeRaw, true);
                    if ($cached['is_stale']) {
                        return $cached['data'];
                    }
                }

                throw $exception;
            }

            $data = $response->data;
            $fetchedAt = DateHelper::now();

            $data->meta->cache_hit = false;
            $data->meta->is_stale = false;
            $data->meta->queried_for_date = $dateString;
            $data->meta->source = $driverName;
            $data->meta->fetched_at = $fetchedAt;

            $this->applyVatQueryDate($data->vat, $fetchedAt);

            if ($includeRaw || config('ro-company-lookup.enable_raw', false)) {
                $data->meta->raw = $response->raw;
            }

            $cache->put($cacheKey, [
                'cached_at' => $fetchedAt->format(DateHelper::CACHE_DATETIME_FORMAT),
                'data' => $this->serializeForCache($data),
                'raw' => $response->raw,
            ], $cacheTtl + $staleTtl);

            return $data;
        };

        if (! $useLocks || ! $cache instanceof \Illuminate\Contracts\Cache\LockProvider) {
            return $fetchRemote();
        }

        $lockSeconds = (int) config('ro-company-lookup.lock_seconds', 10);
        $waitSeconds = (int) config('ro-company-lookup.lock_wait_seconds', 5);

        try {
            return $cache->lock($lockKey, $lockSeconds)->block($waitSeconds, $fetchRemote);
        } catch (LockTimeoutException $exception) {
            if (is_array($cachedEntry)) {
                $cached = $this->hydrateCachedEntry($cachedEntry, $dateString, $includeRaw, true);
                if ($cached['is_stale']) {
                    return $cached['data'];
                }
            }

            throw new LookupFailedException('Failed to acquire cache lock for lookup.', previous: $exception);
        }
    }

    /**
     * @param  array<int, int|string>  $cuis
     */
    public function batch(array $cuis, ?DateTimeInterface $date = null): BatchLookup
    {
        return new BatchLookup($this, $cuis, $date);
    }

    /**
     * @param  array<int, int|string>  $cuis
     * @return array<int, CompanySimpleData>
     */
    public function batchNow(array $cuis, ?DateTimeInterface $date = null): array
    {
        $normalized = NormalizeCui::normalizeMany($cuis);
        $date = DateHelper::normalizeDate($date);
        $dateString = DateHelper::formatDate($date);

        $driverName = $this->getDefaultDriver();
        $cacheStore = config('ro-company-lookup.cache_store');
        /** @var \Illuminate\Contracts\Cache\Repository $cache */
        $cache = cache()->store($cacheStore);
        $cachePrefix = config('ro-company-lookup.cache_prefix', 'ro-company-lookup');
        $cacheTtl = (int) config('ro-company-lookup.cache_ttl_seconds', 86400);
        $staleTtl = (int) config('ro-company-lookup.stale_ttl_seconds', 0);
        $includeRaw = (bool) config('ro-company-lookup.enable_raw', false);

        $results = [];
        $staleCandidates = [];
        $missing = [];

        foreach ($normalized as $cui) {
            $cacheKey = CacheKey::forLookup($cachePrefix, $driverName, $cui, $dateString);
            $cachedEntry = $cache->get($cacheKey);
            if (is_array($cachedEntry)) {
                $cached = $this->hydrateCachedEntry($cachedEntry, $dateString, $includeRaw, true);
                if ($cached['is_fresh']) {
                    $results[$cui] = $cached['data'];

                    continue;
                }

                if ($cached['is_stale']) {
                    $staleCandidates[$cui] = $cached['data'];
                }
            }

            $missing[] = $cui;
        }

        if (count($missing) === 0) {
            return $this->orderedResults($results, $normalized);
        }

        $maxBatchSize = (int) config('ro-company-lookup.batch_max_size', 100);
        $chunkSize = (int) config('ro-company-lookup.batch_chunk_size', $maxBatchSize);
        $chunkSize = max(1, min($chunkSize, $maxBatchSize));

        $chunks = array_chunk($missing, $chunkSize);

        foreach ($chunks as $chunk) {
            try {
                /** @var \Valsis\RoCompanyLookup\Drivers\DriverResponse[] $responses */
                $responses = $this->driver($driverName)->batch($chunk, $date);
            } catch (LookupFailedException $exception) {
                foreach ($chunk as $cui) {
                    if (isset($staleCandidates[$cui])) {
                        $results[$cui] = $staleCandidates[$cui];

                        continue;
                    }

                    throw $exception;
                }

                continue;
            }

            foreach ($responses as $response) {
                $data = $response->data;
                $fetchedAt = DateHelper::now();
                $data->meta->cache_hit = false;
                $data->meta->is_stale = false;
                $data->meta->queried_for_date = $dateString;
                $data->meta->source = $driverName;
                $data->meta->fetched_at = $fetchedAt;

                $this->applyVatQueryDate($data->vat, $fetchedAt);

                if ($includeRaw) {
                    $data->meta->raw = $response->raw;
                }

                $cacheKey = CacheKey::forLookup($cachePrefix, $driverName, $data->company->cui, $dateString);
                $cache->put($cacheKey, [
                    'cached_at' => $fetchedAt->format(DateHelper::CACHE_DATETIME_FORMAT),
                    'data' => $this->serializeForCache($data),
                    'raw' => $response->raw,
                ], $cacheTtl + $staleTtl);

                $results[$data->company->cui] = $data;
            }
        }

        return $this->orderedResults($results, $normalized);
    }

    /**
     * @param  array<int, CompanySimpleData>  $results
     * @param  array<int, int>  $order
     * @return array<int, CompanySimpleData>
     */
    protected function orderedResults(array $results, array $order): array
    {
        $ordered = [];

        foreach ($order as $cui) {
            if (isset($results[$cui])) {
                $ordered[] = $results[$cui];
            }
        }

        return $ordered;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeForCache(CompanySimpleData $data): array
    {
        return $data->transform(
            TransformationContextFactory::create()->withoutPropertyNameMapping()
        );
    }

    protected function applyVatQueryDate(\Valsis\RoCompanyLookup\Data\VatStatusData $vat, \DateTimeImmutable $fetchedAt): void
    {
        if ($vat->current && $vat->current->queried_at === null) {
            $vat->current->queried_at = $fetchedAt;
        }

        foreach ($vat->history as $entry) {
            if ($entry->queried_at === null) {
                $entry->queried_at = $fetchedAt;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $cachedEntry
     * @return array{data: CompanySimpleData, is_fresh: bool, is_stale: bool}
     */
    protected function hydrateCachedEntry(array $cachedEntry, string $dateString, bool $includeRaw, bool $allowStale = false): array
    {
        $cachedAt = $cachedEntry['cached_at'] ?? null;
        $cacheTtl = (int) config('ro-company-lookup.cache_ttl_seconds', 86400);
        $staleTtl = (int) config('ro-company-lookup.stale_ttl_seconds', 0);
        $cachedAtDate = $cachedAt ? DateHelper::parseCacheDateTime($cachedAt) : null;

        $ageSeconds = $cachedAtDate ? DateHelper::now()->getTimestamp() - $cachedAtDate->getTimestamp() : null;
        $isFresh = $ageSeconds !== null && $ageSeconds <= $cacheTtl;
        $isStale = $allowStale
            && $staleTtl > 0
            && ! $isFresh
            && ($ageSeconds === null || $ageSeconds <= $cacheTtl + $staleTtl);

        $dataArray = $cachedEntry['data'] ?? [];
        $raw = $cachedEntry['raw'] ?? null;

        $data = CompanySimpleData::from($dataArray);
        $data->meta->cache_hit = true;
        $data->meta->is_stale = $isStale;
        $data->meta->queried_for_date = $dateString;

        if ($includeRaw || config('ro-company-lookup.enable_raw', false)) {
            $data->meta->raw = $raw;
        }

        return [
            'data' => $data,
            'is_fresh' => $isFresh,
            'is_stale' => $isStale,
        ];
    }
}
