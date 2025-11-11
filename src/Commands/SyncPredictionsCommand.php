<?php

namespace Bwise\BcoUkConnector\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncPredictionsCommand extends Command
{
    protected $signature = 'b-co-uk-connector:sync-predictions {--date= : The date to sync predictions for (Y-m-d format). Defaults to today}';

    protected $description = 'Sync predictions from prediction-module to b-co-uk-connector and save as HTML files';

    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $dateString = $date->format('Y-m-d');

        $this->info("Syncing predictions for date: {$dateString}");

        // Get predictions published on the specified date from source module (assumed 'predictions' in 'pgsql')
        $predictions = DB::connection('pgsql')
            ->table('predictions')
            ->whereDate('created_at', $dateString)
            ->get();

        if ($predictions->isEmpty()) {
            $this->warn("No predictions found for date: {$dateString}");

            return self::SUCCESS;
        }

        $this->info("Found {$predictions->count()} prediction(s) to sync");

        $syncedCount = 0;
        $failedCount = 0;

        foreach ($predictions as $sourcePrediction) {
            try {
                $this->line("Processing: {$sourcePrediction->title} (ID: {$sourcePrediction->id})");

                // Get the site via direct DB access (from 'sites' table in 'pgsql')
                $site = DB::connection('pgsql')
                    ->table('sites')
                    ->where('id', $sourcePrediction->site_id)
                    ->first();

                if (!$site) {
                    $this->warn("  → Site not found for prediction ID {$sourcePrediction->id}. Skipping.");
                    $failedCount++;
                    continue;
                }

                $baseCss = $site->base_css ?? '';
                $siteThemeCss = $site->style_tag ?? '';

                // Render full HTML using app.blade.php layout
                $fullHtml = view('b-co-uk-connector::layouts.app', [
                    'meta_title' => $sourcePrediction->meta_title ?? $sourcePrediction->title,
                    'meta_description' => $sourcePrediction->meta_description ?? '',
                    'modified_time' => isset($sourcePrediction->updated_at) ? Carbon::parse($sourcePrediction->updated_at)->toIso8601String() : '',
                    'content' => $sourcePrediction->content_html ?? '',
                    'base_css' => $baseCss,
                    'site_theme_css' => $siteThemeCss,
                ])->render();

                // Save HTML file
                $filename = $this->saveHtmlFile($sourcePrediction, $fullHtml);

                // Store in b-co-uk-connector database (assumes 'bco_uk_connector_predictions' table is in default connection)
                $this->storeInDatabase($sourcePrediction, $filename);

                $syncedCount++;
                $this->info("✓ Synced: {$sourcePrediction->slug}");
            } catch (\Exception $e) {
                $this->error("Failed to sync prediction ID {$sourcePrediction->id}: {$e->getMessage()}");
                $failedCount++;
            } finally {
                // Clear context after each prediction to avoid pollution
                Context::forget(['site_id', 'site']);
            }
        }

        $this->info("\nSync completed!");
        $this->info("Successfully synced: {$syncedCount}");
        if ($failedCount > 0) {
            $this->warn("Failed: {$failedCount}");
        }

        return self::SUCCESS;
    }

    // $sourcePrediction here is a \stdClass from DB (not a model)
    protected function saveHtmlFile($sourcePrediction, string $html): string
    {
        $storagePath = storage_path('app/public/predictions');

        // Ensure directory exists
        if (! File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        // Generate filename based on slug and locale
        $filename = "{$sourcePrediction->slug}.html";
        $filePath = "{$storagePath}/{$filename}";

        File::put($filePath, $html);

        $this->line("  → Saved HTML file: {$filename}");

        return $filename;
    }

    // $sourcePrediction is a \stdClass from DB
    protected function storeInDatabase($sourcePrediction, string $filename): void
    {
        // Check if prediction already exists in b_co_uk table
        $existingPrediction = DB::connection('b_co_uk')->table('predictions')
            ->where('slug', $sourcePrediction->slug)
            ->where('locale', $sourcePrediction->locale)
            ->first();

        $data = [
            'title' => $sourcePrediction->title,
            'slug' => $sourcePrediction->slug,
            'meta_title' => $sourcePrediction->meta_title,
            'meta_description' => $sourcePrediction->meta_description,
            'locale' => $sourcePrediction->locale,
            'content' => $filename, // Store the filename as content reference
            'status' => 'published',
            'match_timestamp' => $sourcePrediction->published_at,
            'updated_at' => now(),
        ];

        if ($existingPrediction) {
            DB::connection('b_co_uk')->table('predictions')
                ->where('id', $existingPrediction->id)
                ->update($data);
            $this->line("  → Updated database record");
        } else {
            $data['created_at'] = now();
            DB::connection('b_co_uk')->table('predictions')->insert($data);
            $this->line("  → Created database record");
        }
    }
}
