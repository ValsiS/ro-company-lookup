# Usage

## Single lookup

```php
use Valsis\RoCompanyLookup\Facades\RoCompanyLookup;

$company = RoCompanyLookup::lookup('RO123456');
```

The lookup accepts `RO123`, ` ro 123 `, or `123` and normalizes to an integer CUI.

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
