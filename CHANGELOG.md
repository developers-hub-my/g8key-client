# Changelog

All notable changes to `g8key-client` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/developers-hub-my/g8key-client/compare/v0.2.0...HEAD)

## [0.2.0](https://github.com/developers-hub-my/g8key-client/compare/v0.1.0...v0.2.0) — 2026-05-02

### Changed

- Default `api_base` is now `https://lic.g8suite.com` (was `https://g8key.devhub.my`). Anyone relying on the default
  must update or set `G8KEY_API_BASE` explicitly.
- `composer.json` now requires `orchestra/testbench: ^10.0||^11.0` (was `^9.0.0||^10.0.0`). Covers Laravel 12 via
  testbench 10.x and Laravel 13 via testbench 11.x.

### Fixed

- CI matrix: dropped PHP 8.3, which the package never supported (composer.json requires `^8.4`); the v0.1.0 push
  consequently shipped with a failing `run-tests` check on GitHub even though the suite was green locally.
- CI matrix: added `sodium` to the `setup-php` extensions list. Without it `Verifier` would have aborted on first
  `sodium_crypto_sign_verify_detached` call in CI.
- CI matrix: slimmed the extensions list to what the package actually uses
  (`curl, json, sodium, mbstring, dom, libxml, zip, pdo, sqlite, pdo_sqlite, bcmath, intl, fileinfo`); removed
  `imagick`, `gd`, `exif`, `soap`, `pcntl`, `iconv`.

### Notes

- v0.1.0 published a working package; this release is primarily a defaults change and a CI repair. Behaviour against
  custom-configured `api_base` deployments is unchanged.

## [0.1.0](https://github.com/developers-hub-my/g8key-client/releases/tag/v0.1.0) — 2026-05-02

Initial release. Companion client for [G8Key](https://github.com/developers-hub-my/g8key-app); every G8Suite product
installs this package to verify offline EdDSA tokens, run activation and heartbeat against the G8Key server, and gate
features through a `License` facade and route middleware.

### Added

- `License` facade exposing `payload()`, `tier()`, `seats()`, `seatsRemaining()`, `features()`, `has()`,
  `expiresAt()`, `customer()`, `graceDays()`, `fingerprint()`, `activationUuid()`, `status()`, `isValid()`,
  `isInOfflineGrace()`, `flush()`.
- Console commands: `license:activate {key}`, `license:heartbeat`, `license:deactivate`, `license:status`.
- Route middleware aliases `license` (any valid license) and `license.feature:{name}` (gated by feature flag).
- Blade directive `@licenseFeature ... @else ... @endlicenseFeature`.
- `LicenseStore` contract with two implementations:
  - `FileLicenseStore` (default): JSON at `cache_path` with `LOCK_EX` atomic writes and `chmod 0600`.
  - `DatabaseLicenseStore`: single-row upsert against the publishable `g8key_licenses` migration.
- Offline EdDSA `Verifier` mirroring the server-side verification order exactly: split → `alg`/`typ`/`kid` →
  signature length guard → sodium verify → `aud`/`nbf`/`exp`.
- HTTP services: `Activator`, `Heartbeat`, `Deactivator`. Activate POSTs `license_key` (and optional `instance_label`,
  `hostname`, `product_version`) to `/api/v1/g8key/activate`. Heartbeat and Deactivate authenticate with
  `Authorization: Bearer <activation_uuid>` per the server's `AuthenticateActivation` middleware. Heartbeat preserves
  cache state on network failure and flips status on `410` (revoked) / `423` (suspended).
- `Fingerprint::compute()` matches the server's `App\Services\G8Key\FingerprintGenerator` byte-for-byte:
  `sha256:` + sha256-hex over a sorted, lowercased, trimmed JSON of input attributes.
- Multi-`kid` public-key map for zero-downtime key rotation.
- 12 typed exceptions under `G8Key\Client\Exceptions` matching the server's exception types.
- `offline_grace_days` config (env: `G8KEY_OFFLINE_GRACE_DAYS`, default `7`).
- Pest 4 test suite: 54 tests, 106 assertions covering verifier failure modes, store atomicity, manager state
  machine, all four console commands and both middleware against `Http::fake()`.
- Architecture rules: no debugging functions, no HTTP deps in `Verifier`, exceptions extend the package base,
  contracts are interfaces, console commands extend `Illuminate\Console\Command`.
- Documentation under `docs/` (29 files) covering getting-started, architecture, integration, configuration,
  operations, development plan, and decisions (ADR-0001, ADR-0002).

### Notes

- Requires PHP `^8.4`, Laravel `^11.0 || ^12.0 || ^13.0`, `ext-sodium`, `ext-curl`, `ext-json`.
- `License::seatsRemaining()` returns `null` until the server emits a `seats_used` claim. Forward-compatible per
  ADR-0002.
- Daily heartbeat is the cadence floor — it relies on the server's default 24h token TTL. Lower the heartbeat
  cadence if `g8key.token_ttl_hours` is set below 24 on the server.
