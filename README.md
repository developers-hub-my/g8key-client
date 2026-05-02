# g8key-client

[![Latest Version](https://img.shields.io/github/v/release/developers-hub-my/g8key-client?style=flat-square)](https://github.com/developers-hub-my/g8key-client/releases)
[![Packagist Version](https://img.shields.io/packagist/v/developers-hub-my/g8key-client.svg?style=flat-square)](https://packagist.org/packages/developers-hub-my/g8key-client)
[![License](https://img.shields.io/github/license/developers-hub-my/g8key-client?style=flat-square)](LICENSE.md)
[![Tests](https://img.shields.io/github/actions/workflow/status/developers-hub-my/g8key-client/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/developers-hub-my/g8key-client/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Code Style](https://img.shields.io/github/actions/workflow/status/developers-hub-my/g8key-client/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/developers-hub-my/g8key-client/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/developers-hub-my/g8key-client.svg?style=flat-square)](https://packagist.org/packages/developers-hub-my/g8key-client)

Shared Laravel client for [G8Key](https://lic.g8suite.com) licensing. Every G8Suite product (G8Stack, G8ID, G8Connect,
…) installs this package to verify offline EdDSA tokens, run activation and heartbeat against the G8Key server, and
gate features through a `License` facade and route middleware.

## Features

- Offline EdDSA token verification mirroring the server-side verifier
- `php artisan license:activate`, `license:heartbeat`, `license:deactivate`, `license:status`
- `License` facade with `has()`, `tier()`, `seats()`, `expiresAt()`, `isValid()`
- Route middleware (`license`, `license.feature:{name}`) and Blade `@licenseFeature` directive
- Pluggable license store (file or database)
- Multi-`kid` public-key map for zero-downtime key rotation

## Installation

```bash
composer require developers-hub-my/g8key-client
php artisan vendor:publish --tag="g8key-client-config"
```

If using the database store:

```bash
php artisan vendor:publish --tag="g8key-client-migrations"
php artisan migrate
```

## Quick Start

Configure `.env`:

```env
G8KEY_AUDIENCE=g8stack
G8KEY_API_BASE=https://lic.g8suite.com
G8STACK_LICENSE_PUBLIC_KEY_G8STACK_2026_04=<base64 from G8Key admin>
```

Activate, schedule, and gate:

```bash
php artisan license:activate G8ST-K7HM-3PXR-9F2B-WQ8N
```

```php
// routes/console.php
Schedule::command('license:heartbeat')->daily()->withoutOverlapping();
```

```php
use G8Key\Client\Facades\License;

if (License::has('sso')) {
    // SSO is on this license tier
}
```

## Documentation

Full documentation lives in [`docs/`](docs/README.md):

- [Getting Started](docs/01-getting-started/README.md) — install, quick start, configuration
- [Architecture](docs/02-architecture/README.md) — package layout, token format, data flow
- [Integration](docs/03-integration/README.md) — activation, heartbeat, entitlements, deactivation
- [Configuration](docs/04-configuration/README.md) — env vars, public keys, stores
- [Operations](docs/05-operations/README.md) — key rotation, offline grace, troubleshooting
- [Development](docs/06-development/README.md) — implementation plan, testing, contributing
- [Decisions](docs/07-decisions/README.md) — architecture decision records

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [docs/06-development/03-contributing.md](docs/06-development/03-contributing.md).

## Security

If you discover a security issue, email <nasrulhazim.m@gmail.com> rather than opening a public issue.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
