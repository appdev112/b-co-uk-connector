<?php

namespace Bwise\BcoUkConnector;

use Bwise\BcoUkConnector\Commands\MigrateCommand;
use Bwise\BcoUkConnector\Commands\SyncPredictionsCommand;
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
            ->hasCommand(MigrateCommand::class)
            ->hasCommand(SyncPredictionsCommand::class);
    }

    public function register(): void
    {
        parent::register();

        $this->registerDatabaseConnection();
    }

    public function boot(): void
    {
        parent::boot();
    }

    protected function registerDatabaseConnection(): void
    {
        $config = config('b-co-uk-connector');

        $connectionConfig = array_merge(
            $this->getDriverDefaults($config['db_connection'] ?? 'pgsql'),
            [
                'driver' => $config['db_connection'] ?? 'pgsql',
                'host' => $config['db_host'] ?? 'localhost',
                'port' => $config['db_port'] ?? '5432',
                'database' => $config['db_name'] ?? 'b_co_uk',
                'username' => $config['db_username'] ?? 'root',
                'password' => $config['db_password'] ?? 'root',
            ]
        );

        config(['database.connections.b_co_uk' => $connectionConfig]);
    }

    protected function getDriverDefaults(string $driver): array
    {
        return match ($driver) {
            'pgsql' => [
                'charset' => 'utf8',
                'search_path' => 'public',
                'sslmode' => 'prefer',
                'prefix' => '',
                'prefix_indexes' => true,
            ],
            default => [
                'prefix' => '',
                'prefix_indexes' => true,
            ],
        };
    }
}
