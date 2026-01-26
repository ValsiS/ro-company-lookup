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
    "nume_mfinante": "TERMENE JUST SRL",
    "nume_recom": "TERMENE JUST SRL"
  },
  "forma_juridica": {
    "curenta": { "data_actualizare": "2018-10-12T00:00:00Z", "denumire": "...", "organizare": "SRL" },
    "istoric": []
  },
  "statut_tva": {
    "curent": { "cod": 2, "label": "Platitor TVA", "data_interogare": "2026-01-26" },
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
