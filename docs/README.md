# Documentation

Documentation for `developers-hub-my/g8key-client` — the shared Laravel client package every G8Suite product
(G8Stack, G8ID, G8Connect, …) uses to verify licenses issued by [G8Key](https://g8key.devhub.my).

## Overview

This package gives a G8 product four things:

- **Offline EdDSA token verification** that mirrors the server-side verifier exactly
- **Activation, heartbeat, and deactivation** commands against the G8Key API
- **Entitlement checks** through a `License` facade, route middleware, and Blade directive
- **A pluggable license cache** (file or database) with multi-`kid` public-key support for zero-downtime rotation

The G8Key server is the issuer; this package is what every product *consumes*.

## Documentation Structure

### [01. Getting Started](01-getting-started/README.md)

Install, publish config, activate, and read your first entitlement.

### [02. Architecture](02-architecture/README.md)

Package layout, token wire format, and the activate-heartbeat-verify data flow.

### [03. Integration](03-integration/README.md)

How a product wires the package into commands, middleware, scheduler, and Blade.

### [04. Configuration](04-configuration/README.md)

Environment variables, public-key map, and license-store choice.

### [05. Operations](05-operations/README.md)

Key rotation runbook, offline grace behaviour, and troubleshooting.

### [06. Development](06-development/README.md)

Implementation plan, testing approach, and contribution guide. Read this if you are *building* the package.

### [07. Decisions](07-decisions/README.md)

Architecture Decision Records.

## Quick Start

New to the package? Start with [01-installation.md](01-getting-started/01-installation.md), then run through the
[Quick Start](01-getting-started/02-quick-start.md).

## Finding Information

- **Concepts** — [Architecture](02-architecture/README.md)
- **How-to** — [Integration](03-integration/README.md)
- **Operator runbooks** — [Operations](05-operations/README.md)
- **Why we built it this way** — [Decisions](07-decisions/README.md)

## External References

- [G8Key server documentation](https://github.com/developers-hub-my/g8key-app/tree/main/docs/06-g8key) — token format,
  threat model, key rotation from the issuer side
- [Production integration guide](https://github.com/developers-hub-my/g8key-app/blob/main/docs/06-g8key/06-production-integration.md)
  — the source-of-truth design document this package implements
