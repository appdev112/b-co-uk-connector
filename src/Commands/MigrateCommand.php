<?php

namespace Bwise\BcoUkConnector\Commands;

use Illuminate\Console\Command;

class MigrateCommand extends Command
{
    protected $signature = 'b-co-uk-connector:migrate {--fresh : Drop all tables and re-run all migrations} {--force : Force the operation to run when in production}';

    protected $description = 'Run the database migrations for b-co-uk-connector package on the b_co_uk connection';

    public function handle(): int
    {
        $migrationPath = __DIR__.'/../../database/migrations';

        if (! is_dir($migrationPath)) {
            $this->error('Migration directory not found: '.$migrationPath);

            return self::FAILURE;
        }

        // Get the path relative to base_path or use vendor path
        $basePath = base_path();
        $absolutePath = realpath($migrationPath);

        // Try to get relative path from base_path
        if ($absolutePath && str_starts_with($absolutePath, $basePath)) {
            $relativePath = str_replace($basePath.'/', '', $absolutePath);
        } else {
            // Fallback to vendor path format
            $relativePath = 'vendor/bwise-it-devs/b-co-uk-connector/database/migrations';
        }

        $this->info('Running b-co-uk-connector migrations on b_co_uk connection...');
        $this->line("Migration path: {$relativePath}");

        $options = [
            '--database' => 'b_co_uk',
            '--path' => $relativePath,
        ];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        try {
            if ($this->option('fresh')) {
                $this->call('migrate:fresh', $options);
            } else {
                $this->call('migrate', $options);
            }

            $this->info('b-co-uk-connector migrations completed!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Migration failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

