# Changelog

All notable changes to `ro-company-lookup` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [0.2.0] - 2026-01-27

- Added company profile data derived from ANAF general information.
- Added contract test for v9 payload profile mapping.
- Added compact demo command and convenience helpers (VAT payer, registration date).
- Added schema audit snapshots for unknown payload keys.
- Added summary helpers and batch summary utilities.
- Added summary helpers with status and CUI validation utility.
- Added safe summary, keyed batch summaries, and optional per-CUI throttle guard.
- Added configurable output date format for summaries.
- Added global date formatting with per-language defaults and per-request overrides.
- Added VAT collection, inactive status, and split VAT mapping.

## [0.1.9] - 2026-01-27

- Added schema audit for ANAF payloads with optional logging and fail-fast mode.
- Added contract test to ensure fixtures match known schema keys.

## [0.1.8] - 2026-01-27

- Added derived address formatting from structured components when formatted value is missing.

## [0.1.7] - 2026-01-27

- Improved VAT period mapping and current status selection from ANAF payloads.
- Added fax number to contact phones when provided.

## [0.1.6] - 2026-01-27

- Improved VAT mapping to select the correct current period and include query timestamps.

## [0.1.5] - 2026-01-27

- Fixed ANAF v9 entry matching by CUI when data is nested under `date_generale`.

## [0.1.4] - 2026-01-26

- Added cache versioning to allow safe cache busts after mapping changes.

## [0.1.3] - 2026-01-26

- Fixed ANAF v9 response mapping for `date_generale`, VAT scopes, and address blocks.

## [0.1.2] - 2026-01-26

- Added soft lookup API (`tryLookup`) and soft batch results (`tryGet`) with `LookupResultData`.
- Updated ANAF endpoint to v9.
- Added circuit breaker, logging hooks, and JSON schema docs.
- Added contract tests and expanded fixtures.
- CI improvements: coverage gate, composer validation, run-tests summary job, and ruleset updates.
- Documentation updates and example responses.

## [0.1.0] - 2026-01-26

- Initial release: ANAF driver, DTOs, caching, retries, batching, console command, and test suite.
