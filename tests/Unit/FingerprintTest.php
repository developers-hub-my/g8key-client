<?php

use G8Key\Client\Services\Fingerprint;

it('returns a sha256-prefixed hex string', function () {
    $fingerprint = Fingerprint::compute(['app_url' => 'https://example.test', 'hostname' => 'web-1']);

    expect($fingerprint)->toStartWith('sha256:');
    expect($fingerprint)->toMatch('/^sha256:[a-f0-9]{64}$/');
});

it('produces deterministic output regardless of input order', function () {
    $a = Fingerprint::compute(['app_url' => 'https://example.test', 'hostname' => 'web-1']);
    $b = Fingerprint::compute(['hostname' => 'web-1', 'app_url' => 'https://example.test']);

    expect($a)->toBe($b);
});

it('lowercases and trims attribute values', function () {
    $a = Fingerprint::compute(['app_url' => 'https://example.test', 'hostname' => 'WEB-1']);
    $b = Fingerprint::compute(['app_url' => 'https://example.test', 'hostname' => '  web-1  ']);

    expect($a)->toBe($b);
});

it('skips null and empty values', function () {
    $a = Fingerprint::compute(['app_url' => 'https://example.test', 'hostname' => 'web-1']);
    $b = Fingerprint::compute(['app_url' => 'https://example.test', 'hostname' => 'web-1', 'extra' => null, 'note' => '']);

    expect($a)->toBe($b);
});

it('honours an explicit override on resolve', function () {
    $resolved = Fingerprint::resolve('sha256:explicit-override');

    expect($resolved)->toBe('sha256:explicit-override');
});

it('honours a callable in config on resolve', function () {
    config()->set('g8key-client.fingerprint', fn () => 'sha256:from-config');

    expect(Fingerprint::resolve())->toBe('sha256:from-config');
});

it('matches the server FingerprintGenerator algorithm exactly', function () {
    // Mirror App\Services\G8Key\FingerprintGenerator inline so a future
    // server change (or a client regression) trips this test.
    $serverEquivalent = function (array $attributes): string {
        $normalised = [];

        foreach ($attributes as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $normalised[strtolower(trim((string) $key))] = is_string($value)
                ? strtolower(trim($value))
                : $value;
        }

        ksort($normalised);

        $payload = json_encode($normalised, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return 'sha256:'.hash('sha256', $payload);
    };

    $attrs = ['app_url' => 'https://example.test', 'hostname' => 'web-1'];

    expect(Fingerprint::compute($attrs))->toBe($serverEquivalent($attrs));
});
