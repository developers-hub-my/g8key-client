# ADR-0002: Wire format versioning

## Status

Accepted

## Context

The G8Key wire format has two surfaces the client and server must agree on:

1. **HTTP API** — `POST /api/v1/g8key/{activate,heartbeat,deactivate}`, the request and response shapes, and the
   bearer-token convention for `/heartbeat` and `/deactivate`.
2. **Token format** — the EdDSA-signed token returned by `/activate` and `/heartbeat`. Header has `alg/typ/kid`.
   Payload claims are `iss / sub / aud / customer / tier / seats / features / iat / nbf / exp / grace_days /
   fingerprint`. Spec: `g8key-app/docs/06-g8key/02-token-format.md` (status: Stable as of v1.0).

Today the contract works because the server (`developers-hub-my/g8key-app`) and this client move together. That stops
working as the G8Suite roadmap fans out:

- Multiple G8 products (G8Stack, G8ID, G8Connect, …) each pin a different client minor.
- Customer self-hosted instances upgrade on their own cadence.
- Coordinated server-side and client-side deploys are not realistic at scale.

Three failure modes are possible without a versioning policy:

| Failure mode | Example | Risk |
|---|---|---|
| Additive | Server adds `seats_used` claim | Old clients ignore it. Safe. |
| Required | Server makes `entitlements_v2` mandatory and drops `features` | Old clients silently miss new entitlements; access decisions become wrong. |
| Semantic shift | `seats` is redefined from per-license to per-tenant | Old clients misinterpret the field; the worst class of bug. |

Without an ADR, the third decision (and the second) gets made under pressure when a real change lands.

## Decision

The wire format follows three rules:

### 1. URL path carries the breaking-change version

`/api/v1/g8key/...` is the v1 surface. Any breaking change to request or response shape — adding a required field,
removing a field, changing semantics, or changing status-code semantics — moves to `/api/v2/g8key/...`. Both
versions live side-by-side for at least one major client release.

The package's `api_base` config does not encode the version; the version is part of the route. The package's
HTTP services hardcode `v1` paths until v2 ships, at which point the package ships a major version that targets v2.

### 2. Token claims are additive only

New claims (`seats_used`, `min_client_version`, future entitlement maps, …) can land any time. Clients that don't
know about a claim ignore it.

Claims **never** change semantics in place. Renaming or repurposing an existing claim is treated as a breaking
change and goes through `/api/v2/`. Removing a claim follows the deprecation rule below.

The verifier's mandatory checks (`alg/typ/kid/sig/aud/nbf/exp`) are part of the v1 contract and frozen. Changes to
verification order or the set of mandatory checks require `/api/v2/`.

### 3. Deprecation window for additive claims

When the server deprecates a claim:

1. Server CHANGELOG marks the claim as deprecated, with a target removal release.
2. Server keeps emitting the claim for **at least** two minor versions OR six months — whichever is longer.
3. Client CHANGELOG mirrors the deprecation.
4. After the window, server can stop emitting the claim. Clients that still read it gracefully degrade — they get
   `null` from the relevant accessor (e.g. `License::seatsRemaining()` returning `null` when `seats_used` is gone).

Removed claims are documented in the **server** CHANGELOG and in `g8key-app/docs/06-g8key/02-token-format.md`'s
"Reserved" section.

## Consequences

### Easier

- Adding new entitlements, telemetry claims, and policy claims (`seats_used`, `expires_in_days`, etc.) lands once
  on the server; every client picks it up the next heartbeat with no client-side change.
- Customers can run client and server at different minor versions safely. No coordinated deploy.
- The `seatsRemaining()` pattern — return `null` until the server emits the new claim — is now the documented
  shape of every forward-compat addition.

### Harder

- Server engineers cannot rename claims for clarity. Once `seats` is `seats`, it stays `seats`.
- Bigger structural changes require the cost of a `/v2/` rollout — both routes live for a release window before the
  v1 surface is sunset.
- Every claim added is a claim we are committing to deprecate, not delete.

### When this would not be the right call

- A pre-1.0 token format. We are post-stable as declared in the server token-format spec.
- A licensing-only deployment with a single client. Coordinated upgrades remove the need for an ADR. The G8Suite
  roadmap does not match this profile.

## References

- `g8key-app/docs/06-g8key/02-token-format.md` — token format spec (status: Stable as of v1.0).
- `g8key-app/routes/api/g8key.php` — `/api/v1/g8key/*` route definitions.
- ADR-0001 (Shared package vs inline integration) — the consequence of choosing a shared package is that wire-format
  versioning becomes load-bearing.
