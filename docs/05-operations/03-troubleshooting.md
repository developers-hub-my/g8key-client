# Troubleshooting

Symptom → likely cause → fix.

## Quick diagnostics

```bash
php artisan license:status
```

Always start here. The output identifies which state the package thinks it is in and (when the cached token verifies)
prints the kid, audience, and expiry.

## Symptom table

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| `License::isValid()` returns false right after activation | Audience mismatch between config and token | Confirm `G8KEY_AUDIENCE` matches the product slug in G8Key admin. |
| Activation throws `UnknownKidException` | Server signed with a kid not present in `public_keys` map | Add the new kid → public key entry. See [Key Rotation](01-key-rotation.md). |
| Activation throws `InvalidSignatureException` | Public-key value is wrong (typo, truncation, or wrong product) | Re-copy the key from G8Key admin's product detail page. |
| Heartbeat keeps failing with `ActivationFailedException` (network) | Firewall / DNS issue reaching `api_base` | Confirm outbound HTTPS to `lic.g8suite.com` is allowed. |
| `License::status()` returns `revoked` | Server marked the license revoked | Issue a new license through G8Key admin and re-activate. |
| `License::status()` returns `suspended` | Server marked the license suspended | Coordinate with G8Key admin to un-suspend; next heartbeat clears the state. |
| `License::status()` returns `offline_grace` | Heartbeat is failing but grace not yet exhausted | Investigate network; run `php artisan license:heartbeat` manually. |
| `License::status()` returns `not_activated` | Cached store is missing or empty | Run `license:activate <key>` again. |
| `TokenExpiredException` on every request | Token expired and beyond grace | Run heartbeat manually. If still expired, check server-side license expiry. |
| `License::has('feature')` returns false unexpectedly | Feature not in the token | Verify the license tier / features in G8Key admin; some features require an upgrade. |

## File-store specific

| Symptom | Cause | Fix |
|---------|-------|-----|
| `RuntimeException: cannot write license cache` | `storage/app/` not writable by the web user | `chown -R www-data: storage/` or equivalent. |
| `JsonException` reading `license.json` | File corrupted (manual edit, partial write from a kill -9) | Delete the file; re-activate. |

## Database-store specific

| Symptom | Cause | Fix |
|---------|-------|-----|
| `Table 'g8key_licenses' not found` | Migration not run | `php artisan vendor:publish --tag="g8key-client-migrations"` then `php artisan migrate`. |

## Enabling debug logging

The package logs activation, heartbeat, and verification failures via Laravel's logger at `info` and `warning` levels.
Tail your application log during activation to see the full request/response shape.

## Reporting a bug

Open an issue at <https://github.com/developers-hub-my/g8key-client/issues> with:

- `php artisan license:status` output (redact the `token` field)
- The exception class and message
- Whether you are mid-rotation
- Laravel and PHP versions
