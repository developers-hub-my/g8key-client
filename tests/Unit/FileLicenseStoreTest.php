<?php

use G8Key\Client\Stores\FileLicenseStore;

beforeEach(function () {
    $this->path = tempnam(sys_get_temp_dir(), 'g8key-store-').'.json';
    @unlink($this->path);
    $this->store = new FileLicenseStore($this->path);
});

afterEach(function () {
    @unlink($this->path);
});

it('returns null when no cache file exists', function () {
    expect($this->store->read())->toBeNull();
    expect($this->store->exists())->toBeFalse();
});

it('writes and reads a cache record', function () {
    $record = [
        'activation_uuid'   => '01HZ...',
        'token'             => 'eyJ...',
        'status'            => 'active',
        'last_heartbeat_at' => '2026-04-29T10:00:00+00:00',
    ];

    $this->store->write($record);

    expect($this->store->exists())->toBeTrue();
    expect($this->store->read())->toBe($record);
});

it('writes the cache file with restrictive permissions', function () {
    $this->store->write([
        'activation_uuid'   => '01HZ...',
        'token'             => 'eyJ...',
        'status'            => 'active',
        'last_heartbeat_at' => '2026-04-29T10:00:00+00:00',
    ]);

    $perms = fileperms($this->path) & 0777;
    expect($perms)->toBe(0600);
})->skipOnWindows();

it('clears the cache file', function () {
    $this->store->write([
        'activation_uuid'   => '01HZ...',
        'token'             => 'eyJ...',
        'status'            => 'active',
        'last_heartbeat_at' => '2026-04-29T10:00:00+00:00',
    ]);

    $this->store->clear();

    expect($this->store->exists())->toBeFalse();
    expect($this->store->read())->toBeNull();
});

it('treats clear() as idempotent when no file exists', function () {
    $this->store->clear();
    $this->store->clear();

    expect($this->store->exists())->toBeFalse();
});

it('returns null when the cache file contains invalid JSON', function () {
    file_put_contents($this->path, '{not valid json');

    expect($this->store->read())->toBeNull();
});
