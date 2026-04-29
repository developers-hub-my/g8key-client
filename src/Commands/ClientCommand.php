<?php

namespace G8Key\Client\Commands;

use Illuminate\Console\Command;

class ClientCommand extends Command
{
    public $signature = 'g8key-client';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
