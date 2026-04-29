# Operations

Runbooks for operators running G8 products in production.

## Overview

Day-2 concerns: rotating keys, surviving network outages, diagnosing degraded states.

## Table of Contents

### [1. Key Rotation](01-key-rotation.md)

What to do when the G8Key admin rotates a product's signing key.

### [2. Offline Grace](02-offline-grace.md)

How the package behaves when the heartbeat cannot reach the server.

### [3. Troubleshooting](03-troubleshooting.md)

Symptom → cause → fix table.

## Related Documentation

- [Configuration Reference](../04-configuration/README.md)
- [Heartbeat](../03-integration/02-heartbeat.md)
