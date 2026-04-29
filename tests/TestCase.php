<?php

namespace G8Key\Client\Tests;

use G8Key\Client\G8KeyClientServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            G8KeyClientServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('g8key-client.audience', 'g8stack');
        $app['config']->set('g8key-client.api_base', 'https://g8key.test');
        $app['config']->set('g8key-client.api_timeout', 5);
        $app['config']->set('g8key-client.offline_grace_days', 7);
        $app['config']->set('g8key-client.cache_path', tempnam(sys_get_temp_dir(), 'g8key-test-').'.json');
    }
}
