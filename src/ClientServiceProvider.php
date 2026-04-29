<?php

namespace G8Key\Client;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use G8Key\Client\Commands\ClientCommand;

class ClientServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('g8key-client')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_g8key_client_table')
            ->hasCommand(ClientCommand::class);
    }
}
