# Configuration

All configuration lives in `config/ro-company-lookup.php`.

## Core

- `driver`: active driver, default `anaf`
- `timezone`: used when no date is provided, default `Europe/Bucharest`
- `language`: output key naming, `ro` or `en`

## ANAF HTTP

- `anaf.base_url`: API base URL
- `anaf.endpoint`: API endpoint path
- `anaf.timeout`: request timeout (seconds)
- `anaf.connect_timeout`: connect timeout (seconds)
- `anaf.retries`: number of retries for 429/5xx
- `anaf.backoff_ms`: base backoff in ms (exponential)
- `anaf.user_agent`: user agent string

## Cache

- `cache_store`: null for default store or a named store
- `cache_prefix`: prefix for cache keys
- `cache_version`: cache version segment for safe invalidation
- `cache_ttl_seconds`: TTL for fresh cache
- `stale_ttl_seconds`: optional stale fallback window
- `use_locks`: enable cache lock single-flight
- `lock_seconds`: lock TTL
- `lock_wait_seconds`: how long to wait for a lock

## Batch

- `batch_max_size`: max CUIs per ANAF request (hard limit 100)
- `batch_chunk_size`: chunk size used by the package

## Logging

- `logging.enabled`: enable PSR-3 logging
- `logging.channel`: optional log channel
- `logging.level`: log level (default `info`)

## Schema audit

- `schema_audit.enabled`: enable unknown-key detection
- `schema_audit.fail_on_unknown`: throw when unknown keys are found
- `schema_audit.snapshot_path`: directory to write JSON snapshots (optional)
- `schema_audit.channel`: log channel override
- `schema_audit.level`: log level (default `warning`)

## Circuit breaker

- `circuit_breaker.enabled`: enable/disable circuit breaker
- `circuit_breaker.failure_threshold`: number of 5xx failures before opening
- `circuit_breaker.cooldown_seconds`: cooldown duration before closing

## Raw payload

- `enable_raw`: when true, include `meta.raw` in DTOs
