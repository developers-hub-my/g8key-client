<?php

namespace G8Key\Client\Console;

use G8Key\Client\LicenseManager;
use Illuminate\Console\Command;

class StatusCommand extends Command
{
    protected $signature = 'license:status';

    protected $description = 'Print the cached license status, tier, seats, features, and expiry.';

    public function handle(LicenseManager $manager): int
    {
        $status = $manager->status();

        $this->line('  Status:   '.$status);
        $this->line('  Activation: '.($manager->activationUuid() ?? '-'));
        $this->line('  Tier:     '.($manager->tier() ?? '-'));
        $this->line('  Seats:    '.($manager->seats() ?? '-'));

        $features = $manager->features();
        $this->line('  Features: '.($features === [] ? '-' : implode(', ', $features)));

        $expiresAt = $manager->expiresAt();
        $this->line('  Expires:  '.($expiresAt?->toDateTimeString() ?? '-'));

        return $manager->isValid() ? self::SUCCESS : self::FAILURE;
    }
}
