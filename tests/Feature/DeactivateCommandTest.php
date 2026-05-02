<?php

use Carbon\CarbonImmutable;
use G8Key\Client\Contracts\LicenseStore;
use G8Key\Client\Tests\Support\TokenFactory;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->factory = new TokenFactory;

    config()->set('g8key-client.public_keys', [
        $this->factory->kid => $this->factory->publicKeyBase64(),
    ]);

    app(LicenseStore::class)->write([
        'activation_uuid' => '01HZTEST',
        'token' => $this->factory->token(),
        'status' => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);
});

it('clears the local cache on a successful deactivate', function () {
    Http::fake([
        'https://g8key.test/api/v1/g8key/deactivate' => Http::response(['message' => 'Activation deactivated.'], 200),
    ]);

    $this->artisan('license:deactivate', ['--force' => true])->assertSuccessful();

    expect(app(LicenseStore::class)->exists())->toBeFalse();

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer 01HZTEST');
    });
});

it('treats 401 as already deactivated and clears the cache', function () {
    Http::fake([
        'https://g8key.test/api/v1/g8key/deactivate' => Http::response(['message' => 'Unauthenticated.'], 401),
    ]);

    $this->artisan('license:deactivate', ['--force' => true])->assertSuccessful();

    expect(app(LicenseStore::class)->exists())->toBeFalse();
});
