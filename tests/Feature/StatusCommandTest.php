<?php

use Carbon\CarbonImmutable;
use G8Key\Client\Contracts\LicenseStore;
use G8Key\Client\Tests\Support\TokenFactory;

beforeEach(function () {
    $this->factory = new TokenFactory;

    config()->set('g8key-client.public_keys', [
        $this->factory->kid => $this->factory->publicKeyBase64(),
    ]);
});

it('exits 1 with not_activated status when no license is cached', function () {
    $this->artisan('license:status')
        ->assertFailed()
        ->expectsOutputToContain('not_activated');
});

it('exits 0 and prints active details when a valid license is cached', function () {
    app(LicenseStore::class)->write([
        'activation_uuid' => '01HZTEST',
        'token' => $this->factory->token(),
        'status' => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $this->artisan('license:status')
        ->assertSuccessful()
        ->expectsOutputToContain('active')
        ->expectsOutputToContain('01HZTEST')
        ->expectsOutputToContain('pro');
});
