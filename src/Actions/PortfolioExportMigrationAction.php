<?php

namespace Fuelviews\SabHeroPortfolio\Actions;

use Filament\Notifications\Notification;
use Fuelviews\SabHeroPortfolio\Models\Portfolio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Portfolio Export Migration Action
 *
 * Exports portfolios as a migration file package that can be copied to another project.
 * Includes migration file, YAML files with portfolio data, images, and installation instructions.
 */
class PortfolioExportMigrationAction
{
    /**
     * Execute the export action
     *
     * @param  Collection<int, Portfolio>  $portfolios
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function execute(Collection $portfolios)
    {
        try {
            // Create export directory structure
            $timestamp = date('Y_m_d_His');
            $exportDir = storage_path("app/sabhero-portfolio/temp/portfolios-migration-export-{$timestamp}");
            $portfoliosDir = "{$exportDir}/portfolio";
            $markdownDir = "{$portfoliosDir}/markdown";
            $imagesDir = "{$exportDir}/images";

            File::makeDirectory($exportDir, 0755, true);
            File::makeDirectory($markdownDir, 0755, true);
            File::makeDirectory($imagesDir, 0755, true);

            // Export markdown files with portfolio data
            $this->exportMarkdown($portfolios, $markdownDir);

            // Export portfolio images (before and after)
            $this->exportImages($portfolios, $imagesDir);

            // Generate migration file
            $migrationContent = $this->generateMigration($portfolios);
            File::put("{$exportDir}/{$timestamp}_populate_exported_portfolios.php", $migrationContent);

            // Create README
            $readmeContent = $this->generateReadme($timestamp, $portfolios->count());
            File::put("{$exportDir}/README.md", $readmeContent);

            // Create ZIP file with descriptive naming
            $domain = str_replace(['http://', 'https://', 'www.', '.', '-'], ['', '', '', '_', '_'], request()->getHost());
            $portfolioCount = $portfolios->count();
            $estTime = now()->tz('America/New_York');
            $date = $estTime->format('Y_m_d');
            $time = strtolower($estTime->format('h_i_a'));
            $exportFileName = "{$domain}_{$portfolioCount}_portfolios_migration_export_on_{$date}_at_{$time}.zip";
            $zipFilePath = storage_path("app/sabhero-portfolio/{$exportFileName}");
            $this->createZip($zipFilePath, $exportDir);

            Notification::make()
                ->title('Portfolios migration exported successfully')
                ->body("Exported {$portfolios->count()} portfolios with YAML files and images")
                ->success()
                ->send();

            return response()->download($zipFilePath);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Export failed')
                ->body('An error occurred: '.$e->getMessage())
                ->danger()
                ->send();

            return back();
        }
    }

    /**
     * Export portfolio before and after images
     */
    protected function exportImages(Collection $portfolios, string $imagesDir): void
    {
        $exportedCount = 0;
        $skippedCount = 0;
        $mediaDisk = config('sabhero-portfolio.media.disk', 'public');

        foreach ($portfolios as $portfolio) {
            // Export before image
            $beforeMedia = $portfolio->getFirstMedia('before_image');

            if ($beforeMedia) {
                try {
                    $filename = "before-{$portfolio->id}.{$beforeMedia->extension}";
                    $targetPath = "{$imagesDir}/{$filename}";
                    $mediaPath = $beforeMedia->getPath();

                    // If using cloud storage, download to temp
                    if (! file_exists($mediaPath)) {
                        $diskPath = str_replace(Storage::disk($mediaDisk)->path(''), '', $mediaPath);

                        if (Storage::disk($mediaDisk)->exists($diskPath)) {
                            $fileContents = Storage::disk($mediaDisk)->get($diskPath);
                            file_put_contents($targetPath, $fileContents);
                            $exportedCount++;

                            goto afterImageExport; // Move to after image
                        }
                    }

                    // Copy local file to export directory
                    if (file_exists($mediaPath)) {
                        copy($mediaPath, $targetPath);
                        $exportedCount++;
                    } else {
                        Log::warning("Before image file not found for portfolio: {$portfolio->title}", [
                            'portfolio_id' => $portfolio->id,
                            'media_id' => $beforeMedia->id,
                            'path' => $mediaPath,
                        ]);
                        $skippedCount++;
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to export before image for portfolio: {$portfolio->title}", [
                        'portfolio_id' => $portfolio->id,
                        'error' => $e->getMessage(),
                    ]);
                    $skippedCount++;
                }
            }

            afterImageExport:
            // Export after image
            $afterMedia = $portfolio->getFirstMedia('after_image');

            if ($afterMedia) {
                try {
                    $filename = "after-{$portfolio->id}.{$afterMedia->extension}";
                    $targetPath = "{$imagesDir}/{$filename}";
                    $mediaPath = $afterMedia->getPath();

                    // If using cloud storage, download to temp
                    if (! file_exists($mediaPath)) {
                        $diskPath = str_replace(Storage::disk($mediaDisk)->path(''), '', $mediaPath);

                        if (Storage::disk($mediaDisk)->exists($diskPath)) {
                            $fileContents = Storage::disk($mediaDisk)->get($diskPath);
                            file_put_contents($targetPath, $fileContents);
                            $exportedCount++;

                            continue;
                        }
                    }

                    // Copy local file to export directory
                    if (file_exists($mediaPath)) {
                        copy($mediaPath, $targetPath);
                        $exportedCount++;
                    } else {
                        Log::warning("After image file not found for portfolio: {$portfolio->title}", [
                            'portfolio_id' => $portfolio->id,
                            'media_id' => $afterMedia->id,
                            'path' => $mediaPath,
                        ]);
                        $skippedCount++;
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to export after image for portfolio: {$portfolio->title}", [
                        'portfolio_id' => $portfolio->id,
                        'error' => $e->getMessage(),
                    ]);
                    $skippedCount++;
                }
            }
        }

        Log::info("Image export complete", [
            'total_exported' => $exportedCount,
            'skipped' => $skippedCount,
        ]);
    }

    /**
     * Export portfolio data as markdown files with YAML frontmatter
     */
    protected function exportMarkdown(Collection $portfolios, string $markdownDir): void
    {
        $exportedCount = 0;
        $exportOrder = 0;

        foreach ($portfolios as $portfolio) {
            try {
                $exportOrder++;
                $filename = "portfolio-{$portfolio->id}.md";
                $filePath = "{$markdownDir}/{$filename}";

                // Build frontmatter (description will be in body, not frontmatter)
                $frontmatter = [
                    'export_order' => $exportOrder,
                    'id' => $portfolio->id,
                    'title' => $portfolio->title,
                    'type' => $portfolio->type,
                    'spacing' => $portfolio->spacing,
                    'order' => $portfolio->order,
                    'is_published' => $portfolio->is_published,
                    'before_alt' => $portfolio->before_alt,
                    'after_alt' => $portfolio->after_alt,
                    'created_at' => $portfolio->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $portfolio->updated_at?->format('Y-m-d H:i:s'),
                ];

                // Get before and after image filenames if exist
                $beforeMedia = $portfolio->getFirstMedia('before_image');
                if ($beforeMedia) {
                    $frontmatter['before_image'] = "before-{$portfolio->id}.{$beforeMedia->extension}";
                }

                $afterMedia = $portfolio->getFirstMedia('after_image');
                if ($afterMedia) {
                    $frontmatter['after_image'] = "after-{$portfolio->id}.{$afterMedia->extension}";
                }

                // Build markdown content with YAML frontmatter
                $content = "---\n";
                foreach ($frontmatter as $key => $value) {
                    if ($value === null) {
                        continue;
                    }

                    if (is_bool($value)) {
                        $content .= "{$key}: ".($value ? 'true' : 'false')."\n";
                    } elseif (is_int($value)) {
                        $content .= "{$key}: {$value}\n";
                    } else {
                        // Proper YAML string escaping for scalar values
                        $escapedValue = str_replace(["\\", '"'], ["\\\\", '\\"'], $value);
                        $content .= "{$key}: \"{$escapedValue}\"\n";
                    }
                }
                $content .= "---\n\n";

                // Add description as markdown body
                $content .= $portfolio->description ?? '';

                // Write markdown file
                File::put($filePath, $content);
                $exportedCount++;

            } catch (\Exception $e) {
                Log::warning("Failed to export markdown for portfolio: {$portfolio->title}", [
                    'portfolio_id' => $portfolio->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Markdown export complete", [
            'exported' => $exportedCount,
            'total_portfolios' => $portfolios->count(),
        ]);
    }

    /**
     * Generate migration file content
     */
    protected function generateMigration(Collection $portfolios): string
    {
        $portfolioCount = $portfolios->count();

        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Seeds portfolios exported from another installation.
     * Markdown files should be placed in database/portfolio/markdown/
     * Images should be placed in public/images/
     *
     * This migration will:
     * - Read all .md files from database/portfolio/markdown/
     * - Parse YAML frontmatter for portfolio metadata
     * - Extract description from markdown body
     * - Create portfolios with all content and metadata
     * - Attach before_image and after_image from public/images/
     * - Skip portfolios that already exist (by id)
     */
    public function up(): void
    {
        $tablePrefix = '';
        $portfoliosTable = $tablePrefix . 'portfolios';

        echo "\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "  Importing Portfolios from Markdown Files\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "\n";

        $markdownPath = database_path('portfolio/markdown');

        // Get all markdown files
        $markdownFiles = glob($markdownPath . '/*.md');

        if (empty($markdownFiles)) {
            echo "\n";
            echo "✗ No markdown files found in {$markdownPath}\n";
            echo "\n";
            Log::error('No markdown files found for portfolio import', [
                'path' => $markdownPath,
            ]);
            return;
        }

        // Parse all markdown files and extract export_order for sorting
        $portfoliosToImport = [];
        foreach ($markdownFiles as $markdownFile) {
            $portfolioData = $this->parseMarkdownFile($markdownFile);
            if ($portfolioData) {
                $portfoliosToImport[] = [
                    'file' => $markdownFile,
                    'data' => $portfolioData,
                    'order' => $portfolioData['export_order'] ?? 9999,
                ];
            }
        }

        // Sort by export_order in descending order (highest/newest first)
        usort($portfoliosToImport, function($a, $b) {
            return $b['order'] <=> $a['order'];
        });

        echo "  ℹ Portfolios will be imported in reverse order (newest first)\n";
        echo "\n";

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($portfoliosToImport as $portfolioItem) {
            $markdownFile = $portfolioItem['file'];
            $portfolioData = $portfolioItem['data'];

            try {
                // ─────────────────────────────────────────────────────────
                // Step 1: Check if portfolio already exists
                // ─────────────────────────────────────────────────────────
                $existingPortfolio = DB::table($portfoliosTable)->where('id', $portfolioData['id'])->first();
                if ($existingPortfolio) {
                    echo "  ℹ Skipping '{$portfolioData['title']}' (ID: {$portfolioData['id']}) - already exists\n";
                    $skipped++;
                    continue;
                }

                // ─────────────────────────────────────────────────────────
                // Step 2: Create portfolio (description from markdown body)
                // ─────────────────────────────────────────────────────────
                $portfolioId = DB::table($portfoliosTable)->insertGetId([
                    'id' => $portfolioData['id'],
                    'title' => $portfolioData['title'],
                    'description' => $portfolioData['body'] ?? null,
                    'type' => $portfolioData['type'] ?? null,
                    'spacing' => $portfolioData['spacing'] ?? null,
                    'order' => $portfolioData['order'] ?? 0,
                    'is_published' => $portfolioData['is_published'] ?? false,
                    'before_alt' => $portfolioData['before_alt'] ?? null,
                    'after_alt' => $portfolioData['after_alt'] ?? null,
                    'created_at' => $portfolioData['created_at'] ?? now(),
                    'updated_at' => $portfolioData['updated_at'] ?? now(),
                ]);

                // ─────────────────────────────────────────────────────────
                // Step 3: Attach before image if exists
                // ─────────────────────────────────────────────────────────
                if (!empty($portfolioData['before_image'])) {
                    $imagePath = public_path('images/' . $portfolioData['before_image']);

                    if (file_exists($imagePath)) {
                        try {
                            $portfolio = \Fuelviews\SabHeroPortfolio\Models\Portfolio::find($portfolioId);

                            if ($portfolio) {
                                $mediaDisk = config('sabhero-portfolio.media.disk', 'public');
                                $portfolio->addMedia($imagePath)
                                    ->preservingOriginal()
                                    ->withResponsiveImages()
                                    ->toMediaCollection('before_image', $mediaDisk);
                            }
                        } catch (\Exception $e) {
                            Log::warning("Failed to attach before image for portfolio: {$portfolioData['title']}", [
                                'portfolio_id' => $portfolioId,
                                'image_path' => $imagePath,
                                'error' => $e->getMessage(),
                            ]);
                            echo "  ⚠ Failed to attach before image: {$portfolioData['before_image']}\n";
                        }
                    } else {
                        Log::warning("Before image file not found for portfolio: {$portfolioData['title']}", [
                            'portfolio_id' => $portfolioId,
                            'expected_path' => $imagePath,
                        ]);
                        echo "  ⚠ Before image not found: {$portfolioData['before_image']}\n";
                    }
                }

                // ─────────────────────────────────────────────────────────
                // Step 4: Attach after image if exists
                // ─────────────────────────────────────────────────────────
                if (!empty($portfolioData['after_image'])) {
                    $imagePath = public_path('images/' . $portfolioData['after_image']);

                    if (file_exists($imagePath)) {
                        try {
                            $portfolio = \Fuelviews\SabHeroPortfolio\Models\Portfolio::find($portfolioId);

                            if ($portfolio) {
                                $mediaDisk = config('sabhero-portfolio.media.disk', 'public');
                                $portfolio->addMedia($imagePath)
                                    ->preservingOriginal()
                                    ->withResponsiveImages()
                                    ->toMediaCollection('after_image', $mediaDisk);
                            }
                        } catch (\Exception $e) {
                            Log::warning("Failed to attach after image for portfolio: {$portfolioData['title']}", [
                                'portfolio_id' => $portfolioId,
                                'image_path' => $imagePath,
                                'error' => $e->getMessage(),
                            ]);
                            echo "  ⚠ Failed to attach after image: {$portfolioData['after_image']}\n";
                        }
                    } else {
                        Log::warning("After image file not found for portfolio: {$portfolioData['title']}", [
                            'portfolio_id' => $portfolioId,
                            'expected_path' => $imagePath,
                        ]);
                        echo "  ⚠ After image not found: {$portfolioData['after_image']}\n";
                    }
                }

                echo "  ✓ Imported: {$portfolioData['title']}\n";
                $imported++;

            } catch (\Exception $e) {
                Log::error("Failed to import portfolio from: " . basename($markdownFile), [
                    'file' => $markdownFile,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                echo "  ✗ Error importing '" . basename($markdownFile) . "': {$e->getMessage()}\n";
                $errors++;
            }
        }

        echo "\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "  Import Complete\n";
        echo "────────────────────────────────────────────────────────────────\n";
        echo "  ✓ Imported: {$imported}\n";
        echo "  ℹ Skipped:  {$skipped}\n";
        echo "  ✗ Errors:   {$errors}\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "\n";

        Log::info('Portfolio migration import completed', [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    /**
     * Parse markdown file with YAML frontmatter
     */
    protected function parseMarkdownFile(string $filePath): ?array
    {
        try {
            $content = file_get_contents($filePath);

            // Check for frontmatter
            if (!preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
                Log::warning("No frontmatter found in markdown file", [
                    'file' => $filePath,
                ]);
                return null;
            }

            $frontmatterYaml = $matches[1];
            $body = trim($matches[2]);

            // Parse YAML frontmatter
            $frontmatter = Yaml::parse($frontmatterYaml);

            if (!isset($frontmatter['id']) || !isset($frontmatter['title'])) {
                Log::warning("Missing required fields (id/title) in frontmatter", [
                    'file' => $filePath,
                ]);
                return null;
            }

            return array_merge($frontmatter, ['body' => $body]);

        } catch (\Exception $e) {
            Log::error("Failed to parse markdown file", [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This will delete all portfolios imported by this migration.
     * Portfolios are identified by reading markdown files and matching IDs.
     */
    public function down(): void
    {
        $markdownPath = database_path('portfolio/markdown');
        $markdownFiles = glob($markdownPath . '/*.md');

        if (empty($markdownFiles)) {
            echo "\n";
            echo "✗ No markdown files found - cannot determine which portfolios to delete\n";
            echo "\n";
            return;
        }

        echo "\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "  Rolling Back Portfolio Migration Import\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "\n";

        $deleted = 0;

        foreach ($markdownFiles as $markdownFile) {
            try {
                $portfolioData = $this->parseMarkdownFile($markdownFile);

                if (!$portfolioData || empty($portfolioData['id'])) {
                    continue;
                }

                $id = $portfolioData['id'];
                $portfolio = \Fuelviews\SabHeroPortfolio\Models\Portfolio::find($id);

                if ($portfolio) {
                    // Delete media files
                    $portfolio->clearMediaCollection('before_image');
                    $portfolio->clearMediaCollection('after_image');

                    // Delete portfolio
                    $portfolio->delete();

                    echo "  ✓ Deleted: {$portfolioData['title']} (ID: {$id})\n";
                    $deleted++;

                    Log::info("Rolled back portfolio: {$portfolioData['title']}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to rollback portfolio from: " . basename($markdownFile), [
                    'error' => $e->getMessage(),
                ]);
                echo "  ✗ Error deleting from '" . basename($markdownFile) . "': {$e->getMessage()}\n";
            }
        }

        echo "\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "  Rollback Complete - Deleted {$deleted} portfolios\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "\n";

        Log::info('Portfolio migration rollback completed', [
            'deleted' => $deleted,
        ]);
    }
};
PHP;
    }

    /**
     * Generate README file content
     */
    protected function generateReadme(string $timestamp, int $portfolioCount): string
    {
        return <<<MD
# Portfolios Migration Export

This package contains {$portfolioCount} exported portfolio entries from SabHero Portfolio that can be imported into another Laravel project.

## Contents

- `{$timestamp}_populate_exported_portfolios.php` - Migration file that reads and processes markdown files
- `portfolio/markdown/` - Directory containing {$portfolioCount} markdown files with YAML frontmatter
- `images/` - Directory containing before/after portfolio images
- `README.md` - This file

## Installation Instructions

### Step 1: Copy Files

1. Copy the migration file to your project's `database/migrations/` directory:
   ```bash
   cp {$timestamp}_populate_exported_portfolios.php /path/to/your/project/database/migrations/
   ```

2. Copy the portfolio directory (with markdown files) to your project's database directory:
   ```bash
   cp -r portfolio /path/to/your/project/database/
   ```

3. Copy the images to your project's public directory:
   ```bash
   cp -r images /path/to/your/project/public/
   ```

### Step 2: Run Migration

Run the migration to import the portfolios:

```bash
php artisan migrate
```

The migration will:
- Scan `database/portfolio/markdown/` for all .md files
- Parse YAML frontmatter for portfolio metadata
- Extract description from markdown body
- Create portfolios with all content and metadata
- Attach before_image and after_image from `public/images/` directory
- Skip any portfolios that already exist (based on ID)
- Show detailed progress with console output
- Log all operations to Laravel logs for troubleshooting
- Track and report imported, skipped, and error counts

### Step 3: Verify

Check your portfolios in Filament:

```bash
php artisan serve
```

Navigate to your Filament admin panel and verify the portfolios were imported correctly.

### Step 4: Review Logs (Recommended)

Check Laravel logs for any warnings or errors during import:

```bash
tail -f storage/logs/laravel.log
```

The migration logs:
- YAML parsing results
- Image import warnings
- Any errors that occurred during import

### Step 5: Cleanup (Optional)

After successful import, you can optionally remove the markdown files if you no longer need them:

```bash
rm -rf database/portfolio/markdown/
```

**Note:** Images in `public/images/` should remain as they are actively used by your portfolios.

## Features

### Production-Ready Migration
- **No embedded data** - All portfolio data lives in markdown files
- **Flexible editing** - Edit portfolios directly in .md files before importing
- **Comprehensive error handling** - Try/catch blocks around all operations
- **Detailed logging** - All operations logged to Laravel logs
- **Progress tracking** - Real-time console output with import statistics
- **Reversible** - Includes `down()` method to rollback the import
- **Idempotent** - Safe to run multiple times (skips duplicates)

### Data Integrity
- Portfolio data stored in markdown files with YAML frontmatter for easy editing
- Description stored in markdown body for readability
- Portfolios are matched by ID to prevent duplicates
- All portfolio metadata preserved (type, spacing, order, is_published, etc.)
- Images stored in public directory for direct web access
- Images processed through Spatie Media Library with responsive images
- Uses your configured media disk from `config/sabhero-portfolio.php`

### Markdown File Format
Each portfolio is exported as a markdown file with YAML frontmatter:

```markdown
---
export_order: 1
id: 123
title: "Kitchen Renovation"
type: "residential"
spacing: "yes"
order: 1
is_published: true
before_alt: "Old kitchen before renovation"
after_alt: "Modern kitchen after renovation"
before_image: "before-123.jpg"
after_image: "after-123.jpg"
created_at: "2024-01-15 10:00:00"
updated_at: "2024-01-15 10:05:00"
---

Complete modern kitchen remodel with custom cabinets
```

**Note:** The `export_order` field determines import order. Portfolios are imported in **reverse order** (highest export_order first, lowest last), so the newest exported portfolios are imported first.

### Import Summary
After migration completes, you'll see a summary like:
```
════════════════════════════════════════════════════════════════
  Import Complete
────────────────────────────────────────────────────────────────
  ✓ Imported: 25
  ℹ Skipped:  2
  ✗ Errors:   0
════════════════════════════════════════════════════════════════
```

## Rollback

To reverse the migration and remove all imported portfolios:

```bash
php artisan migrate:rollback
```

**WARNING:** This will permanently delete all portfolios imported by this migration, along with their media files. The migration reads the markdown files to determine which portfolios to delete.

## Troubleshooting

### Markdown files not found
- Ensure markdown files were copied to `database/portfolio/markdown/`
- Check that markdown filenames end with `.md`
- Verify file permissions on the markdown directory (should be readable)
- Check console output during migration for file reading errors
- Review Laravel logs for "No markdown files found" errors

### Frontmatter parsing errors
- Ensure frontmatter is enclosed in `---` markers
- Verify YAML syntax is valid in frontmatter (use a YAML validator)
- Check that `id` and `title` fields are present
- Ensure boolean values use `true`/`false` (not `yes`/`no`)
- Review Laravel logs for "Failed to parse markdown file" errors

### Images not appearing
- Ensure images were copied to `public/images/`
- Check file permissions on the images directory (should be readable by web server)
- Verify images are web-accessible at `http://yoursite.com/images/filename.jpg`
- Verify your media disk configuration in `config/sabhero-portfolio.php`
- Check console output during migration for image warnings
- Review Laravel logs for detailed error messages

### Portfolios not importing
- Check that you've run `php artisan migrate` for the SabHero Portfolio package first
- Ensure the portfolios table exists
- Ensure markdown files are in `database/portfolio/markdown/`
- Check Laravel logs (`storage/logs/laravel.log`) for detailed errors
- Verify database connection and permissions

## Technical Details

### Migration Structure
The migration uses a structured approach:

**Per Portfolio:**
1. **Parse markdown** - Read and parse .md file with YAML frontmatter
2. **Extract description** - Get description from markdown body
3. **Duplicate detection** - Skip portfolios that already exist
4. **Portfolio creation** - Insert portfolio with all metadata
5. **Before image processing** - Attach before_image from public/images/
6. **After image processing** - Attach after_image from public/images/

### Error Handling
All operations are wrapped in try/catch blocks with:
- Detailed error logging to Laravel logs
- Console output for immediate visibility
- Graceful degradation (continues on errors)
- Final summary with error counts

### Logging
The migration logs to Laravel's standard log file:
- **Info**: Successful operations (completed import)
- **Warning**: Non-critical issues (YAML errors, image missing)
- **Error**: Critical failures (parsing failed, import failed)

### Dependencies
This migration requires:
- `symfony/yaml` package for YAML parsing
- Spatie Media Library for image handling
- SabHero Portfolio package installed and migrated

## Generated

Exported on: {$this->formatTimestamp()}
From: {$this->getDomain()}
MD;
    }

    /**
     * Create ZIP file from directory
     * Uses explicit file listing to avoid symlink resolution issues (Laravel Forge)
     */
    protected function createZip(string $zipFilePath, string $sourceDir): void
    {
        $zip = new \ZipArchive;

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create ZIP file');
        }

        // Add migration file at root (find the .php file)
        $migrationFiles = glob($sourceDir.'/*.php');
        foreach ($migrationFiles as $migrationFile) {
            if (is_file($migrationFile)) {
                $zip->addFile($migrationFile, basename($migrationFile));
            }
        }

        // Add README.md at root
        $readmeFile = $sourceDir.'/README.md';
        if (file_exists($readmeFile)) {
            $zip->addFile($readmeFile, 'README.md');
        }

        // Add markdown files in portfolio/markdown/
        $markdownFiles = glob($sourceDir.'/portfolio/markdown/*.md');
        foreach ($markdownFiles as $markdownFile) {
            if (is_file($markdownFile)) {
                $zip->addFile($markdownFile, 'portfolio/markdown/'.basename($markdownFile));
            }
        }

        // Add images in images/
        $imageFiles = glob($sourceDir.'/images/*');
        foreach ($imageFiles as $imageFile) {
            if (is_file($imageFile)) {
                $zip->addFile($imageFile, 'images/'.basename($imageFile));
            }
        }

        $zip->close();
    }

    /**
     * Format current timestamp
     */
    protected function formatTimestamp(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    /**
     * Get domain name
     */
    protected function getDomain(): string
    {
        return request()->getHost();
    }
}
