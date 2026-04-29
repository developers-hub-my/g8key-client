# Quick Start

Activate a license, schedule the daily heartbeat, and gate a feature — end to end.

## Prerequisites

- Package installed (see [Installation](01-installation.md))
- A license key from the G8Key admin (e.g. `G8ST-K7HM-3PXR-9F2B-WQ8N`)
- Public key for your product copied into `.env`

## 1. Activate

```bash
php artisan license:activate G8ST-K7HM-3PXR-9F2B-WQ8N
```

What happens:

1. The command POSTs to `{api_base}/api/v1/g8key/activate` with the key plus a machine fingerprint.
2. The server returns `{ activation_uuid, token }`.
3. The package verifies the token offline against the configured public key.
4. Verified token + activation UUID are persisted to the configured store.

Output on success:

```text
License activated.
  Tier:    pro
  Seats:   5
  Expires: 2027-04-29 15:00:00
```

## 2. Schedule the heartbeat

In `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('license:heartbeat')->daily()->withoutOverlapping();
```

The heartbeat refreshes the cached token. When the server returns `410` (revoked) or `423` (suspended), the package
flips into a degraded state that `License::isValid()` reflects.

## 3. Gate a feature

Use the facade in code:

```php
use G8Key\Client\Facades\License;

if (License::has('sso')) {
    // SSO is on this license tier
}
```

Or middleware in routes:

```php
Route::middleware('license')->group(function () {
    // any route here requires a valid license
});

Route::middleware('license.feature:audit_log')->group(function () {
    // requires the audit_log feature flag in the token
});
```

Or Blade:

```blade
@licenseFeature('priority_support')
    <a href="/support/priority">Priority support</a>
@endlicenseFeature
```

## 4. Inspect the cached license

```bash
php artisan license:status
```

Prints the decoded payload, current status, and remaining offline grace.

## Next Steps

- [Configuration Reference](03-configuration.md) — every field explained
- [Integration](../03-integration/README.md) — deeper wiring patterns
- [Key Rotation](../05-operations/01-key-rotation.md) — what to do when the issuer rotates keys
