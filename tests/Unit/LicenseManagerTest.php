<?php

use Carbon\CarbonImmutable;
use G8Key\Client\Contracts\TokenVerifier;
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
        'activation_uuid' => '01HZTEST',
        'token' => $token,
        'status' => 'active',
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
        'activation_uuid' => '01HZTEST',
        'token' => $token,
        'status' => 'revoked',
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
        'activation_uuid' => '01HZTEST',
        'token' => $token,
        'status' => 'suspended',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->status())->toBe('suspended');
    expect($manager->isValid())->toBeFalse();
});

it('reports offline_grace when the token expired but heartbeat is recent', function () {
    $expiredToken = $this->factory->token(['nbf' => time() - 7200, 'exp' => time() - 3600]);

    $this->store->write([
        'activation_uuid' => '01HZTEST',
        'token' => $expiredToken,
        'status' => 'active',
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
        'activation_uuid' => '01HZTEST',
        'token' => $expiredToken,
        'status' => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->subDays(30)->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->status())->toBe('expired');
    expect($manager->isValid())->toBeFalse();
});

it('memoizes the verified payload across multiple calls within one request', function () {
    $token = $this->factory->token();
    $this->store->write([
        'activation_uuid' => '01HZTEST',
        'token' => $token,
        'status' => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $real = new Verifier(
        [$this->factory->kid => $this->factory->publicKeyBase64()],
        'g8stack',
    );

    $verifier = new class($real) implements TokenVerifier
    {
        public int $calls = 0;

        public function __construct(private readonly Verifier $inner) {}

        public function verify(string $token): array
        {
            $this->calls++;

            return $this->inner->verify($token);
        }
    };

    $manager = new LicenseManager($this->store, $verifier, 7);

    $manager->payload();
    $manager->tier();
    $manager->has('sso');
    $manager->status();

    expect($verifier->calls)->toBe(1);
});

it('exposes customer, graceDays, and fingerprint claims', function () {
    $token = $this->factory->token([
        'customer' => '01HCUSTOMER',
        'grace_days' => 14,
        'fingerprint' => 'sha256:abc123',
    ]);

    $this->store->write([
        'activation_uuid' => '01HZTEST',
        'token' => $token,
        'status' => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->customer())->toBe('01HCUSTOMER');
    expect($manager->graceDays())->toBe(14);
    expect($manager->fingerprint())->toBe('sha256:abc123');
});

it('returns null for seatsRemaining when token has no seats_used claim', function () {
    $this->store->write([
        'activation_uuid' => '01HZTEST',
        'token' => $this->factory->token(),
        'status' => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->seatsRemaining())->toBeNull();
});

it('computes seatsRemaining when token includes seats_used', function () {
    $this->store->write([
        'activation_uuid' => '01HZTEST',
        'token' => $this->factory->token(['seats' => 5, 'seats_used' => 3]),
        'status' => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->seatsRemaining())->toBe(2);
});

it('clamps seatsRemaining to zero when seats_used exceeds seats', function () {
    $this->store->write([
        'activation_uuid' => '01HZTEST',
        'token' => $this->factory->token(['seats' => 5, 'seats_used' => 9]),
        'status' => 'active',
        'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
    ]);

    $manager = new LicenseManager($this->store, $this->verifier, 7);

    expect($manager->seatsRemaining())->toBe(0);
});
