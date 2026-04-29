# Activation

How `license:activate` works and how to wire it into a product's install runbook.

## Command

```bash
php artisan license:activate G8ST-K7HM-3PXR-9F2B-WQ8N
```

## What it does

1. Builds a machine fingerprint (default: `sha256(app.url + hostname)`).
2. POSTs the license key + fingerprint to `{api_base}/api/v1/g8key/activate`.
3. Verifies the returned token offline against the configured public-key map.
4. Persists `{ activation_uuid, token, status: 'active', last_heartbeat_at: now }` via the configured store.

## Custom fingerprint

Override the fingerprint generator if your deployment topology calls for it (e.g. include the container ID, exclude the
hostname when running behind a load balancer):

```php
// config/g8key-client.php
'fingerprint' => fn () => hash('sha256', config('app.url').gethostname().machine_id()),
```

## Re-activation

Running `license:activate` on a host that already has a cached license is rejected by default — the existing activation
must be released first via `license:deactivate` (see [Deactivation](04-deactivation.md)). The server side guards
against the same condition.

## Common failures

| Failure | Likely cause | Resolution |
|---------|--------------|------------|
| `ActivationFailedException: 401` | Wrong license key, or product mismatch on server | Verify the key with G8Key admin. |
| `AudienceMismatchException` | `audience` config does not match the product slug on the server | Set `G8KEY_AUDIENCE` to the slug shown in G8Key admin. |
| `UnknownKidException` | Server signed with a kid not present in `public_keys` config | Add the new kid → public key entry; see [Key Rotation](../05-operations/01-key-rotation.md). |
| `InvalidSignatureException` | Wrong public key value pasted into env | Re-copy from G8Key admin's product detail page. |

## Where to put activation in the install flow

For SaaS deployments: as a one-shot step after `php artisan migrate` and seeding, before the application opens to
traffic. For on-prem deployments: as part of the operator runbook, immediately after first deploy.

## Next Steps

- [Heartbeat](02-heartbeat.md) — scheduling the daily refresh
- [Deactivation](04-deactivation.md) — releasing an activation
