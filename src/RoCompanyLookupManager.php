<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup;

use DateTimeInterface;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Manager;
use Psr\Log\LoggerInterface;
use Spatie\LaravelData\Support\Transformation\TransformationContextFactory;
use Valsis\RoCompanyLookup\Batch\BatchLookup;
use Valsis\RoCompanyLookup\Contracts\RoCompanyLookupDriver;
use Valsis\RoCompanyLookup\Data\CompanySimpleData;
use Valsis\RoCompanyLookup\Data\LookupResultData;
use Valsis\RoCompanyLookup\Drivers\AnafDriver;
use Valsis\RoCompanyLookup\Exceptions\CircuitOpenException;
use Valsis\RoCompanyLookup\Exceptions\InvalidCuiException;
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
        $cacheVersion = config('ro-company-lookup.cache_version', 'v1');
        $cachePrefix = sprintf('%s:%s', $cachePrefix, $cacheVersion);

        $cacheKey = CacheKey::forLookup($cachePrefix, $driverName, $normalizedCui, $dateString);
        $lockKey = CacheKey::forLock($cachePrefix, $driverName, $normalizedCui, $dateString);
        $circuitKey = CacheKey::forCircuit($cachePrefix, $driverName);
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
            $cachedEntry,
            $circuitKey
        ) {
            $cachedEntry = $cache->get($cacheKey);
            if (is_array($cachedEntry)) {
                $cached = $this->hydrateCachedEntry($cachedEntry, $dateString, $includeRaw);
                if ($cached['is_fresh']) {
                    return $cached['data'];
                }
            }

            if ($this->isCircuitOpen($cache, $circuitKey)) {
                $this->log('warning', 'ro-company-lookup.circuit_open', [
                    'driver' => $driverName,
                    'cui' => $normalizedCui,
                    'date' => $dateString,
                ]);

                if (is_array($cachedEntry)) {
                    $cached = $this->hydrateCachedEntry($cachedEntry, $dateString, $includeRaw, true);
                    if ($cached['is_stale']) {
                        return $cached['data'];
                    }
                }

                throw new CircuitOpenException('Service temporarily unavailable (circuit open).', 503);
            }

            $startedAt = microtime(true);
            $this->log('info', 'ro-company-lookup.request', [
                'driver' => $driverName,
                'cui' => $normalizedCui,
                'date' => $dateString,
            ]);

            try {
                $response = $this->driver($driverName)->lookup($normalizedCui, $date);
            } catch (LookupFailedException $exception) {
                $this->recordCircuitFailure($cache, $circuitKey, $exception);
                $this->log('error', 'ro-company-lookup.failure', [
                    'driver' => $driverName,
                    'cui' => $normalizedCui,
                    'date' => $dateString,
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'duration_ms' => $this->durationMs($startedAt),
                ]);

                if (is_array($cachedEntry)) {
                    $cached = $this->hydrateCachedEntry($cachedEntry, $dateString, $includeRaw, true);
                    if ($cached['is_stale']) {
                        return $cached['data'];
                    }
                }

                throw $exception;
            }

            $this->clearCircuit($cache, $circuitKey);
            $this->log('info', 'ro-company-lookup.response', [
                'driver' => $driverName,
                'cui' => $normalizedCui,
                'date' => $dateString,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            $data = $response->data;
            $fetchedAt = DateHelper::now();

            $data->meta->cache_hit = false;
            $data->meta->is_stale = false;
            $data->meta->queried_for_date = $dateString;
            $data->meta->source = $driverName;
            $data->meta->fetched_at = $fetchedAt;

            $this->applyVatQueryDate($data->vat, $fetchedAt);

            if ($includeRaw || config('ro-company-lookup.enable_raw', false)) {
                $data->meta->raw = $response->raw ?? [];
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

    public function tryLookup(int|string $cui, ?DateTimeInterface $date = null, bool $includeRaw = false): LookupResultData
    {
        try {
            $data = $this->lookup($cui, $date, $includeRaw);
        } catch (InvalidCuiException $exception) {
            return LookupResultData::invalid($exception->getMessage());
        } catch (CircuitOpenException $exception) {
            return LookupResultData::error($exception->getMessage(), 'circuit_open', $exception->getCode());
        } catch (LookupFailedException $exception) {
            return LookupResultData::error($exception->getMessage(), 'lookup_failed', $exception->getCode());
        }

        return $this->resultFromData($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(int|string $cui, ?DateTimeInterface $date = null): array
    {
        $data = $this->lookup($cui, $date);

        return $data->summary();
    }

    public function trySummary(int|string $cui, ?DateTimeInterface $date = null): LookupResultData
    {
        return $this->tryLookup($cui, $date);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function summaryOrNull(int|string $cui, ?DateTimeInterface $date = null): ?array
    {
        $result = $this->tryLookup($cui, $date);
        if (! $result->exists()) {
            return null;
        }

        return $result->summary();
    }

    /**
     * @return array<string, mixed>
     */
    public function summaryOrFail(int|string $cui, ?DateTimeInterface $date = null): array
    {
        $result = $this->tryLookup($cui, $date);
        if (! $result->exists()) {
            throw new LookupFailedException($result->message ?? 'Company not found.');
        }

        return $result->summary();
    }

    /**
     * @param  array<int, int|string>  $cuis
     * @return array<int, array<string, mixed>>
     */
    public function batchSummary(array $cuis, ?DateTimeInterface $date = null): array
    {
        $results = $this->tryBatchNow($cuis, $date);
        $summaries = [];

        foreach ($results as $index => $result) {
            $summaries[$index] = $result->summary();
        }

        return $summaries;
    }

    public function exists(int|string $cui, ?DateTimeInterface $date = null): bool
    {
        return $this->tryLookup($cui, $date)->exists();
    }

    public function normalizeCui(int|string $cui): int
    {
        return NormalizeCui::normalize($cui);
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
     * @return array<int, LookupResultData>
     */
    public function tryBatchNow(array $cuis, ?DateTimeInterface $date = null): array
    {
        $valid = [];
        $indexMap = [];
        $results = [];

        foreach ($cuis as $index => $cui) {
            try {
                $normalized = NormalizeCui::normalize($cui);
                $valid[] = $normalized;
                $indexMap[] = $index;
            } catch (InvalidCuiException $exception) {
                $results[$index] = LookupResultData::invalid($exception->getMessage());
            }
        }

        if ($valid !== []) {
            $dataItems = $this->batchNow($valid, $date);

            foreach ($dataItems as $position => $data) {
                $index = $indexMap[$position] ?? null;
                if ($index === null) {
                    continue;
                }

                $results[$index] = $this->resultFromData($data);
            }
        }

        ksort($results);

        return array_values($results);
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
        $cacheVersion = config('ro-company-lookup.cache_version', 'v1');
        $cachePrefix = sprintf('%s:%s', $cachePrefix, $cacheVersion);
        $cacheTtl = (int) config('ro-company-lookup.cache_ttl_seconds', 86400);
        $staleTtl = (int) config('ro-company-lookup.stale_ttl_seconds', 0);
        $includeRaw = (bool) config('ro-company-lookup.enable_raw', false);
        $circuitKey = CacheKey::forCircuit($cachePrefix, $driverName);

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
            if ($this->isCircuitOpen($cache, $circuitKey)) {
                $this->log('warning', 'ro-company-lookup.circuit_open', [
                    'driver' => $driverName,
                    'count' => count($chunk),
                    'date' => $dateString,
                ]);

                foreach ($chunk as $cui) {
                    if (isset($staleCandidates[$cui])) {
                        $results[$cui] = $staleCandidates[$cui];

                        continue;
                    }

                    throw new CircuitOpenException('Service temporarily unavailable (circuit open).', 503);
                }

                continue;
            }

            $startedAt = microtime(true);
            $this->log('info', 'ro-company-lookup.request', [
                'driver' => $driverName,
                'count' => count($chunk),
                'date' => $dateString,
            ]);

            try {
                /** @var \Valsis\RoCompanyLookup\Drivers\DriverResponse[] $responses */
                $responses = $this->driver($driverName)->batch($chunk, $date);
            } catch (LookupFailedException $exception) {
                $this->recordCircuitFailure($cache, $circuitKey, $exception);
                $this->log('error', 'ro-company-lookup.failure', [
                    'driver' => $driverName,
                    'count' => count($chunk),
                    'date' => $dateString,
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'duration_ms' => $this->durationMs($startedAt),
                ]);

                foreach ($chunk as $cui) {
                    if (isset($staleCandidates[$cui])) {
                        $results[$cui] = $staleCandidates[$cui];

                        continue;
                    }

                    throw $exception;
                }

                continue;
            }

            $this->clearCircuit($cache, $circuitKey);
            $this->log('info', 'ro-company-lookup.response', [
                'driver' => $driverName,
                'count' => count($chunk),
                'date' => $dateString,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

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
                    $data->meta->raw = $response->raw ?? [];
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

    protected function resultFromData(CompanySimpleData $data): LookupResultData
    {
        if ($this->isNotFound($data)) {
            return LookupResultData::notFound($data);
        }

        return LookupResultData::ok($data);
    }

    protected function isNotFound(CompanySimpleData $data): bool
    {
        return $data->company->name_mfinante === null
            && $data->company->registration_number === null
            && $data->caen->principal_mfinante === null
            && $data->address->anaf === null
            && $data->address->registered_office === null
            && $data->vat->current === null
            && $data->legal->current === null;
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
            $data->meta->raw = $raw ?? [];
        }

        return [
            'data' => $data,
            'is_fresh' => $isFresh,
            'is_stale' => $isStale,
        ];
    }

    protected function logger(): ?LoggerInterface
    {
        $config = config('ro-company-lookup.logging', []);
        $enabled = (bool) ($config['enabled'] ?? false);
        if (! $enabled) {
            return null;
        }

        $channel = $config['channel'] ?? null;
        if (is_string($channel) && $channel !== '') {
            return Log::channel($channel);
        }

        return app(LoggerInterface::class);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $logger = $this->logger();
        if (! $logger) {
            return;
        }

        $config = config('ro-company-lookup.logging', []);
        $configuredLevel = (string) ($config['level'] ?? $level);

        $logger->log($configuredLevel, $message, $context);
    }

    protected function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    protected function isCircuitOpen(\Illuminate\Contracts\Cache\Repository $cache, string $key): bool
    {
        $config = config('ro-company-lookup.circuit_breaker', []);
        if (! ($config['enabled'] ?? true)) {
            return false;
        }

        $threshold = (int) ($config['failure_threshold'] ?? 3);
        $cooldown = (int) ($config['cooldown_seconds'] ?? 60);
        if ($threshold <= 0 || $cooldown <= 0) {
            return false;
        }

        $state = $cache->get($key);
        if (! is_array($state)) {
            return false;
        }

        $failures = (int) ($state['failures'] ?? 0);
        $lastFailure = $state['last_failure_at'] ?? null;
        if ($failures < $threshold || ! is_string($lastFailure) || $lastFailure === '') {
            return false;
        }

        $lastFailureAt = DateHelper::parseCacheDateTime($lastFailure);
        if (! $lastFailureAt) {
            return false;
        }

        $elapsed = DateHelper::now()->getTimestamp() - $lastFailureAt->getTimestamp();
        if ($elapsed <= $cooldown) {
            return true;
        }

        $cache->forget($key);

        return false;
    }

    protected function recordCircuitFailure(\Illuminate\Contracts\Cache\Repository $cache, string $key, LookupFailedException $exception): void
    {
        if ($exception instanceof CircuitOpenException) {
            return;
        }

        $code = (int) $exception->getCode();
        if ($code < 500 || $code >= 600) {
            return;
        }

        $config = config('ro-company-lookup.circuit_breaker', []);
        if (! ($config['enabled'] ?? true)) {
            return;
        }

        $cooldown = (int) ($config['cooldown_seconds'] ?? 60);
        if ($cooldown <= 0) {
            return;
        }

        $state = $cache->get($key);
        $failures = is_array($state) ? (int) ($state['failures'] ?? 0) : 0;

        $cache->put($key, [
            'failures' => $failures + 1,
            'last_failure_at' => DateHelper::now()->format(DateHelper::CACHE_DATETIME_FORMAT),
        ], $cooldown);
    }

    protected function clearCircuit(\Illuminate\Contracts\Cache\Repository $cache, string $key): void
    {
        $cache->forget($key);
    }
}
