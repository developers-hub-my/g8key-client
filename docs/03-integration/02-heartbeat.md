# Heartbeat

Daily refresh of the cached token.

## Purpose

The heartbeat is what keeps the cached token fresh and how the server tells the product about revocation or suspension.
It is not a liveness probe.

## Wire format

Request — `POST {api_base}/api/v1/g8key/heartbeat`:

```http
Authorization: Bearer 01HZ...     ← the activation_uuid from /activate
Content-Type: application/json

{ "product_version": "1.0.1" }   ← optional
```

Auth is the activation UUID as a bearer token. The body is empty unless reporting a `product_version` change.

Response — `200 OK`:

```json
{ "token": "eyJh...", "expires_in": 86400 }
```

`410 Gone` and `423 Locked` indicate revocation and suspension respectively.

## Schedule

`routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('license:heartbeat')->daily()->withoutOverlapping();
```

`withoutOverlapping()` prevents two scheduler runs from racing on the cache file.

The default server-side token TTL is 24 hours, so daily is the cadence floor — every successful heartbeat issues a
fresh 24-hour token. If your server uses a shorter TTL, raise the cadence to match.

## Outcomes

| Server response | Effect |
|-----------------|--------|
| `200 OK` | Cached token replaced with the fresh one. `status = 'active'`. `last_heartbeat_at = now`. |
| `410 Gone` | License revoked. `status = 'revoked'`. `License::isValid()` returns false. |
| `423 Locked` | License suspended. `status = 'suspended'`. `License::isValid()` returns false. |
| Network error | State untouched. `License::isValid()` continues true until `offline_grace_days` elapses. |

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
