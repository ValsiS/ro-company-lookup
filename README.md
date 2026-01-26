# Ro Company Lookup

[![Tests](https://img.shields.io/github/actions/workflow/status/ValsiS/ro-company-lookup/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ValsiS/ro-company-lookup/actions/workflows/run-tests.yml)
[![Validate](https://img.shields.io/github/actions/workflow/status/ValsiS/ro-company-lookup/run-tests.yml?branch=main&label=validate&style=flat-square)](https://github.com/ValsiS/ro-company-lookup/actions/workflows/run-tests.yml)
[![Latest Version](https://img.shields.io/github/v/release/ValsiS/ro-company-lookup.svg?style=flat-square)](https://github.com/ValsiS/ro-company-lookup/releases)
[![PHP Version](https://img.shields.io/badge/php-8.3%2B-8892BF.svg?style=flat-square)](https://www.php.net/)
[![Total Downloads](https://img.shields.io/github/downloads/ValsiS/ro-company-lookup/total.svg?style=flat-square)](https://github.com/ValsiS/ro-company-lookup/releases)
[![License](https://img.shields.io/github/license/ValsiS/ro-company-lookup.svg?style=flat-square)](LICENSE)

A production-ready Laravel package that retrieves basic Romanian company data by CUI from ANAF public web services.
Current version: `v0.1.1`.

## Requirements

- PHP 8.3+
- Laravel 12+

## Compatibility

| PHP | Laravel | CI |
| --- | --- | --- |
| 8.3 | 12.x | [![PHP 8.3](https://img.shields.io/github/actions/workflow/status/ValsiS/ro-company-lookup/run-tests.yml?branch=main&label=php%208.3&style=flat-square)](https://github.com/ValsiS/ro-company-lookup/actions/workflows/run-tests.yml) |
| 8.5 | 12.x | [![PHP 8.5](https://img.shields.io/github/actions/workflow/status/ValsiS/ro-company-lookup/run-tests.yml?branch=main&label=php%208.5&style=flat-square)](https://github.com/ValsiS/ro-company-lookup/actions/workflows/run-tests.yml) |

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
- `cache_version` to force a cache bust across all keys
- `batch_max_size` and `batch_chunk_size` for batching behavior
- `logging` for PSR-3 request/response metadata logging
- `circuit_breaker` for cooldown after repeated 5xx responses

## Usage

```php
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;

$company = RoCompanyLookup::lookup('RO123456');

$companyWithDate = RoCompanyLookup::lookup('123456', new DateTimeImmutable('2024-01-10'));
```

The lookup accepts `RO123`, ` ro 123 `, or `123` and normalizes to an integer CUI. By default, the query date is "today" in `Europe/Bucharest`.

If you prefer a non-throwing API, use `tryLookup()`:

```php
$result = RoCompanyLookup::tryLookup('RO123456');

if ($result->status === 'ok') {
    $company = $result->data;
}
```

## Documentation

Full documentation is available in the repo wiki pages under `docs/wiki`. Start with:

- `docs/wiki/Home.md`

JSON Schema definitions (versioned) are available under `docs/schemas`.

### Field naming & structure

Output follows the official ANAF field naming conventions (e.g., `firma`, `adresa`, `cod_caen`, `date_contact`, `forma_juridica`, `statut_tva`). Use the `language` config to switch between `ro` and `en` output keys. Internally you can still access properties using the original PHP property names.

Example output (RO):

```json
{
  "firma": {
    "cui": 33034700,
    "j": "J2014000546297",
    "nume_mfinante": "ACME SRL",
    "nume_recom": "ACME SRL"
  },
  "adresa": {
    "anaf": {
      "formatat": "Strada Nicolae Titulescu, Nr. 32, Ploiești, Județ Prahova",
      "judet": "Prahova",
      "localitate": "Ploiești"
    },
    "sediu_social": {
      "formatat": "Strada Nicolae Titulescu, Nr. 32, Ploiești, Județ Prahova",
      "judet": "Prahova",
      "localitate": "Ploiești"
    }
  },
  "cod_caen": {
    "principal_mfinante": { "cod": "6311", "label": "Prelucrarea datelor..." },
    "principal_recom": { "cod": "6310", "label": "Prelucrarea datelor...", "versiune": 3 }
  },
  "date_contact": {
    "telefon": ["0344803100"],
    "email": []
  },
  "forma_juridica": {
    "curenta": { "data_actualizare": "2018-10-12T00:00:00Z", "denumire": "Societate cu Răspundere Limitată", "organizare": "SRL" },
    "istoric": []
  },
  "statut_tva": {
    "curent": { "cod": 2, "label": "Plătitor TVA", "data_interogare": "2026-01-26" },
    "istoric": []
  },
  "meta": {
    "sursa": "anaf",
    "data_interogare": "2026-01-26T10:00:00Z",
    "data_ceruta": "2026-01-26",
    "este_stale": false,
    "cache_hit": true
  }
}
```

Example output (EN):

```json
{
  "company": {
    "cui": 33034700,
    "trade_register_number": "J2014000546297",
    "ministry_of_finance_name": "ACME SRL",
    "recom_name": "ACME SRL"
  },
  "address": {
    "anaf": {
      "formatted_address": "Strada Nicolae Titulescu, Nr. 32, Ploiești, Județ Prahova",
      "county": "Prahova",
      "city": "Ploiești"
    },
    "registered_office": {
      "formatted_address": "Strada Nicolae Titulescu, Nr. 32, Ploiești, Județ Prahova",
      "county": "Prahova",
      "city": "Ploiești"
    }
  },
  "caen_code": {
    "primary_ministry_of_finance": { "code": "6311", "label": "Data processing..." },
    "primary_recom": { "code": "6310", "label": "Data processing...", "version": 3 }
  },
  "contact_details": {
    "phone_numbers": ["0344803100"],
    "emails": []
  },
  "legal_form": {
    "current": { "updated_at": "2018-10-12T00:00:00Z", "name": "Limited Liability Company", "organization": "SRL" },
    "history": []
  },
  "vat_status": {
    "current": { "code": 2, "label": "Plătitor TVA", "queried_at": "2026-01-26" },
    "history": []
  },
  "meta": {
    "source": "anaf",
    "queried_at": "2026-01-26T10:00:00Z",
    "queried_for_date": "2026-01-26",
    "is_stale": false,
    "cache_hit": true
  }
}
```

Example batch response (RO, array of items):

```json
[
  { "firma": { "cui": 123456 }, "meta": { "sursa": "anaf" } },
  { "firma": { "cui": 789012 }, "meta": { "sursa": "anaf" } }
]
```

### Batch lookup

```php
$companies = RoCompanyLookup::batch(['RO123', 'RO456'])->get();
```

ANAF supports up to 100 CUIs per request. The package enforces this limit.

Soft batch lookup (no exceptions):

```php
$results = RoCompanyLookup::batch(['RO123', 'BAD', 'RO456'])->tryGet();
```

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
- Bump `cache_version` to invalidate existing cache entries after a mapping change.
- Retries use exponential backoff and only trigger on 429 and 5xx responses.
- Circuit breaker (optional) opens after repeated 5xx responses and cools down for a configurable interval.

## Response errors

The package throws typed exceptions for error scenarios:

- `InvalidCuiException` when the input cannot be normalized to a valid CUI.
- `LookupFailedException` for upstream failures (timeouts, 429/5xx, invalid responses).
- `CircuitOpenException` when the circuit breaker is open.

Example (single lookup):

```php
try {
    $company = RoCompanyLookup::lookup('RO123456');
} catch (\Valsis\RoCompanyLookup\Exceptions\InvalidCuiException $e) {
    // Handle invalid input
} catch (\Valsis\RoCompanyLookup\Exceptions\CircuitOpenException $e) {
    // Service temporarily unavailable
} catch (\Valsis\RoCompanyLookup\Exceptions\LookupFailedException $e) {
    // Upstream failure
}
```

For user-facing flows, consider `tryLookup()` which returns a `LookupResultData` with `status` values of `ok`, `not_found`, `invalid`, or `error`.

## Artisan command

```bash
php artisan ro-company-lookup:check 123456 --date=2024-01-10 --raw
```

The command outputs JSON to stdout.

## Testing

```bash
composer test
```

Other useful scripts:

```bash
composer lint
composer analyse
composer ci
```

## Troubleshooting

- Ensure outbound HTTPS access to `webservicesp.anaf.ro`.
- Increase `timeout`/`connect_timeout` for slow environments.
- Use `enable_raw` to inspect the underlying ANAF response when debugging mapping issues.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.
We use Dependabot for dependency updates.

## Code of Conduct

Please see [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) for details.

## Security

Please review [SECURITY.md](SECURITY.md) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
