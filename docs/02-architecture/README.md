# Architecture

How the pieces fit together: the package, the G8Key server, and the host product.

## Overview

G8Key follows a classic offline-token model. The G8Key server signs short-lived EdDSA tokens; this client package
verifies them locally on every request, with no network round-trip in the hot path. Network calls happen only at
activation and during the daily heartbeat.

## Table of Contents

### [1. Overview](01-overview.md)

The package's role in the G8Suite licensing model.

### [2. Package Layout](02-package-layout.md)

Directory structure, classes, and how the service provider wires them.

### [3. Token Format](03-token-format.md)

Wire format the verifier checks: header, payload, signature, and the validation order.

### [4. Data Flow](04-data-flow.md)

End-to-end sequence diagrams for activation, heartbeat, and entitlement checks.

## Related Documentation

- [Integration](../03-integration/README.md) — applying these patterns in a host product
- [Decisions](../07-decisions/README.md) — why the design chose package over inline
- G8Key server token format docs — the issuer side of the contract.
  See: <https://github.com/developers-hub-my/g8key-app/blob/main/docs/06-g8key/02-token-format.md>
