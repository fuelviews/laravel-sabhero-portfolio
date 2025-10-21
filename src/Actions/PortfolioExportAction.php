<?php

namespace Fuelviews\SabHeroPortfolio\Actions;

use Filament\Notifications\Notification;
use Fuelviews\SabHeroPortfolio\Models\Portfolio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use RuntimeException;
use ZipArchive;

/**
 * Portfolio Export Action
 *
 * Handles exporting portfolios to ZIP format with CSV and images.
 * Supports cloud storage (S3, etc.) by downloading files to temp storage.
 * Exports both before_image and after_image media collections.
 */
class PortfolioExportAction
{
    /**
     * Execute the export action
     *
     * @param  Collection<int, Portfolio>  $portfolios
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function execute(Collection $portfolios)
    {
        $tempWorkPath = null;
        $zipFilePath = null;

        try {
            // Create temp directories for export
            $timestamp = time();
            $tempWorkPath = storage_path('app/sabhero-portfolio/temp/export_zip_'.$timestamp);
            $tempMediaPath = $tempWorkPath.'/images';

            $this->createTempDirectory($tempMediaPath);

            // Generate descriptive filename and place ZIP outside work directory
            $exportFileName = $this->generateFilename($portfolios->count());
            $zipFilePath = storage_path('app/sabhero-portfolio/temp/'.$exportFileName);
            $csvFilePath = $tempWorkPath.'/portfolios.csv';

            // Create CSV with portfolio data
            $this->createCsv($csvFilePath, $portfolios, $tempMediaPath);

            // Create ZIP file
            $this->createZip($zipFilePath, $csvFilePath, $tempMediaPath);

            // Keep working directory for debugging/review (don't clean up)
            // Keep ZIP file as well (don't delete after send)

            // Download ZIP without deleting it
            return response()->download($zipFilePath);

        } catch (\Exception $e) {
            // Keep temp files even on error for debugging

            Notification::make()
                ->title('Export failed')
                ->body('An error occurred: '.$e->getMessage())
                ->danger()
                ->send();

            return back();
        }
    }

    /**
     * Create temporary directory for export
     */
    protected function createTempDirectory(string $path): void
    {
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Generate descriptive export filename
     *
     * Format: domain_count_portfolios_export_on_date_at_time.zip
     */
    protected function generateFilename(int $portfolioCount): string
    {
        $domain = str_replace(
            ['http://', 'https://', 'www.', '.', '-'],
            ['', '', '', '_', '_'],
            request()->getHost()
        );

        $estTime = now()->tz('America/New_York');
        $date = $estTime->format('Y_m_d');
        $time = strtolower($estTime->format('h_i_a'));

        return "{$domain}_{$portfolioCount}_portfolios_export_on_{$date}_at_{$time}.zip";
    }

    /**
     * Create CSV file with portfolio data and process media files
     */
    protected function createCsv(string $csvFilePath, Collection $portfolios, string $tempMediaPath): void
    {
        $csv = Writer::createFromPath($csvFilePath, 'w+');

        // Write CSV headers
        $csv->insertOne([
            'ID',
            'Title',
            'Description',
            'Type',
            'Spacing',
            'Order',
            'Is Published',
            'Before Alt',
            'After Alt',
            'Before Image',
            'After Image',
            'Created At',
            'Updated At',
        ]);

        // Write portfolio data and copy media files
        $mediaDisk = config('sabhero-portfolio.media.disk', 'public');

        foreach ($portfolios as $portfolio) {
            $beforeImage = $this->copyMediaFilesToTemp($portfolio, 'before_image', $tempMediaPath, $mediaDisk);
            $afterImage = $this->copyMediaFilesToTemp($portfolio, 'after_image', $tempMediaPath, $mediaDisk);

            $csv->insertOne([
                $portfolio->id ?? '',
                $portfolio->title ?? '',
                $portfolio->description ?? '',
                $portfolio->type ?? '',
                $portfolio->spacing ?? '',
                $portfolio->order ?? '',
                $portfolio->is_published ? 'true' : 'false',
                $portfolio->before_alt ?? '',
                $portfolio->after_alt ?? '',
                $beforeImage,
                $afterImage,
                $portfolio->created_at ? $portfolio->created_at->format('Y-m-d H:i:s') : '',
                $portfolio->updated_at ? $portfolio->updated_at->format('Y-m-d H:i:s') : '',
            ]);
        }
    }

    /**
     * Copy media files to temp directory for ZIP inclusion
     *
     * @param  string  $collection  Media collection name ('before_image' or 'after_image')
     * @return string Filename of the exported image
     */
    protected function copyMediaFilesToTemp(Portfolio $portfolio, string $collection, string $tempMediaPath, string $mediaDisk): string
    {
        $mediaFiles = $portfolio->getMedia($collection);

        if ($mediaFiles->isEmpty()) {
            return '';
        }

        $media = $mediaFiles->first();

        try {
            $mediaPath = $media->getPath();
            $prefix = $collection === 'before_image' ? 'before' : 'after';
            $exportFilename = "{$prefix}-{$portfolio->id}.{$media->extension}";

            // If using cloud storage, download to temp
            if (! file_exists($mediaPath)) {
                $diskPath = str_replace(Storage::disk($mediaDisk)->path(''), '', $mediaPath);

                if (Storage::disk($mediaDisk)->exists($diskPath)) {
                    $tempFilePath = $tempMediaPath.'/'.$exportFilename;
                    $fileContents = Storage::disk($mediaDisk)->get($diskPath);
                    file_put_contents($tempFilePath, $fileContents);
                    $mediaPath = $tempFilePath;
                }
            }

            // Copy local file to temp directory with standardized naming
            if (file_exists($mediaPath)) {
                $tempFilePath = $tempMediaPath.'/'.$exportFilename;
                if (! file_exists($tempFilePath)) {
                    copy($mediaPath, $tempFilePath);
                }
            }

            // Return filename if file exists
            if (file_exists($tempMediaPath.'/'.$exportFilename)) {
                return $exportFilename;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to export media file: '.$e->getMessage());
        }

        return '';
    }

    /**
     * Create ZIP file with CSV and images
     */
    protected function createZip(string $zipFilePath, string $csvFilePath, string $tempMediaPath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Failed to create ZIP file.');
        }

        // Add images to ZIP
        $imageFiles = glob($tempMediaPath.'/*');

        foreach ($imageFiles as $imageFile) {
            if (is_file($imageFile)) {
                $zip->addFile($imageFile, 'images/'.basename($imageFile));
            }
        }

        // Add CSV to ZIP
        $zip->addFile($csvFilePath, 'portfolios.csv');
        $zip->close();
    }
}
