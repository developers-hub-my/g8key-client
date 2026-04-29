# g8key-client — Implementation Plan

> Reference: `g8key-app/docs/06-g8key/06-production-integration.md` is the source of truth.
> This document is the build sheet — phased, file-by-file.

## Goals

Single Composer package consumed by every G8Suite product (G8Stack, G8ID, G8Connect, …) for:

- Offline EdDSA token verification (mirror of server-side `TokenVerifier`)
- Activation, heartbeat, deactivation against `g8key.devhub.my`
- Entitlement checks (`License::has('sso')`, middleware, Blade directive)
- Pluggable license cache (file or DB)
- Multi-kid public key support for zero-downtime key rotation

## Phase 0 — Scaffold (DONE)

- [x] Spatie skeleton configured: vendor `developers-hub-my`, namespace `G8Key\Client`, package `g8key-client`
- [x] composer.json keywords cleaned, autoload PSR-4 set
- [x] Initial classes renamed (`Client.php`, `ClientServiceProvider.php`, `Facades/Client.php`, `Commands/ClientCommand.php`)

## Phase 1 — Composer & runtime requirements

- [ ] Add to `require`:
  - `ext-sodium: *` (EdDSA verify)
  - `ext-curl: *` (HTTP)
  - `ext-json: *`
  - `guzzlehttp/guzzle: ^7.8` (or rely on `illuminate/http`)
  - `ramsey/uuid: ^4.7` (only if not already pulled by Laravel)
- [ ] Bump min PHP if useful (already `^8.4`)
- [ ] Drop `hasViews()` from the service provider — package has no views

## Phase 2 — Config (`config/g8key-client.php`)

Replace empty stub with the canonical shape from the integration doc:

```php
return [
    'audience'           => env('G8KEY_AUDIENCE', 'g8stack'),
    'api_base'           => env('G8KEY_API_BASE', 'https://g8key.devhub.my'),
    'api_timeout'        => env('G8KEY_API_TIMEOUT', 10),
    'public_keys'        => [
        // 'g8stack-2026-04' => env('G8STACK_LICENSE_PUBLIC_KEY_G8STACK_2026_04'),
    ],
    'store'              => env('G8KEY_STORE', 'file'), // file | database
    'cache_path'         => storage_path('app/license.json'),
    'database_table'     => 'g8key_licenses',
    'offline_grace_days' => env('G8KEY_OFFLINE_GRACE_DAYS', 7),
    'fingerprint'        => null, // closure or callable; default → md5(app_url + hostname)
];
```

## Phase 3 — Exceptions (`src/Exceptions/`)

Mirror server-side exception types so error mapping is identical on both sides:

- `G8KeyClientException.php` (base, RuntimeException)
- `MalformedTokenException.php`
- `AlgConfusionException.php`
- `UnknownKidException.php`
- `InvalidSignatureException.php`
- `AudienceMismatchException.php`
- `TokenExpiredException.php`
- `ActivationFailedException.php` (wraps HTTP 4xx/5xx from `/activate`)
- `HeartbeatFailedException.php`
- `LicenseRevokedException.php` (HTTP 410)
- `LicenseSuspendedException.php` (HTTP 423)
- `NotActivatedException.php` (no token in store)

## Phase 4 — Contracts (`src/Contracts/`)

```php
interface LicenseStore {
    public function read(): ?array;            // ['activation_uuid' => …, 'token' => …, 'last_heartbeat_at' => …, 'status' => …]
    public function write(array $data): void;
    public function clear(): void;
    public function exists(): bool;
}

interface TokenVerifier {
    public function verify(string $token): array; // returns decoded payload, throws on failure
}
```

## Phase 5 — Stores (`src/Stores/`)

- `FileLicenseStore.php` — JSON at `cache_path`, atomic writes (`file_put_contents` + `LOCK_EX`), `chmod 0600`
- `DatabaseLicenseStore.php` — single-row table, upsert by `id=1`
- Migration: `database/migrations/create_g8key_licenses_table.php.stub`
  - `id`, `activation_uuid (uuid, unique)`, `token (text)`, `payload_json (json)`, `status`, `last_heartbeat_at`, `timestamps`

Replace the placeholder migration that the skeleton produced.

## Phase 6 — Verifier (`src/Services/Verifier.php`)

Direct copy of `app/Services/G8Key/TokenVerifier.php` from the G8Key server, with one difference: pull
`kid → public_key` from injected array instead of DB.

Order of checks (must not change):

1. Three segments
2. `alg = EdDSA` and `typ = G8K` (header sanity before crypto)
3. `kid` is known
4. Signature length == `SODIUM_CRYPTO_SIGN_BYTES`
5. `sodium_crypto_sign_verify_detached` against `"{$h}.{$p}"`
6. `aud` matches expected audience
7. `nbf <= now` and `exp > now`

## Phase 7 — Network services (`src/Services/`)

- `Activator.php`
  - `activate(string $key, ?string $fingerprint = null): array`
  - POST `{api_base}/api/v1/g8key/activate` → `{activation_uuid, token}`
  - On success: verify token, store via `LicenseStore`
- `Heartbeat.php`
  - `pulse(): array`
  - POST `{api_base}/api/v1/g8key/heartbeat` with cached `activation_uuid`
  - 200 → store fresh token; 410 → throw `LicenseRevokedException`; 423 → throw `LicenseSuspendedException`
  - Network failure → degraded mode (still readable as long as cached token is in `offline_grace_days`)
- `Deactivator.php`
  - `deactivate(): void` — POST `/deactivate`, then `store->clear()`

## Phase 8 — LicenseManager (`src/LicenseManager.php`)

The binding behind the facade. Lazily verifies the cached token once per request (memoized).

```php
public function payload(): ?array;
public function tier(): ?string;
public function seats(): ?int;
public function features(): array;
public function has(string $feature): bool;
public function expiresAt(): ?\Carbon\CarbonImmutable;
public function activationUuid(): ?string;
public function status(): string;          // 'active' | 'expired' | 'revoked' | 'suspended' | 'offline_grace' | 'not_activated'
public function isValid(): bool;
public function isInOfflineGrace(): bool;
```

`isValid()` returns false when:

- Store is empty
- Token expired AND beyond `offline_grace_days` since last heartbeat
- Cached status is `revoked` or `suspended`

## Phase 9 — Facade (`src/Facades/License.php`)

Rename `src/Facades/Client.php` → `src/Facades/License.php`:

```php
class License extends Facade {
    protected static function getFacadeAccessor(): string {
        return LicenseManager::class;
    }
}
```

Update `composer.json` aliases:

```json
"aliases": { "License": "G8Key\\Client\\Facades\\License" }
```

Decide what to do with `src/Client.php` — delete (no purpose) or repurpose as a thin convenience wrapper.
Recommendation: **delete**.

## Phase 10 — Service provider (`src/G8KeyClientServiceProvider.php`)

Rename from `ClientServiceProvider`. Wire:

- `singleton(TokenVerifier::class)` → `Verifier` constructed with `config('g8key-client.public_keys')` + `config('g8key-client.audience')`
- `singleton(LicenseStore::class)` → `FileLicenseStore` or `DatabaseLicenseStore` based on `config('g8key-client.store')`
- `singleton(LicenseManager::class)` → resolves with verifier + store
- `singleton(Activator/Heartbeat/Deactivator::class)`
- Register console commands
- `Router::aliasMiddleware('license', RequireLicense::class)`
- `Router::aliasMiddleware('license.feature', RequireFeature::class)`
- `Blade::if('licenseFeature', fn ($f) => app(LicenseManager::class)->has($f))`
- `publishes` config + migration with tags `g8key-client-config`, `g8key-client-migrations`

## Phase 11 — Console commands (`src/Console/`)

| Command | Signature | Purpose |
| --- | --- | --- |
| `ActivateCommand` | `license:activate {key}` | One-shot activation; persists token |
| `HeartbeatCommand` | `license:heartbeat` | Daily refresh; flips degraded mode on 410/423 |
| `DeactivateCommand` | `license:deactivate` | Releases activation; clears store |
| `StatusCommand` | `license:status` | Pretty-prints cached payload + remaining grace |

`HeartbeatCommand` should be safe under `withoutOverlapping()`.

## Phase 12 — HTTP middleware (`src/Http/Middleware/`)

- `RequireLicense` — `abort(403)` if `LicenseManager::isValid()` is false
- `RequireFeature` — takes feature name parameter; checks `has($feature)`

## Phase 13 — Tests (`tests/`)

Tear out the placeholder `ExampleTest`. Build:

- `Unit/VerifierTest.php` — happy path + each failure mode (alg, typ, kid, sig length, sig mismatch, aud, nbf, exp).
  Use a real keypair generated in test setup.
- `Unit/FileLicenseStoreTest.php` — atomic write, perms, missing file
- `Unit/LicenseManagerTest.php` — entitlement methods against fake payload
- `Feature/ActivateCommandTest.php` — `Http::fake()` for activate endpoint
- `Feature/HeartbeatCommandTest.php` — happy + 410 + 423 + network failure
- `Feature/MiddlewareTest.php` — 403 paths
- Expand `ArchTest` to enforce: no debugging functions, no `Illuminate\Http` Facade leak from inside services, etc.

## Phase 14 — Docs polish

- Replace `README.md` skeleton text with a real intro + install + activate + facade examples + key rotation runbook
- Add `CHANGELOG.md` entry for `0.1.0`
- Cross-link to `g8key-app/docs/06-production-integration.md` for the operator-side flow

## File checklist (post-build)

```text
g8key-client/
├── composer.json                              (Phase 1)
├── config/
│   └── g8key-client.php                       (Phase 2)
├── database/
│   └── migrations/
│       └── create_g8key_licenses_table.php.stub  (Phase 5)
├── src/
│   ├── G8KeyClientServiceProvider.php         (Phase 10, renamed)
│   ├── LicenseManager.php                     (Phase 8)
│   ├── Contracts/
│   │   ├── LicenseStore.php                   (Phase 4)
│   │   └── TokenVerifier.php                  (Phase 4)
│   ├── Exceptions/                            (Phase 3 — 12 files)
│   ├── Stores/
│   │   ├── FileLicenseStore.php               (Phase 5)
│   │   └── DatabaseLicenseStore.php           (Phase 5)
│   ├── Services/
│   │   ├── Verifier.php                       (Phase 6)
│   │   ├── Activator.php                      (Phase 7)
│   │   ├── Heartbeat.php                      (Phase 7)
│   │   └── Deactivator.php                    (Phase 7)
│   ├── Facades/
│   │   └── License.php                        (Phase 9, renamed)
│   ├── Console/
│   │   ├── ActivateCommand.php                (Phase 11)
│   │   ├── HeartbeatCommand.php               (Phase 11)
│   │   ├── DeactivateCommand.php              (Phase 11)
│   │   └── StatusCommand.php                  (Phase 11)
│   └── Http/
│       └── Middleware/
│           ├── RequireLicense.php             (Phase 12)
│           └── RequireFeature.php             (Phase 12)
└── tests/                                     (Phase 13)
```

Files to delete after rename:

- `src/Client.php` (replaced by `LicenseManager.php`)
- `src/Commands/ClientCommand.php` (replaced by four real commands; remove the `Commands/` dir if empty)
- `src/Facades/Client.php` (replaced by `Facades/License.php`)
- `src/ClientServiceProvider.php` (replaced by `G8KeyClientServiceProvider.php`)
- `database/migrations/create_g8key_client_table.php.stub` (replaced by `create_g8key_licenses_table.php.stub`)

## Order of execution (build script)

1. Phases 1 → 2 → 3 (foundation, contracts can be touched before services exist)
2. Phase 4 (contracts)
3. Phase 5 + 6 in parallel (stores and verifier are independent)
4. Phase 7 (network — depends on store + verifier)
5. Phase 8 (manager — depends on store + verifier)
6. Phase 9 + 10 + 11 + 12 (facade, provider, commands, middleware — manager-dependent but parallel to each other)
7. Phase 13 (tests — touch each phase as it lands; final round at the end)
8. Phase 14 (docs)

A tight v0.1.0 covers Phases 1–11 + happy-path tests. Middleware + Blade can land in v0.2.0 if cutting scope.

## Open questions before coding

1. **Fingerprint policy** — what is the canonical fingerprint formula the server expects?
   (`hash('sha256', app.url + hostname + machine_id)`?) Confirm against the server-side `Activator` controller.
2. **Heartbeat cadence vs. grace** — doc says daily heartbeat + 7-day offline grace; confirm token TTL on the server.
   If TTL > 24h, daily heartbeat is overkill; if TTL < 24h, daily is too slow.
3. **Migration table or single-row JSON?** — `DatabaseLicenseStore` design assumes a single row. If multi-tenant
   per-process licensing is ever needed, this changes.
4. **Should the package ship a `License` route group helper** (`Route::license()->group(...)`) or stick with the
   middleware alias? Middleware is simpler; helper is more Laravel-idiomatic.

Lock these answers before Phase 7.
