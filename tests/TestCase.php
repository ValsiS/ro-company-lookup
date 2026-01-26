<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Valsis\RoCompanyLookup\RoCompanyLookupServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            \Spatie\LaravelData\LaravelDataServiceProvider::class,
            RoCompanyLookupServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'RoCompanyLookup' => \Valsis\RoCompanyLookup\Facades\RoCompanyLookup::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.timezone', 'Europe/Bucharest');
        $app['config']->set('ro-company-lookup.timezone', 'Europe/Bucharest');
        $app['config']->set('ro-company-lookup.language', 'ro');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('ro-company-lookup.cache_store', 'array');
        $app['config']->set('ro-company-lookup.anaf.retries', 1);
        $app['config']->set('ro-company-lookup.anaf.backoff_ms', 0);
    }
}
