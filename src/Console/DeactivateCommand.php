<?php

namespace G8Key\Client\Console;

use G8Key\Client\Exceptions\G8KeyClientException;
use G8Key\Client\Services\Deactivator;
use Illuminate\Console\Command;

class DeactivateCommand extends Command
{
    protected $signature = 'license:deactivate {--force : Skip the confirmation prompt}';

    protected $description = 'Release this host\'s activation back to the G8Key server and clear the local cache.';

    public function handle(Deactivator $deactivator): int
    {
        if (! $this->option('force') && ! $this->confirm('Deactivate this license? This frees the seat on the server.', false)) {
            $this->line('Aborted.');

            return self::SUCCESS;
        }

        try {
            $deactivator->deactivate();
        } catch (G8KeyClientException $e) {
            $this->error('Deactivation failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('License deactivated.');

        return self::SUCCESS;
    }
}
