# Batch lookups

```php
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;

$companies = RoCompanyLookup::batch(['RO123', 'RO456'])->get();
```

Soft batch lookup (no exceptions):

```php
$results = RoCompanyLookup::batch(['RO123', 'BAD', 'RO456'])->tryGet();
```

## Notes

- ANAF supports up to 100 CUIs per request.
- The package enforces `batch_max_size` and chunks based on `batch_chunk_size`.
- Cache is read per-item and merged with remote results.
- Stale fallback is applied per item when remote calls fail.
- `tryGet()` returns a list of `LookupResultData` items with `status` per CUI.
