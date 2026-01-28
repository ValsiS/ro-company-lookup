# Usage

## Single lookup

```php
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;

$company = RoCompanyLookup::lookup('RO123456');
```

The lookup accepts `RO123`, ` ro 123 `, or `123` and normalizes to an integer CUI. After normalization, the CUI must be between 2 and 10 digits.

## Lookup with explicit date

```php
$company = RoCompanyLookup::lookup('123456', new DateTimeImmutable('2024-01-10'));
```

If no date is provided, the package uses "today" in the configured timezone.

## Language (output key naming)

```php
// config/ro-company-lookup.php
'language' => 'ro', // or 'en'
```

Romanian output follows ANAF field naming (`adresa`, `cod_caen`, `date_contact`, `forma_juridica`, `statut_tva`).
English output provides professional translations with matching structure (e.g., `address`, `caen_code`, `contact`, `legal_form`, `vat_status`).

## Raw payload

```php
// config/ro-company-lookup.php
'enable_raw' => true,
```

This adds `meta.raw` with the original ANAF payload.

## Errors and exceptions

The package throws typed exceptions:

- `InvalidCuiException` for invalid input
- `LookupFailedException` for upstream errors
- `CircuitOpenException` when the circuit breaker is open

## Soft lookups (no exceptions)

Use `tryLookup()` to return a typed result instead of throwing:

```php
$result = RoCompanyLookup::tryLookup('RO123456');

if ($result->status === 'ok') {
    $company = $result->data;
}
```

## Standard summary payload

The summary helpers return a consistent payload shape that always includes validation and status metadata:

```json
{
  "exists": true,
  "valid": true,
  "status": "ok",
  "message": null,
  "error": null,
  "code": null,
  "cui": 12345678,
  "name": "EXEMPLU SRL",
  "caen": "6201",
  "registration_date": "01.01.2020",
  "vat_payer": false
}
```

`status` is one of `ok`, `not_found`, `invalid`, `error`. For invalid input, `error` can be `invalid_cui`, `invalid_cui_too_short`, or `invalid_cui_too_long`.
`exists` is true only when `status` is `ok`, while `valid` reflects whether the CUI input passed validation.
