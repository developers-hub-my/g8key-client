# Contributing

How to propose changes to `g8key-client`.

## Before you open a PR

1. Read the [Implementation Plan](01-implementation-plan.md) — most large changes belong inside an existing phase.
2. Open an issue first for anything that changes the public API (facade methods, middleware aliases, config shape).
3. Run the suite locally: `composer test`.
4. Run the linter: `vendor/bin/pint --test`.
5. Run static analysis: `composer analyse`.

## Branching

- `main` is the integration branch.
- Feature branches: `feature/<short-description>`.
- Fix branches: `fix/<short-description>`.

## Commit style

Conventional Commits, no emojis:

```text
feat(verifier): add EdDSA verifier with kid-keyed map
fix(heartbeat): preserve cached token on network failure
test(activator): cover 401 + 410 + 423 paths
docs(operations): document key rotation flow
```

The CHANGELOG is generated from these — descriptive subject lines pay off.

## What to test

| Change | Required tests |
|--------|----------------|
| New facade method | Unit test for the manager method + arch test if it touches new boundaries |
| New middleware | Feature test: 200 happy path + 403 for each failure path |
| New exception | Verifier or service test that throws and asserts class |
| Config change | Update `02-architecture/02-package-layout.md` and `04-configuration/01-environment-vars.md` |

## What not to test

- Laravel framework behaviour itself
- Spatie LaravelPackageTools internals
- The remote G8Key server (mock with `Http::fake()`)

## Releasing

The package follows semver. Coordinate releases with G8Key server changes when the wire format moves. Cross-link the
matching server release in the CHANGELOG entry.

## Next Steps

- [Implementation Plan](01-implementation-plan.md)
- [Testing](02-testing.md)
