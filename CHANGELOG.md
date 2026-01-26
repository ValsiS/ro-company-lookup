# Changelog

All notable changes to `ro-company-lookup` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

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
