# Batch lookups

```php
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;

$companies = RoCompanyLookup::batch(['RO123', 'RO456'])->get();
```

## Notes

- ANAF supports up to 100 CUIs per request.
- The package enforces `batch_max_size` and chunks based on `batch_chunk_size`.
- Cache is read per-item and merged with remote results.
- Stale fallback is applied per item when remote calls fail.
