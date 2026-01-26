# Caching and retries

## Caching

- Results are cached per (driver + CUI + date).
- Default TTL is 24 hours (`cache_ttl_seconds`).
- If a request fails and a stale entry exists within `stale_ttl_seconds`, the stale entry is returned and `meta.is_stale` is set to true.
- Cache locks can be enabled with `use_locks` to prevent stampedes.

## Retries

- Retries are applied only for HTTP 429 and 5xx responses.
- Backoff is exponential based on `anaf.backoff_ms`.
- Other 4xx responses fail fast.

## Circuit breaker

- The circuit breaker opens after repeated 5xx responses.
- When open, calls fail fast or return stale cache entries (if available).
- Cooldown duration is controlled by `circuit_breaker.cooldown_seconds`.
