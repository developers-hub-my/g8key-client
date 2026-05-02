# Architecture Decisions

Decision records that shape the package.

## Overview

ADRs capture *why* the package looks the way it does. They are dated, immutable once accepted, and superseded by new
ADRs rather than edited.

## Index

| ADR | Title | Status |
|-----|-------|--------|
| [Template](00-template.md) | ADR template (copy when writing a new one) | -- |
| [ADR-0001](01-package-vs-inline.md) | Shared package vs inline integration | Accepted |
| [ADR-0002](02-wire-format-versioning.md) | Wire format versioning | Accepted |

## Process

1. Open a discussion on the proposed decision.
2. Copy [`00-template.md`](00-template.md) to `0N-{title}.md` with status `Proposed`.
3. Once consensus lands, change status to `Accepted` and merge.
4. If a future decision invalidates this one, do not edit — write a new ADR with status `Accepted` and update the
   superseded ADR's status to `Superseded by ADR-XXXX` (where `XXXX` is the new ADR number).
