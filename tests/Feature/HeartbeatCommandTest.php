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

    $this->originalToken = $this->factory->token();

    app(LicenseStore::class)->write([
        'activation_uuid'   => '01HZTEST',
        'token'             => $this->originalToken,
        'status'            => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->subDay()->toIso8601String(),
    ]);
});

it('refreshes the cached token on a 200 heartbeat', function () {
    $newToken = $this->factory->token(['seats' => 10]);

    Http::fake([
        'https://g8key.test/api/v1/g8key/heartbeat' => Http::response(['token' => $newToken], 200),
    ]);

    $this->artisan('license:heartbeat')->assertSuccessful();

    $cached = app(LicenseStore::class)->read();
    expect($cached['token'])->toBe($newToken);
    expect($cached['status'])->toBe('active');
});

it('flips status to revoked on a 410 response', function () {
    Http::fake([
        'https://g8key.test/api/v1/g8key/heartbeat' => Http::response(['error' => 'revoked'], 410),
    ]);

    $this->artisan('license:heartbeat')->assertFailed();

    $cached = app(LicenseStore::class)->read();
    expect($cached['status'])->toBe('revoked');
});

it('flips status to suspended on a 423 response', function () {
    Http::fake([
        'https://g8key.test/api/v1/g8key/heartbeat' => Http::response(['error' => 'suspended'], 423),
    ]);

    $this->artisan('license:heartbeat')->assertFailed();

    $cached = app(LicenseStore::class)->read();
    expect($cached['status'])->toBe('suspended');
});

it('preserves cache state on network failure', function () {
    Http::fake(function () {
        throw new Illuminate\Http\Client\ConnectionException('refused');
    });

    $this->artisan('license:heartbeat')->assertSuccessful();

    $cached = app(LicenseStore::class)->read();
    expect($cached['token'])->toBe($this->originalToken);
    expect($cached['status'])->toBe('active');
});
