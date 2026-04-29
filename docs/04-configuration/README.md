# Configuration

Reference for environment variables, public keys, and license-store choice.

## Overview

This section is the operator's reference. Everything that can be tuned without changing code lives here.

## Table of Contents

### [1. Environment Variables](01-environment-vars.md)

Every env var the package consults, with defaults and examples.

### [2. Public Keys](02-public-keys.md)

How to manage the `kid` → public-key map across rotations.

### [3. Stores](03-stores.md)

File store vs database store: when to choose which.

## Related Documentation

- [Configuration Walkthrough](../01-getting-started/03-configuration.md) — first-pass tour of `config/g8key-client.php`
- [Key Rotation](../05-operations/01-key-rotation.md) — operational procedure
