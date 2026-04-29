<?php

namespace G8Key\Client\Console;

use G8Key\Client\Exceptions\LicenseRevokedException;
use G8Key\Client\Exceptions\LicenseSuspendedException;
use G8Key\Client\Services\Heartbeat;
use Illuminate\Console\Command;

class HeartbeatCommand extends Command
{
    protected $signature = 'license:heartbeat';

    protected $description = 'Refresh the cached license token by heartbeating the G8Key server.';

    public function handle(Heartbeat $heartbeat): int
    {
        try {
            $result = $heartbeat->pulse();
        } catch (LicenseRevokedException $e) {
            $this->error('License revoked: '.$e->getMessage());

            return self::FAILURE;
        } catch (LicenseSuspendedException $e) {
            $this->error('License suspended: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($result['status'] === 'offline') {
            $this->warn('Heartbeat could not reach the server; relying on offline grace.');

            return self::SUCCESS;
        }

        $this->info('Heartbeat OK.');

        return self::SUCCESS;
    }
}
