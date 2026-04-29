# Data Flow

End-to-end sequences for the three flows that matter.

## Activation

```mermaid
sequenceDiagram
    participant Op as Operator
    participant App as G8 Product
    participant Pkg as g8key-client
    participant Srv as G8Key Server
    participant FS as License Store

    Op->>App: php artisan license:activate G8ST-...
    App->>Pkg: ActivateCommand
    Pkg->>Srv: POST /api/v1/g8key/activate {key, fingerprint}
    Srv-->>Pkg: 200 {activation_uuid, token}
    Pkg->>Pkg: Verifier::verify(token)
    Pkg->>FS: write activation_uuid + token + status=active
    Pkg-->>Op: tier / seats / expires
```

Failure modes:

- Network down → `ActivationFailedException`. No partial state written.
- Wrong audience or unknown kid in returned token → `AudienceMismatchException` / `UnknownKidException`. Indicates the
  product is configured against the wrong public key.

## Heartbeat (daily)

```mermaid
sequenceDiagram
    participant Sch as Scheduler
    participant Pkg as g8key-client
    participant Srv as G8Key Server
    participant FS as License Store

    Sch->>Pkg: license:heartbeat
    Pkg->>FS: read activation_uuid
    Pkg->>Srv: POST /api/v1/g8key/heartbeat {activation_uuid, fingerprint}

    alt 200 OK
        Srv-->>Pkg: {token}
        Pkg->>Pkg: verify token
        Pkg->>FS: update token + last_heartbeat_at
    else 410 Revoked
        Srv-->>Pkg: 410
        Pkg->>FS: status=revoked
    else 423 Suspended
        Srv-->>Pkg: 423
        Pkg->>FS: status=suspended
    else Network error
        Pkg->>FS: leave state untouched (rely on offline_grace_days)
    end
```

Network failures do not flip status. Only an authoritative `410` / `423` from the server does.

## Entitlement check (hot path)

```mermaid
sequenceDiagram
    participant Code as Application code
    participant Fac as License facade
    participant LM as LicenseManager
    participant V as Verifier
    participant FS as License Store

    Code->>Fac: License::has('sso')
    Fac->>LM: has('sso')

    Note over LM: First call this request<br/>memoizes the payload

    LM->>FS: read cached token
    FS-->>LM: token + status + last_heartbeat_at
    LM->>V: verify(token)
    V-->>LM: payload
    LM->>LM: check status, exp + offline_grace_days
    LM-->>Fac: in_array('sso', payload.features)
    Fac-->>Code: bool
```

`LicenseManager` memoizes the verified payload for the duration of the request, so repeated `License::*` calls on the
same request do not re-verify.

## Next Steps

- [Activation walkthrough](../03-integration/01-activation.md)
- [Heartbeat scheduling](../03-integration/02-heartbeat.md)
- [Entitlement patterns](../03-integration/03-entitlements.md)
