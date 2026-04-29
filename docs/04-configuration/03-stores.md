# Stores

Where the package persists `{ activation_uuid, token, status, last_heartbeat_at }`.

## Choices

| Store | When to use | Trade-off |
|-------|-------------|-----------|
| `file` (default) | Single-server deployments, on-prem appliances | Atomic writes; cannot share state across instances. |
| `database` | Multi-instance deployments behind a load balancer | All instances see the same state; requires the published migration. |

## File store

```php
'store'      => 'file',
'cache_path' => storage_path('app/license.json'),
```

The file is written atomically (`file_put_contents` with `LOCK_EX`) and chmod-ed to `0600`. It contains JSON with the
shape:

```json
{
  "activation_uuid": "01HZ...",
  "token": "eyJ…",
  "status": "active",
  "last_heartbeat_at": "2026-04-29T15:00:00+00:00"
}
```

Add it to the host application's deployment-state backup if losing the file would block traffic. Re-activation is
always possible if the file is lost, but it costs a manual step.

## Database store

```php
'store'           => 'database',
'database_table'  => 'g8key_licenses',
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag="g8key-client-migrations"
php artisan migrate
```

The migration creates a single-row table keyed by `id = 1`. Multi-tenant per-row licensing is not supported in v0.x.

## Switching between stores

Switching does not migrate state. Plan to run `license:activate` on the new store after the switch. The license is
not consumed by re-activation — the same activation UUID is preserved server-side as long as the fingerprint matches.

## Custom stores

Bind your own implementation of `G8Key\Client\Contracts\LicenseStore`:

```php
// app/Providers/AppServiceProvider.php
$this->app->bind(
    \G8Key\Client\Contracts\LicenseStore::class,
    \App\Licensing\RedisLicenseStore::class,
);
```

The contract is small: `read()`, `write()`, `clear()`, `exists()`.

## Next Steps

- [Key Rotation](../05-operations/01-key-rotation.md)
- [Offline Grace](../05-operations/02-offline-grace.md)
