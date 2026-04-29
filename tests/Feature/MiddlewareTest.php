<?php

use Carbon\CarbonImmutable;
use G8Key\Client\Contracts\LicenseStore;
use G8Key\Client\LicenseManager;
use G8Key\Client\Tests\Support\TokenFactory;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->factory = new TokenFactory;

    config()->set('g8key-client.public_keys', [
        $this->factory->kid => $this->factory->publicKeyBase64(),
    ]);

    Route::middleware('license')->get('/protected', fn () => 'ok');
    Route::middleware('license.feature:sso')->get('/sso', fn () => 'ok');
});

afterEach(function () {
    app(LicenseStore::class)->clear();
});

it('returns 403 from license middleware when no license is cached', function () {
    $this->get('/protected')->assertForbidden();
});

it('passes through license middleware when a valid token is cached', function () {
    app(LicenseStore::class)->write([
        'activation_uuid'   => '01HZTEST',
        'token'             => $this->factory->token(),
        'status'            => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    app(LicenseManager::class)->flush();

    $this->get('/protected')->assertOk()->assertSee('ok');
});

it('returns 403 from license.feature middleware when the feature is not in the token', function () {
    app(LicenseStore::class)->write([
        'activation_uuid'   => '01HZTEST',
        'token'             => $this->factory->token(['features' => ['audit_log']]),
        'status'            => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    app(LicenseManager::class)->flush();

    $this->get('/sso')->assertForbidden();
});

it('passes license.feature middleware when the feature is present', function () {
    app(LicenseStore::class)->write([
        'activation_uuid'   => '01HZTEST',
        'token'             => $this->factory->token(['features' => ['sso', 'audit_log']]),
        'status'            => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    app(LicenseManager::class)->flush();

    $this->get('/sso')->assertOk()->assertSee('ok');
});
