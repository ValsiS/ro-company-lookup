# Ro Company Lookup

[![Tests](https://img.shields.io/github/actions/workflow/status/ValsiS/ro-company-lookup/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ValsiS/ro-company-lookup/actions/workflows/run-tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/valsis/ro-company-lookup.svg?style=flat-square)](https://packagist.org/packages/valsis/ro-company-lookup)
[![PHP Version](https://img.shields.io/packagist/php-v/valsis/ro-company-lookup.svg?style=flat-square)](https://packagist.org/packages/valsis/ro-company-lookup)
[![Total Downloads](https://img.shields.io/packagist/dt/valsis/ro-company-lookup.svg?style=flat-square)](https://packagist.org/packages/valsis/ro-company-lookup)
[![License](https://img.shields.io/packagist/l/valsis/ro-company-lookup.svg?style=flat-square)](LICENSE)

A production-ready Laravel package that retrieves basic Romanian company data by CUI from ANAF public web services.
Current version: `v0.1.0`.

## Requirements

- PHP 8.3+
- Laravel 12+

## Installation

```bash
composer require valsis/ro-company-lookup
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=ro-company-lookup-config
```

Config options include timeouts, retries, cache TTL, stale TTL, and raw payload access. Additional options:

- `timezone` (default `Europe/Bucharest`)
- `language` (`ro` or `en` for output field naming)
- `use_locks` to toggle cache locks for single-flight protection
- `batch_max_size` and `batch_chunk_size` for batching behavior

## Usage

```php
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;

$company = RoCompanyLookup::lookup('RO123456');

$companyWithDate = RoCompanyLookup::lookup('123456', new DateTimeImmutable('2024-01-10'));
```

The lookup accepts `RO123`, ` ro 123 `, or `123` and normalizes to an integer CUI. By default, the query date is "today" in `Europe/Bucharest`.

## Documentation

Full documentation is available in the repo wiki pages under `docs/wiki`. Start with:

- `docs/wiki/Home.md`

### Field naming & structure

Output follows the Termene.ro structure as closely as possible (e.g., `firma`, `adresa`, `cod_caen`, `date_contact`, `forma_juridica`, `statut_tva`). Use the `language` config to switch between `ro` and `en` output keys. Internally you can still access properties using the original PHP property names.

### Batch lookup

```php
$companies = RoCompanyLookup::batch(['RO123', 'RO456'])->get();
```

ANAF supports up to 100 CUIs per request. The package enforces this limit.

### Raw payload access

To include raw ANAF payloads in the DTO meta object:

```php
// config/ro-company-lookup.php
'enable_raw' => true,
```

You can also include raw data per command call with `--raw`.

## Caching, retries, and stale fallback

- Results are cached by driver + CUI + date.
- Default cache TTL is 24 hours.
- When a request fails and a stale cache entry exists (within the configured stale TTL), the stale entry is returned with `meta.is_stale = true`.
- Retries use exponential backoff and only trigger on 429 and 5xx responses.

## Artisan command

```bash
php artisan ro-company-lookup:check 123456 --date=2024-01-10 --raw
```

The command outputs JSON to stdout.

## Testing

```bash
composer test
```

## Troubleshooting

- Ensure outbound HTTPS access to `webservicesp.anaf.ro`.
- Increase `timeout`/`connect_timeout` for slow environments.
- Use `enable_raw` to inspect the underlying ANAF response when debugging mapping issues.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

Please review [SECURITY.md](SECURITY.md) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
