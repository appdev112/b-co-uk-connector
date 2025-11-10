<?php

namespace Bwise\BcoUkConnector;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BcoUkConnectorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('b-co-uk-connector')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_migration_table_name_table');
    }
}
