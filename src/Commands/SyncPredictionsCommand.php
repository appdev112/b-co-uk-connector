<?php

namespace Bwise\BcoUkConnector\Commands;

use App\Models\Prediction as SourcePrediction;
use App\Services\PredictionService;
use App\Services\SiteService;
use Bwise\BcoUkConnector\Models\Prediction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\File;

class SyncPredictionsCommand extends Command
{
    protected $signature = 'b-co-uk-connector:sync-predictions {--date= : The date to sync predictions for (Y-m-d format). Defaults to today}';

    protected $description = 'Sync predictions from prediction-module to b-co-uk-connector and save as HTML files';

    public function handle(): int
    {
        $date = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        $dateString = $date->format('Y-m-d');

        $this->info("Syncing predictions for date: {$dateString}");

        // Get predictions published on the specified date
        $predictions = SourcePrediction::query()
            //->whereDate('published_at', $dateString)
            //->where('status', \App\Enums\PredictionStatus::Published)
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

                // Set site context (similar to SetSiteContext middleware)
                $site = app(SiteService::class)->findById($sourcePrediction->site_id);
                Context::add('site_id', $sourcePrediction->site_id);
                Context::add('site', $site);

                // Get prediction page data
                $predictionPageData = app(PredictionService::class)->getDataForPredictionPage(
                    $sourcePrediction->site_id,
                    $sourcePrediction->locale,
                    $sourcePrediction->slug,
                    true
                );

                // Generate HTML body content
                $htmlBodyContent = view('predictions.show-plain', $predictionPageData)->render();

                // Render full HTML using app.blade.php layout
                $fullHtml = view('b-co-uk-connector::layouts.app', [
                    'meta_title' => $sourcePrediction->meta_title ?? $sourcePrediction->title,
                    'meta_description' => $sourcePrediction->meta_description ?? '',
                    'modified_time' => $sourcePrediction->updated_at?->toIso8601String() ?? '',
                    'content' => $htmlBodyContent,
                ])->render();

                // Save HTML file
                $filename = $this->saveHtmlFile($sourcePrediction, $fullHtml);

                // Store in b-co-uk-connector database
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

    protected function saveHtmlFile(SourcePrediction $prediction, string $html): string
    {
        $storagePath = storage_path('app/public/predictions');

        // Ensure directory exists
        if (! File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        // Generate filename based on slug and locale
        $filename = "{$prediction->slug}-{$prediction->locale}.html";
        $filePath = "{$storagePath}/{$filename}";

        File::put($filePath, $html);

        $this->line("  → Saved HTML file: {$filename}");

        return $filename;
    }

    protected function storeInDatabase(SourcePrediction $sourcePrediction, string $filename): void
    {
        // Check if prediction already exists
        $existingPrediction = Prediction::query()
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
        ];

        if ($existingPrediction) {
            $existingPrediction->update($data);
            $this->line("  → Updated database record");
        } else {
            Prediction::create($data);
            $this->line("  → Created database record");
        }
    }
}

