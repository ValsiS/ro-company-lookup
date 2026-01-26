# DTOs and output shape

The package returns `CompanySimpleData` (spatie/laravel-data). Output keys are mapped based on the `language` config.

## Root structure (RO)

```json
{
  "adresa": {
    "anaf": { "formatat": "..." },
    "sediu_social": { "formatat": "..." }
  },
  "cod_caen": {
    "principal_mfinante": { "cod": "6311", "label": "..." },
    "principal_recom": { "cod": "6310", "label": "...", "versiune": 3 }
  },
  "date_contact": {
    "telefon": ["0344..."],
    "email": ["contact@example.com"]
  },
  "firma": {
    "cui": 33034700,
    "j": "J2014000546297",
    "nume_mfinante": "ACME SRL",
    "nume_recom": "ACME SRL"
  },
  "forma_juridica": {
    "curenta": { "data_actualizare": "2018-10-12T00:00:00Z", "denumire": "...", "organizare": "SRL" },
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

## English mapping

When `language` is set to `en`, the same structure is returned with translated keys.

## JSON Schemas

Versioned JSON Schemas are available in:

- `docs/schemas/company-simple.ro.v1.json`
- `docs/schemas/company-simple.en.v1.json`

## LookupResultData

`tryLookup()` returns a `LookupResultData` object with:

- `status`: `ok`, `not_found`, `invalid`, or `error`
- `data`: `CompanySimpleData` when `status` is `ok` or `not_found`
- `error`, `message`, `error_code` when `status` is `invalid` or `error`

```json
{
  "address": {
    "anaf": { "formatted_address": "..." },
    "registered_office": { "formatted_address": "..." }
  },
  "caen_code": {
    "primary_ministry_of_finance": { "code": "6311", "label": "..." },
    "primary_recom": { "code": "6310", "label": "...", "version": 3 }
  },
  "contact_details": {
    "phone_numbers": ["0344..."],
    "emails": ["contact@example.com"]
  },
  "company": {
    "cui": 33034700,
    "trade_register_number": "J2014000546297",
    "ministry_of_finance_name": "ACME SRL",
    "recom_name": "ACME SRL"
  },
  "legal_form": {
    "current": { "updated_at": "2018-10-12T00:00:00Z", "name": "...", "organization": "SRL" },
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
