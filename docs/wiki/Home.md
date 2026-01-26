# Ro Company Lookup

A Laravel package for retrieving basic Romanian company data by CUI from ANAF public web services.

## Quick start

```bash
composer require valsis/ro-company-lookup
php artisan vendor:publish --tag=ro-company-lookup-config
```

```php
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;

$company = RoCompanyLookup::lookup('RO123456');
```

## Key features

- Single and batch lookup (up to 100 CUIs per ANAF request)
- Caching with stale fallback
- Retries with exponential backoff
- DTOs via spatie/laravel-data
- ANAF-aligned output structure with optional EN key naming

## Documentation pages

- Installation: `docs/wiki/Installation.md`
- Configuration: `docs/wiki/Configuration.md`
- Usage: `docs/wiki/Usage.md`
- Batch lookups: `docs/wiki/Batch.md`
- DTOs and output shape: `docs/wiki/DTOs.md`
- Caching and retries: `docs/wiki/Caching-Retries.md`
- Artisan command: `docs/wiki/Commands.md`
- Troubleshooting: `docs/wiki/Troubleshooting.md`
- JSON Schemas: `docs/schemas`
