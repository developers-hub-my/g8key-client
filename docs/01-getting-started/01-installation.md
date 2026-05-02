# Installation

Install `developers-hub-my/g8key-client` into a Laravel application and publish its assets.

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | `>= 8.4` |
| Laravel | `^11.0` `^12.0` `^13.0` |
| Extension `ext-sodium` | required (EdDSA verification) |
| Extension `ext-curl` | required (HTTP) |
| Extension `ext-json` | required |

## Install via Composer

```bash
composer require developers-hub-my/g8key-client
```

The package auto-registers `G8Key\Client\G8KeyClientServiceProvider` and the `License` facade alias through
`extra.laravel` discovery — no manual provider entry needed.

## Publish the configuration

```bash
php artisan vendor:publish --tag="g8key-client-config"
```

This copies `config/g8key-client.php` into the host application. See
[Configuration Reference](03-configuration.md) for the meaning of each field.

## Publish the migration (optional)

Only required if you set `'store' => 'database'` in the config. Skip it for the default file store.

```bash
php artisan vendor:publish --tag="g8key-client-migrations"
php artisan migrate
```

## Environment variables

Add the minimum set to `.env`:

```env
G8KEY_AUDIENCE=g8stack
G8KEY_API_BASE=https://lic.g8suite.com

# At least one public key, keyed by kid. The G8Key admin gives you both.
G8STACK_LICENSE_PUBLIC_KEY_G8STACK_2026_04=base64-encoded-public-key
```

The audience must match the `aud` claim in the signed token — i.e. the product slug registered in G8Key.

## Verify the install

```bash
php artisan list | grep license
```

You should see four commands: `license:activate`, `license:heartbeat`, `license:deactivate`, `license:status`.

## Next Steps

- [Quick Start](02-quick-start.md) — activate a license and read an entitlement
- [Configuration Reference](03-configuration.md) — every config field explained
