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

Romanian output follows the Termene.ro structure (`adresa`, `cod_caen`, `date_contact`, `forma_juridica`, `statut_tva`).
English output provides professional translations while preserving the same structure.

## Raw payload

```php
// config/ro-company-lookup.php
'enable_raw' => true,
```

This adds `meta.raw` with the original ANAF payload.
