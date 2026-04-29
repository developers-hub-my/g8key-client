<?php

namespace G8Key\Client\Console;

use G8Key\Client\Services\Activator;
use Illuminate\Console\Command;

class ActivateCommand extends Command
{
    protected $signature = 'license:activate {key : License key issued by the G8Key admin}';

    protected $description = 'Activate this host against the G8Key server using the supplied license key.';

    public function handle(Activator $activator): int
    {
        $key = (string) $this->argument('key');

        $result = $activator->activate($key);
        $payload = $result['payload'];

        $this->info('License activated.');
        $this->line('  Tier:    '.($payload['tier'] ?? '-'));
        $this->line('  Seats:   '.($payload['seats'] ?? '-'));

        if (isset($payload['exp'])) {
            $this->line('  Expires: '.date('Y-m-d H:i:s', (int) $payload['exp']));
        }

        if (! empty($payload['features']) && is_array($payload['features'])) {
            $this->line('  Features: '.implode(', ', $payload['features']));
        }

        return self::SUCCESS;
    }
}
