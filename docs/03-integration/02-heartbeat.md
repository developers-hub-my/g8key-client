# Heartbeat

Daily refresh of the cached token.

## Purpose

The heartbeat is what keeps the cached token fresh and how the server tells the product about revocation or suspension.
It is not a liveness probe.

## Schedule

`routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('license:heartbeat')->daily()->withoutOverlapping();
```

`withoutOverlapping()` is important — the lock prevents two scheduler runs from racing on the cache file.

## Outcomes

| Server response | Effect |
|-----------------|--------|
| `200 OK` | Cached token replaced with the fresh one. `status = 'active'`. `last_heartbeat_at = now`. |
| `410 Gone` | License has been revoked. `status = 'revoked'`. `License::isValid()` returns false. |
| `423 Locked` | License has been suspended. `status = 'suspended'`. `License::isValid()` returns false. |
| Network error | State untouched. `License::isValid()` continues to return true until `offline_grace_days` elapses. |

## Reading degraded state

```php
use G8Key\Client\Facades\License;

if (! License::isValid()) {
    return view('errors.license-degraded', [
        'status' => License::status(),
    ]);
}
```

`License::status()` returns one of: `active`, `expired`, `revoked`, `suspended`, `offline_grace`, `not_activated`.

## Manual run

```bash
php artisan license:heartbeat
```

Useful in support situations or right after re-pasting a public key during rotation.

## Next Steps

- [Entitlements](03-entitlements.md)
- [Offline Grace](../05-operations/02-offline-grace.md)
