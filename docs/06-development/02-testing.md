# Testing

Test layout and conventions.

## Stack

- **Pest 4** as the runner
- **Orchestra Testbench** for Laravel package testing
- `pestphp/pest-plugin-arch` for architecture rules
- `pestphp/pest-plugin-laravel` for HTTP/scheduler/database helpers

## Layout

```text
tests/
├── Pest.php                          # uses(TestCase::class)->in(__DIR__)
├── TestCase.php                      # Orchestra base
├── ArchTest.php                      # arch rules
├── Unit/
│   ├── VerifierTest.php
│   ├── FileLicenseStoreTest.php
│   └── LicenseManagerTest.php
└── Feature/
    ├── ActivateCommandTest.php
    ├── HeartbeatCommandTest.php
    ├── DeactivateCommandTest.php
    ├── StatusCommandTest.php
    └── MiddlewareTest.php
```

## Verifier tests

The verifier is the security-critical path. Cover every failure mode:

| Scenario | Expected exception |
|----------|-------------------|
| Token has fewer or more than 3 segments | `MalformedTokenException` |
| Header `alg != EdDSA` | `AlgConfusionException` |
| Header `typ != G8K` | `MalformedTokenException` |
| Header `kid` not in map | `UnknownKidException` |
| Signature byte length != 64 | `InvalidSignatureException` |
| Signature does not verify | `InvalidSignatureException` |
| Payload `aud` mismatch | `AudienceMismatchException` |
| Payload `nbf` in future | `MalformedTokenException` |
| Payload `exp` in past | `TokenExpiredException` |
| Happy path | returns payload array |

Generate a real keypair in `beforeEach()` so tests do not depend on fixtures.

## Network tests

Use `Http::fake()` to assert the request shape and feed canned responses:

```php
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'https://g8key.devhub.my/api/v1/g8key/activate' => Http::response([
            'activation_uuid' => '01HZ...',
            'token'           => fakeSignedToken(/* ... */),
        ], 200),
    ]);
});
```

Each command test asserts exactly one request, with the right body shape, and exactly one store write.

## Architecture tests

```php
arch('it does not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('verifier has no HTTP dependencies')
    ->expect('G8Key\Client\Services\Verifier')
    ->not->toUse(['Illuminate\Http\Client\Factory', 'GuzzleHttp\Client']);

arch('exceptions extend the base exception')
    ->expect('G8Key\Client\Exceptions')
    ->toExtend('G8Key\Client\Exceptions\G8KeyClientException');
```

## Running

```bash
composer test                # full suite
composer test-coverage       # with coverage
vendor/bin/pest --filter=Verifier
```

## Next Steps

- [Implementation Plan](01-implementation-plan.md)
- [Contributing](03-contributing.md)
