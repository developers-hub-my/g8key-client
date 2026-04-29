<?php

use Carbon\CarbonImmutable;
use G8Key\Client\LicenseManager;
use G8Key\Client\Services\Verifier;
use G8Key\Client\Stores\FileLicenseStore;
use G8Key\Client\Tests\Support\TokenFactory;

beforeEach(function () {
    $this->factory = new TokenFactory;
    $this->path = tempnam(sys_get_temp_dir(), 'g8key-mgr-').'.json';
    @unlink($this->path);
    $this->store = new FileLicenseStore($this->path);
    $this->verifier = new Verifier(
        [$this->factory->kid => $this->factory->publicKeyBase64()],
        'g8stack',
    );
});

afterEach(function () {
    @unlink($this->path);
});

it('reports not_activated when the store is empty', function () {
    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->status())->toBe('not_activated');
    expect($manager->isValid())->toBeFalse();
    expect($manager->payload())->toBeNull();
    expect($manager->has('sso'))->toBeFalse();
});

it('reports active and exposes payload entitlements when token is valid', function () {
    $token = $this->factory->token();
    $this->store->write([
        'activation_uuid'   => '01HZTEST',
        'token'             => $token,
        'status'            => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->status())->toBe('active');
    expect($manager->isValid())->toBeTrue();
    expect($manager->tier())->toBe('pro');
    expect($manager->seats())->toBe(5);
    expect($manager->has('sso'))->toBeTrue();
    expect($manager->has('nonexistent'))->toBeFalse();
    expect($manager->activationUuid())->toBe('01HZTEST');
});

it('reports revoked when the store says so, regardless of the token', function () {
    $token = $this->factory->token();
    $this->store->write([
        'activation_uuid'   => '01HZTEST',
        'token'             => $token,
        'status'            => 'revoked',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->status())->toBe('revoked');
    expect($manager->isValid())->toBeFalse();
    expect($manager->has('sso'))->toBeFalse();
});

it('reports suspended when the store says so', function () {
    $token = $this->factory->token();
    $this->store->write([
        'activation_uuid'   => '01HZTEST',
        'token'             => $token,
        'status'            => 'suspended',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->status())->toBe('suspended');
    expect($manager->isValid())->toBeFalse();
});

it('reports offline_grace when the token expired but heartbeat is recent', function () {
    $expiredToken = $this->factory->token(['nbf' => time() - 7200, 'exp' => time() - 3600]);

    $this->store->write([
        'activation_uuid'   => '01HZTEST',
        'token'             => $expiredToken,
        'status'            => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->subHour()->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->status())->toBe('offline_grace');
    expect($manager->isValid())->toBeTrue();
    expect($manager->isInOfflineGrace())->toBeTrue();
});

it('reports expired when the token expired and heartbeat is beyond grace', function () {
    $expiredToken = $this->factory->token(['nbf' => time() - 7200, 'exp' => time() - 3600]);

    $this->store->write([
        'activation_uuid'   => '01HZTEST',
        'token'             => $expiredToken,
        'status'            => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->subDays(30)->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->status())->toBe('expired');
    expect($manager->isValid())->toBeFalse();
});

it('memoizes the verified payload across multiple calls within one request', function () {
    $token = $this->factory->token();
    $this->store->write([
        'activation_uuid'   => '01HZTEST',
        'token'             => $token,
        'status'            => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $verifier = new class($this->factory->kid, $this->factory->publicKeyBase64()) extends Verifier {
        public int $calls = 0;

        public function __construct(string $kid, string $key)
        {
            parent::__construct([$kid => $key], 'g8stack');
        }

        public function verify(string $token): array
        {
            $this->calls++;

            return parent::verify($token);
        }
    };

    $manager = new LicenseManager($this->store, $verifier, 7);

    $manager->payload();
    $manager->tier();
    $manager->has('sso');
    $manager->status();

    expect($verifier->calls)->toBe(1);
});
