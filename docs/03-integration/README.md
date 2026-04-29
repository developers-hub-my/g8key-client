# Integration

How a host product wires the package into commands, scheduler, middleware, and Blade.

## Overview

After installation, every G8Suite product follows the same wiring pattern. This section documents that pattern in
detail. Read it once when bringing the package into a new product.

## Table of Contents

### [1. Activation](01-activation.md)

The `license:activate` command, fingerprinting, and post-activation checks.

### [2. Heartbeat](02-heartbeat.md)

Scheduling the daily heartbeat, handling degraded states.

### [3. Entitlements](03-entitlements.md)

Facade, route middleware, and Blade directive.

### [4. Deactivation](04-deactivation.md)

`license:deactivate` and what it does to the cached state.

## Related Documentation

- [Quick Start](../01-getting-started/02-quick-start.md) — the same flow at five-minute pace
- [Data Flow](../02-architecture/04-data-flow.md) — sequence diagrams for each flow
- [Operations](../05-operations/README.md) — runbooks for ongoing operation
