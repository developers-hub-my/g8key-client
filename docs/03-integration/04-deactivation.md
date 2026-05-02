# Deactivation

Release an activation back to the server.

## Command

```bash
php artisan license:deactivate
```

## Wire format

Request — `POST {api_base}/api/v1/g8key/deactivate`:

```http
Authorization: Bearer 01HZ...     ← the activation_uuid from /activate
Content-Type: application/json

(empty body)
```

Response — `200 OK`:

```json
{ "message": "Activation deactivated." }
```

## What it does

1. Reads the cached `activation_uuid`.
2. POSTs to `/deactivate` with the UUID as the bearer token.
3. On success — or `401` / `404` (activation already gone server-side) — clears the local store.

## When to use it

| Situation | Use deactivate? |
|-----------|-----------------|
| Decommissioning a host | Yes — frees the seat on the server. |
| Migrating to a new server | Yes on the old host, then `license:activate` on the new one. |
| Rotating signing keys | No — heartbeat handles this transparently. |
| License expired | No — let it expire and re-activate with a new key. |

## Idempotency

Deactivating a host whose activation no longer exists server-side returns `401` or `404`; the package treats both as
already-deactivated and clears the local store regardless.

## Next Steps

- [Activation](01-activation.md)
- [Troubleshooting](../05-operations/03-troubleshooting.md)
