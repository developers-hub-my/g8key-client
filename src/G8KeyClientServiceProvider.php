<?php

namespace G8Key\Client;

use G8Key\Client\Console\ActivateCommand;
use G8Key\Client\Console\DeactivateCommand;
use G8Key\Client\Console\HeartbeatCommand;
use G8Key\Client\Console\StatusCommand;
use G8Key\Client\Contracts\LicenseStore;
use G8Key\Client\Contracts\TokenVerifier;
use G8Key\Client\Http\Middleware\RequireFeature;
use G8Key\Client\Http\Middleware\RequireLicense;
use G8Key\Client\Services\Activator;
use G8Key\Client\Services\Deactivator;
use G8Key\Client\Services\Heartbeat;
use G8Key\Client\Services\Verifier;
use G8Key\Client\Stores\DatabaseLicenseStore;
use G8Key\Client\Stores\FileLicenseStore;
use Illuminate\Contracts\Database\ConnectionResolverInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class G8KeyClientServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('g8key-client')
            ->hasConfigFile()
            ->hasMigration('create_g8key_licenses_table')
            ->hasCommands([
                ActivateCommand::class,
                HeartbeatCommand::class,
                DeactivateCommand::class,
                StatusCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(TokenVerifier::class, function ($app) {
            $keys = (array) $app['config']->get('g8key-client.public_keys', []);
            $aud  = (string) $app['config']->get('g8key-client.audience');

            return new Verifier($keys, $aud);
        });

        $this->app->singleton(LicenseStore::class, function ($app) {
            $driver = (string) $app['config']->get('g8key-client.store', 'file');

            if ($driver === 'database') {
                /** @var ConnectionResolverInterface $resolver */
                $resolver = $app->make('db');
                $table = (string) $app['config']->get('g8key-client.database_table', 'g8key_licenses');

                return new DatabaseLicenseStore($resolver->connection(), $table);
            }

            $path = (string) $app['config']->get('g8key-client.cache_path', storage_path('app/license.json'));

            return new FileLicenseStore($path);
        });

        $this->app->singleton(LicenseManager::class, function ($app) {
            return new LicenseManager(
                $app->make(LicenseStore::class),
                $app->make(TokenVerifier::class),
                (int) $app['config']->get('g8key-client.offline_grace_days', 7),
            );
        });

        $this->app->singleton(Activator::class, function ($app) {
            return new Activator(
                $app->make(HttpFactory::class),
                $app->make(TokenVerifier::class),
                $app->make(LicenseStore::class),
                (string) $app['config']->get('g8key-client.api_base'),
                (int) $app['config']->get('g8key-client.api_timeout', 10),
            );
        });

        $this->app->singleton(Heartbeat::class, function ($app) {
            return new Heartbeat(
                $app->make(HttpFactory::class),
                $app->make(TokenVerifier::class),
                $app->make(LicenseStore::class),
                (string) $app['config']->get('g8key-client.api_base'),
                (int) $app['config']->get('g8key-client.api_timeout', 10),
            );
        });

        $this->app->singleton(Deactivator::class, function ($app) {
            return new Deactivator(
                $app->make(HttpFactory::class),
                $app->make(LicenseStore::class),
                (string) $app['config']->get('g8key-client.api_base'),
                (int) $app['config']->get('g8key-client.api_timeout', 10),
            );
        });
    }

    public function bootingPackage(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('license', RequireLicense::class);
        $router->aliasMiddleware('license.feature', RequireFeature::class);

        Blade::if('licenseFeature', fn (string $feature) => $this->app->make(LicenseManager::class)->has($feature));
    }
}
