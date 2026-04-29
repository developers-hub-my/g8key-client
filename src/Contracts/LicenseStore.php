<?php

namespace G8Key\Client\Contracts;

interface LicenseStore
{
    /**
     * Read the cached activation record.
     *
     * @return array{activation_uuid: string, token: string, status: string, last_heartbeat_at: string}|null
     */
    public function read(): ?array;

    /**
     * Persist an activation record.
     *
     * @param  array{activation_uuid: string, token: string, status: string, last_heartbeat_at: string}  $data
     */
    public function write(array $data): void;

    /** Remove the cached activation record. */
    public function clear(): void;

    /** Whether a cached activation record is present. */
    public function exists(): bool;
}
