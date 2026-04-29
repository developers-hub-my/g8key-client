# Deactivation

Release an activation back to the server.

## Command

```bash
php artisan license:deactivate
```

## What it does

1. Reads the cached `activation_uuid`.
2. POSTs to `{api_base}/api/v1/g8key/deactivate` with the UUID and fingerprint.
3. On success, clears the local store. The host can no longer read entitlements until re-activated.

## When to use it

| Situation | Use deactivate? |
|-----------|-----------------|
| Decommissioning a host | Yes — frees the seat on the server. |
| Migrating to a new server | Yes on the old host, then `license:activate` on the new one. |
| Rotating signing keys | No — heartbeat handles this transparently. |
| License expired | No — let it expire and re-activate with a new key. |

## Idempotency

Deactivating an already-deactivated host is a no-op (server returns `404`; the local store is cleared regardless).

## Next Steps

- [Activation](01-activation.md)
- [Troubleshooting](../05-operations/03-troubleshooting.md)
