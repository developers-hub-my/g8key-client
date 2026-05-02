# Activation

How `license:activate` works and how to wire it into a product's install runbook.

## Command

```bash
php artisan license:activate G8ST-K7HM-3PXR-9F2B-WQ8N
```

## Wire format

Request — `POST {api_base}/api/v1/g8key/activate`:

```json
{
  "license_key": "G8ST-K7HM-3PXR-9F2B-WQ8N",
  "fingerprint": "sha256:<64-hex>",
  "hostname": "web-1",
  "instance_label": "production",
  "product_version": "1.0.0"
}
```

`license_key` and `fingerprint` are required. The other three are optional context the server stores against the
activation row for telemetry.

Response — `201 Created`:

```json
{
  "activation_uuid": "01HZ...",
  "token": "eyJh...",
  "expires_in": 86400
}
```

The package verifies the token offline before persisting it.

## What the command does

1. Resolves a fingerprint via `Fingerprint::resolve()` — uses the configured `fingerprint` callable if set,
   otherwise computes `sha256:` + `sha256(JSON of {app_url, hostname})` to match the server's
   `App\Services\G8Key\FingerprintGenerator`.
2. POSTs `{license_key, fingerprint, hostname}` (and any explicit `instance_label` / `product_version` context).
3. Verifies the returned token offline against the configured public-key map.
4. Persists `{ activation_uuid, token, status: 'active', last_heartbeat_at: now }` via the configured store.

## Custom fingerprint

Override the fingerprint generator if your deployment topology calls for it (e.g. include the container ID, exclude the
hostname when running behind a load balancer):

```php
// config/g8key-client.php
'fingerprint' => fn () => \G8Key\Client\Services\Fingerprint::compute([
    'app_url'  => config('app.url'),
    'hostname' => gethostname(),
    'cluster'  => env('CLUSTER_ID'),
]),
```

`Fingerprint::compute()` matches the server's algorithm exactly: lowercases + trims attributes, sorts by key,
JSON-encodes, sha256-hashes, prefixes `sha256:`. Stay in this format so existing activations survive.

## Re-activation

The server treats a repeat activation with the **same** `(license_key, fingerprint)` pair as **idempotent** — it
returns the existing activation UUID and a fresh token. Different fingerprint → consumes another seat (or 409 if at
the seat limit). Run `license:deactivate` on the old host before re-pointing at a new one.

## Common failures

| Failure | Likely cause | Resolution |
|---------|--------------|------------|
| `ActivationFailedException: 422` | License key malformed (failed checksum) | Verify the key with G8Key admin. |
| `ActivationFailedException: 404` | License not found on server | Check the key is for the right product. |
| `ActivationFailedException: 410` | License revoked | Issue a new license. |
| `ActivationFailedException: 423` | License suspended | Coordinate with G8Key admin. |
| `ActivationFailedException: 409` | Seat limit reached or license inactive | Deactivate an old host or upgrade the seat count. |
| `AudienceMismatchException` | `G8KEY_AUDIENCE` does not match the product slug on the server | Fix the env var. |
| `UnknownKidException` | Server signed with a kid not present in `public_keys` config | Add the new kid → public key entry; see [Key Rotation](../05-operations/01-key-rotation.md). |
| `InvalidSignatureException` | Wrong public key value pasted into env | Re-copy from G8Key admin's product detail page. |

## Where to put activation in the install flow

For SaaS deployments: as a one-shot step after `php artisan migrate` and seeding, before the application opens to
traffic. For on-prem deployments: as part of the operator runbook, immediately after first deploy.

## Next Steps

- [Heartbeat](02-heartbeat.md) — scheduling the daily refresh
- [Deactivation](04-deactivation.md) — releasing an activation
