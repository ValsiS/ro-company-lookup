# DTOs and output shape

The package returns `CompanySimpleData` (spatie/laravel-data). Output keys are mapped based on the `language` config.

## Root structure (RO)

```json
{
  "adresa": {
    "anaf": { "formatat": "Str. Exemplu, Nr. 10, Mun. Test, Judet IL" },
    "sediu_social": { "formatat": "Str. Exemplu, Nr. 10, Mun. Test, Judet IL" }
  },
  "cod_caen": {
    "principal_mfinante": { "cod": "6201", "label": null },
    "principal_recom": { "cod": "6201", "label": null, "versiune": null }
  },
  "date_contact": {
    "telefon": [],
    "email": []
  },
  "firma": {
    "cui": 12345678,
    "j": "J2018000000001",
    "nume_mfinante": "EXEMPLU SRL",
    "nume_recom": "EXEMPLU SRL"
  },
  "forma_juridica": {
    "curenta": { "data_actualizare": "2026-02-01T10:00:00Z", "denumire": "SOCIETATE COMERCIALĂ CU RĂSPUNDERE LIMITATĂ", "organizare": "SRL" },
    "istoric": []
  },
  "statut_tva": {
    "curent": { "cod": 1, "label": "Neplătitor TVA", "data_interogare": "2026-02-01" },
    "istoric": []
  },
  "meta": {
    "sursa": "anaf",
    "data_interogare": "2026-02-01T10:00:00Z",
    "data_ceruta": "2026-02-01",
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
    "anaf": { "formatted_address": "Str. Exemplu, Nr. 10, Mun. Test, Judet IL" },
    "registered_office": { "formatted_address": "Str. Exemplu, Nr. 10, Mun. Test, Judet IL" }
  },
  "caen_code": {
    "primary_ministry_of_finance": { "code": "6201", "label": null },
    "primary_recom": { "code": "6201", "label": null, "version": null }
  },
  "contact_details": {
    "phone_numbers": [],
    "emails": []
  },
  "company": {
    "cui": 12345678,
    "trade_register_number": "J2018000000001",
    "ministry_of_finance_name": "EXEMPLU SRL",
    "recom_name": "EXEMPLU SRL"
  },
  "legal_form": {
    "current": { "updated_at": "2026-02-01T10:00:00Z", "name": "SOCIETATE COMERCIALĂ CU RĂSPUNDERE LIMITATĂ", "organization": "SRL" },
    "history": []
  },
  "vat_status": {
    "current": { "code": 1, "label": "Neplătitor TVA", "queried_at": "2026-02-01" },
    "history": []
  },
  "meta": {
    "source": "anaf",
    "queried_at": "2026-02-01T10:00:00Z",
    "queried_for_date": "2026-02-01",
    "is_stale": false,
    "cache_hit": true
  }
}
```
