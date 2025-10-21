<?php

namespace Fuelviews\SabHeroPortfolio\Actions;

use Filament\Notifications\Notification;
use Fuelviews\SabHeroPortfolio\Models\Portfolio;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader;
use RuntimeException;
use ZipArchive;

/**
 * Portfolio Import Action
 *
 * Handles importing portfolios from ZIP format containing CSV and images.
 * Supports cloud storage by downloading to temp, then uploading to media library.
 * Imports both before_image and after_image media collections.
 */
class PortfolioImportAction
{
    /**
     * Execute the import action
     *
     * @param  string  $zipFile  The uploaded ZIP file path on storage disk
     */
    public function execute(string $zipFile): void
    {
        $tempLocalZipPath = null;
        $extractPath = null;

        try {
            // Get Filament upload disk configuration
            $uploadDisk = config('filament.default_filesystem_disk', 'public');

            // Validate file exists
            if (! Storage::disk($uploadDisk)->exists($zipFile)) {
                $this->sendNotification('Import failed', 'Uploaded ZIP file not found.', 'danger');

                return;
            }

            // Download to local temp for processing
            [$tempLocalZipPath, $extractPath] = $this->downloadAndExtract($uploadDisk, $zipFile);

            // Find and validate CSV
            $csvFilePath = $this->findCsvFile($extractPath);

            if (! $csvFilePath) {
                // Delete only the original uploaded file from Filament disk
                if (Storage::disk($uploadDisk)->exists($zipFile)) {
                    Storage::disk($uploadDisk)->delete($zipFile);
                }

                $this->sendNotification('Import failed', 'No CSV file found in the extracted ZIP.', 'danger');

                return;
            }

            // Process CSV records
            $this->processRecords($csvFilePath, $extractPath);

            // Delete only the original uploaded file from Filament disk
            if (Storage::disk($uploadDisk)->exists($zipFile)) {
                Storage::disk($uploadDisk)->delete($zipFile);
            }

            // Success notification
            $this->sendNotification('Portfolios imported successfully!', null, 'success');

        } catch (\Exception $e) {
            $this->sendNotification('Import failed', 'An error occurred: '.$e->getMessage(), 'danger');
        }
    }

    /**
     * Download ZIP from storage and extract to local temp directory
     *
     * @return array{string, string} [$tempLocalZipPath, $extractPath]
     */
    protected function downloadAndExtract(string $uploadDisk, string $zipFile): array
    {
        $timestamp = time();
        $tempLocalZipPath = storage_path('app/sabhero-portfolio/temp/import_'.$timestamp.'.zip');
        $extractPath = storage_path('app/sabhero-portfolio/temp/import_extract_'.$timestamp);

        // Ensure temp directory exists
        if (! file_exists(dirname($tempLocalZipPath))) {
            mkdir(dirname($tempLocalZipPath), 0755, true);
        }

        // Download file to local temp storage
        $fileContents = Storage::disk($uploadDisk)->get($zipFile);
        file_put_contents($tempLocalZipPath, $fileContents);

        // Create extraction directory
        if (! file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        // Extract the ZIP file
        $zip = new ZipArchive;
        $openResult = $zip->open($tempLocalZipPath);

        if ($openResult === true) {
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            throw new RuntimeException("Failed to extract ZIP file (Error code: {$openResult}).");
        }

        return [$tempLocalZipPath, $extractPath];
    }

    /**
     * Find CSV file in extracted directory
     */
    protected function findCsvFile(string $extractPath): ?string
    {
        foreach (scandir($extractPath) as $file) {
            if (Str::endsWith($file, '.csv')) {
                return $extractPath.'/'.$file;
            }
        }

        return null;
    }

    /**
     * Process CSV records and import portfolios (oldest to newest)
     */
    protected function processRecords(string $csvFilePath, string $extractPath): void
    {
        $csv = Reader::createFromPath($csvFilePath, 'r');
        $csv->setHeaderOffset(0);

        // Load all records into an array
        $records = [];
        foreach ($csv->getRecords() as $record) {
            $records[] = $record;
        }

        // Sort by Created At timestamp (oldest to newest)
        usort($records, function ($a, $b) {
            // Parse dates with fallback to 0 for invalid/empty dates
            $dateA = isset($a['Created At']) && $a['Created At'] ? @strtotime($a['Created At']) : 0;
            $dateB = isset($b['Created At']) && $b['Created At'] ? @strtotime($b['Created At']) : 0;

            // Handle failed parsing
            if ($dateA === false) {
                $dateA = 0;
            }
            if ($dateB === false) {
                $dateB = 0;
            }

            return $dateA <=> $dateB; // Ascending order (oldest first)
        });

        // Import each record in order
        foreach ($records as $record) {
            $this->importPortfolio($record, $extractPath);
        }
    }

    /**
     * Import a single portfolio from CSV record
     */
    protected function importPortfolio(array $record, string $extractPath): void
    {
        $portfolio = Portfolio::updateOrCreate(
            ['id' => $record['ID']],
            [
                'title' => $record['Title'] ?? '',
                'description' => $record['Description'] ?? '',
                'type' => $record['Type'] ?? '',
                'spacing' => $record['Spacing'] ?? '',
                'order' => $record['Order'] ?? 0,
                'is_published' => ($record['Is Published'] ?? 'false') === 'true',
                'before_alt' => $record['Before Alt'] ?? '',
                'after_alt' => $record['After Alt'] ?? '',
                'created_at' => ! empty($record['Created At']) ? $record['Created At'] : null,
                'updated_at' => ! empty($record['Updated At']) ? $record['Updated At'] : null,
            ]
        );

        // Import before and after images
        $this->importImages($portfolio, $record, $extractPath);
    }

    /**
     * Import before_image and after_image for a portfolio
     */
    protected function importImages(Portfolio $portfolio, array $record, string $extractPath): void
    {
        // Import before image
        if (! empty($record['Before Image'])) {
            $beforeImageName = trim($record['Before Image']);
            $beforeImagePath = $extractPath.'/images/'.$beforeImageName;

            if (file_exists($beforeImagePath)) {
                // Clear existing before image
                $portfolio->clearMediaCollection('before_image');

                // Add new before image
                $portfolio->addMedia($beforeImagePath)
                    ->preservingOriginal() // Keep original file in temp directory
                    ->toMediaCollection('before_image');
            }
        }

        // Import after image
        if (! empty($record['After Image'])) {
            $afterImageName = trim($record['After Image']);
            $afterImagePath = $extractPath.'/images/'.$afterImageName;

            if (file_exists($afterImagePath)) {
                // Clear existing after image
                $portfolio->clearMediaCollection('after_image');

                // Add new after image
                $portfolio->addMedia($afterImagePath)
                    ->preservingOriginal() // Keep original file in temp directory
                    ->toMediaCollection('after_image');
            }
        }
    }

    /**
     * Send Filament notification
     */
    protected function sendNotification(string $title, ?string $body, string $type): void
    {
        $notification = Notification::make()->title($title);

        if ($body) {
            $notification->body($body);
        }

        match ($type) {
            'success' => $notification->success(),
            'danger' => $notification->danger(),
            default => $notification->info(),
        };

        $notification->send();
    }
}
