<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Valsis\RoCompanyLookup\Console\CheckCompanyCommand;

class RoCompanyLookupServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ro-company-lookup')
            ->hasConfigFile('ro-company-lookup')
            ->hasCommand(CheckCompanyCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RoCompanyLookupManager::class, function ($app) {
            return new RoCompanyLookupManager($app);
        });

        $this->app->alias(RoCompanyLookupManager::class, 'ro-company-lookup');
    }

    public function packageBooted(): void
    {
        $this->publishes([
            __DIR__.'/../config/ro-company-lookup.php' => config_path('ro-company-lookup.php'),
        ], 'ro-company-lookup-config');
    }
}
