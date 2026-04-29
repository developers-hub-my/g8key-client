<?php

namespace G8Key\Client\Stores;

use G8Key\Client\Contracts\LicenseStore;
use Illuminate\Database\ConnectionInterface;

class DatabaseLicenseStore implements LicenseStore
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly string $table,
    ) {}

    public function read(): ?array
    {
        $row = $this->connection->table($this->table)->where('id', 1)->first();

        if ($row === null) {
            return null;
        }

        return [
            'activation_uuid'   => $row->activation_uuid,
            'token'             => $row->token,
            'status'            => $row->status,
            'last_heartbeat_at' => (string) $row->last_heartbeat_at,
        ];
    }

    public function write(array $data): void
    {
        $this->connection->table($this->table)->upsert(
            [
                array_merge(
                    ['id' => 1],
                    $data,
                    ['updated_at' => now(), 'created_at' => now()],
                ),
            ],
            uniqueBy: ['id'],
            update: ['activation_uuid', 'token', 'status', 'last_heartbeat_at', 'updated_at'],
        );
    }

    public function clear(): void
    {
        $this->connection->table($this->table)->where('id', 1)->delete();
    }

    public function exists(): bool
    {
        return $this->connection->table($this->table)->where('id', 1)->exists();
    }
}
