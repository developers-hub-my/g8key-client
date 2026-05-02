<?php

use G8Key\Client\Contracts\LicenseStore;
use G8Key\Client\Tests\Support\TokenFactory;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->factory = new TokenFactory;

    config()->set('g8key-client.public_keys', [
        $this->factory->kid => $this->factory->publicKeyBase64(),
    ]);
});

it('activates and persists the verified token on success', function () {
    $token = $this->factory->token();

    Http::fake([
        'https://g8key.test/api/v1/g8key/activate' => Http::response([
            'activation_uuid' => '01HZTEST',
            'token' => $token,
            'expires_in' => 86400,
        ], 201),
    ]);

    $this->artisan('license:activate', ['key' => 'G8ST-AAAA-BBBB-CCCC-DDDD'])
        ->assertSuccessful()
        ->expectsOutputToContain('License activated.')
        ->expectsOutputToContain('Tier:    pro');

    $cached = app(LicenseStore::class)->read();
    expect($cached['activation_uuid'])->toBe('01HZTEST');
    expect($cached['token'])->toBe($token);
    expect($cached['status'])->toBe('active');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://g8key.test/api/v1/g8key/activate'
            && $request['license_key'] === 'G8ST-AAAA-BBBB-CCCC-DDDD'
            && is_string($request['fingerprint'])
            && str_starts_with($request['fingerprint'], 'sha256:');
    });
});

it('fails when the activate endpoint returns a non-2xx', function () {
    Http::fake([
        'https://g8key.test/api/v1/g8key/activate' => Http::response(['message' => 'License not found.'], 404),
    ]);

    $this->artisan('license:activate', ['key' => 'BAD-KEY'])->assertFailed();

    expect(app(LicenseStore::class)->exists())->toBeFalse();
});
