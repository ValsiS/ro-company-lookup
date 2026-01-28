# DTOs and output shape

The package returns `CompanySimpleData` (spatie/laravel-data). Output keys are mapped based on the `language` config.

## Root structure (RO)

```json
{
  "adresa": {
    "domiciliu_fiscal": { "formatat": "Str. Exemplu, Nr. 10, Mun. Test, Judet IL" },
    "sediu_social": { "formatat": "Str. Exemplu, Nr. 10, Mun. Test, Judet IL" }
  },
  "cod_caen": { "cod": "6201", "label": null, "versiune": null },
  "date_contact": {
    "telefon": [],
    "email": []
  },
  "firma": {
    "cui": 12345678,
    "nr_reg_com": "J2018000000001",
    "denumire": "EXEMPLU SRL",
    "profil": {
      "data_inregistrare": "01.01.2020",
      "stare_inregistrare": "INREGISTRAT din data 01.01.2020",
      "organ_fiscal": "Administratia Judeteana a Finantelor Publice Test",
      "forma_de_proprietate": "PROPR.PRIVATA-CAPITAL PRIVAT AUTOHTON",
      "status_ro_e_factura": false,
      "data_inreg_ro_e_factura": null,
      "iban": null
    }
  },
  "forma_juridica": {
    "curenta": { "data_actualizare": "2026-02-01T10:00:00Z", "denumire": "SOCIETATE COMERCIALĂ CU RĂSPUNDERE LIMITATĂ", "organizare": "SRL" },
    "istoric": []
  },
  "tva_incasare": {
    "activ": false,
    "data_inceput": null,
    "data_sfarsit": null,
    "data_publicare": null,
    "data_actualizare": null,
    "tip_act": null
  },
  "stare_inactiv": {
    "este_inactiv": false,
    "data_inactivare": null,
    "data_reactivare": null,
    "data_publicare": null,
    "data_radiere": null
  },
  "split_tva": {
    "activ": false,
    "data_inceput": null,
    "data_anulare": null
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

For invalid input, `error` can be:

- `invalid_cui`
- `invalid_cui_too_short`
- `invalid_cui_too_long`

The summary helpers (`summary`, `summarySafe`, `summaryOrResult`, and batch equivalents) return a standardized payload:

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

`exists` is true only when `status` is `ok`. `valid` indicates whether the CUI input passed validation.

```json
{
  "address": {
    "fiscal_domicile": { "formatted_address": "Str. Exemplu, Nr. 10, Mun. Test, Judet IL" },
    "registered_office": { "formatted_address": "Str. Exemplu, Nr. 10, Mun. Test, Judet IL" }
  },
  "caen_code": { "code": "6201", "label": null, "version": null },
  "contact": {
    "phone_numbers": [],
    "emails": []
  },
  "company": {
    "cui": 12345678,
    "registration_number": "J2018000000001",
    "name": "EXEMPLU SRL",
    "profile": {
      "registration_date": "2020-01-01",
      "registration_status": "INREGISTRAT din data 01.01.2020",
      "fiscal_office": "Administratia Judeteana a Finantelor Publice Test",
      "ownership_form": "PROPR.PRIVATA-CAPITAL PRIVAT AUTOHTON",
      "e_invoice_status": false,
      "e_invoice_registration_date": null,
      "iban": null
    }
  },
  "legal_form": {
    "current": { "updated_at": "2026-02-01T10:00:00Z", "name": "SOCIETATE COMERCIALĂ CU RĂSPUNDERE LIMITATĂ", "organization": "SRL" },
    "history": []
  },
  "vat_collection": {
    "enabled": false,
    "start_date": null,
    "end_date": null,
    "published_at": null,
    "updated_at": null,
    "act_type": null
  },
  "inactive_status": {
    "is_inactive": false,
    "inactivated_at": null,
    "reactivated_at": null,
    "published_at": null,
    "removed_at": null
  },
  "split_vat": {
    "enabled": false,
    "start_date": null,
    "cancelled_at": null
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
