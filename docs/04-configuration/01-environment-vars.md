# Environment Variables

Every variable the package reads.

## Reference

| Variable | Default | Required | Purpose |
|----------|---------|----------|---------|
| `G8KEY_AUDIENCE` | `g8stack` | yes | Product slug; must equal the `aud` claim. |
| `G8KEY_API_BASE` | `https://lic.g8suite.com` | yes | G8Key server base URL. |
| `G8KEY_API_TIMEOUT` | `10` | no | HTTP timeout in seconds. |
| `G8KEY_STORE` | `file` | no | `file` or `database`. |
| `G8KEY_OFFLINE_GRACE_DAYS` | `7` | no | Days the cached token stays trusted after a failed heartbeat. |
| `G8{PRODUCT}_LICENSE_PUBLIC_KEY_{KID}` | -- | yes (≥ 1) | Public key entry. See [Public Keys](02-public-keys.md). |

## Example: G8Stack `.env`

```env
G8KEY_AUDIENCE=g8stack
G8KEY_API_BASE=https://lic.g8suite.com
G8KEY_API_TIMEOUT=10
G8KEY_STORE=file
G8KEY_OFFLINE_GRACE_DAYS=7

# Active key
G8STACK_LICENSE_PUBLIC_KEY_G8STACK_2026_07=<base64 public key>

# Old key, still in grace window after rotation
G8STACK_LICENSE_PUBLIC_KEY_G8STACK_2026_04=<base64 public key>
```

## Example: G8ID `.env`

```env
G8KEY_AUDIENCE=g8id
G8KEY_API_BASE=https://lic.g8suite.com

G8ID_LICENSE_PUBLIC_KEY_G8ID_2026_04=<base64 public key>
```

The variable name is convention, not enforcement; what matters is that whatever you name it gets referenced from the
`public_keys` array in `config/g8key-client.php`.

## Next Steps

- [Public Keys](02-public-keys.md)
- [Stores](03-stores.md)
