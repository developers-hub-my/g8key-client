# ADR-0001: Shared package vs inline integration

## Status

Accepted

## Context

G8Key issues licenses for many G8Suite products: G8Stack, G8ID, G8Connect, with more on the roadmap (target: ~15
products). Each product needs to verify tokens, manage activation cache, run a heartbeat, and gate features.

Two reasonable shapes:

1. **Inline** — copy the licensing code into each product's `app/Services/Licensing/`.
2. **Shared package** — extract `developers-hub-my/g8key-client` and `composer require` it from each product.

## Decision

Build a shared Composer package.

## Consequences

### Easier

- Bug fixes and security hardening to the verifier land in one place. The verifier is the security boundary; having
  one canonical implementation is the point.
- Console-command UX is consistent across products. Every product has the same `php artisan license:*` surface.
- Key rotation procedure is identical in every product: bump `.env`, redeploy.
- Cross-cutting features (`License::seatsRemaining()`, deprecation warnings for expiring licenses, etc.) land once.
- Each product's tree stays focused on its own domain; licensing is a transitive dependency, not 1.5kloc of side code.

### Harder

- Coordinating releases. A breaking change to the package requires bumping every product. Mitigated by semver
  discipline and a deprecation window.
- Local development across products + package. Composer path repositories and `link` workflows are well-trodden but
  add friction relative to inline code.
- Versioning on the wire. The package and server must agree on the token format. ADR-XXXX (future) will lock down the
  protocol-versioning policy.

## When this would not be the right call

- Single product. Inline is simpler when there is one consumer.
- The product genuinely needs different verification logic (e.g. embedded environment with no `ext-sodium`).
  This is not the case for any G8Suite product today.

## References

- Production integration guide:
  <https://github.com/developers-hub-my/g8key-app/blob/main/docs/06-g8key/06-production-integration.md>
- The decision tree section of that guide is the upstream version of this ADR.
