# Public Keys

Managing the `kid` → public-key map.

## Why a map

Tokens carry a `kid` header. The verifier picks the matching public key from the map. Multiple kids can coexist, which
is what makes zero-downtime rotation possible: during the grace window, both the old and new keys live in the map, and
the verifier handles whichever the server happens to sign with.

## Configuration shape

```php
// config/g8key-client.php
'public_keys' => [
    'g8stack-2026-04' => env('G8STACK_LICENSE_PUBLIC_KEY_G8STACK_2026_04'),
    'g8stack-2026-07' => env('G8STACK_LICENSE_PUBLIC_KEY_G8STACK_2026_07'),
],
```

`kid` is the string you see on the G8Key admin product page → Signing Keys. The value is base64-encoded
(33-byte raw → 44-char base64).

## Where the public key comes from

```bash
# In the G8Key admin repo:
php artisan tinker --execute '
    $p = App\Models\Product::where("slug", "g8stack")->first();
    $k = $p->signingKeys()->where("status", "active")->first();
    echo "kid: " . $k->kid . PHP_EOL;
    echo "public_key: " . $k->public_key . PHP_EOL;
'
```

Paste both values into the host product's `.env`.

## Where the public key does NOT come from

Never fetch the public key over an insecure channel. Either:

- Copy from the G8Key admin UI manually, or
- Fetch over TLS once at install time from `/api/v1/g8key/public-keys/{slug}` and cache locally

The package does not auto-fetch. That choice belongs to the deployment.

## Validation

The package does not validate public-key correctness on boot. The first verification attempt that uses a malformed key
will throw `InvalidSignatureException`. To validate eagerly:

```bash
php artisan license:status
```

That triggers a verification of the cached token against the configured map.

## Next Steps

- [Stores](03-stores.md)
- [Key Rotation](../05-operations/01-key-rotation.md) — operator procedure
