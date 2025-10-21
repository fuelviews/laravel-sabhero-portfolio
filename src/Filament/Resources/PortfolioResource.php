<?php

namespace Fuelviews\SabHeroPortfolio\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Fuelviews\SabHeroPortfolio\Actions\PortfolioExportAction;
use Fuelviews\SabHeroPortfolio\Actions\PortfolioExportMigrationAction;
use Fuelviews\SabHeroPortfolio\Actions\PortfolioImportAction;
use Fuelviews\SabHeroPortfolio\Enums\PortfolioType;
use Fuelviews\SabHeroPortfolio\Filament\Resources\PortfolioResource\Pages;
use Fuelviews\SabHeroPortfolio\Models\Portfolio;

class PortfolioResource extends Resource
{
    protected static ?string $model = Portfolio::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Portfolio';

    protected static ?string $navigationLabel = 'Entries';

    public static function getNavigationBadge(): ?string
    {
        return (string) Portfolio::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                Portfolio::getForm(),
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(26),

                Tables\Columns\TextColumn::make('type')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $configTypes = PortfolioType::getTypes();
                        $label = $configTypes[$state]['label'] ?? $state;

                        // Capitalize each word in the label
                        return ucwords($label);
                    })
                    ->color(function ($state) {
                        $configTypes = PortfolioType::getTypes();

                        return $configTypes[$state]['color'] ?? 'gray';
                    }),

                Tables\Columns\TextColumn::make('order')
                    ->badge()
                    ->sortable(),

                Tables\Columns\SpatieMediaLibraryImageColumn::make('before_image')
                    ->collection('before_image')
                    ->conversion('thumbnail')
                    ->label('Before')
                    ->height(100)
                    ->extraAttributes(fn ($record) => [
                        'alt' => $record->getFirstMedia('before_image')?->getCustomProperty('alt'),
                    ]),

                Tables\Columns\SpatieMediaLibraryImageColumn::make('after_image')
                    ->collection('after_image')
                    ->conversion('thumbnail')
                    ->label('After')
                    ->height(100)
                    ->extraAttributes(fn ($record) => [
                        'alt' => $record->getFirstMedia('after_image')?->getCustomProperty('alt'),
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('spacing')
                    ->options([
                        'yes' => 'Top and Bottom',
                        'no' => 'No Spacing',
                        'top' => 'Top Only',
                        'bottom' => 'Bottom Only',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options(function () {
                        // Get all types and convert to simple key-value array format
                        $options = [];
                        $configTypes = PortfolioType::getTypes();

                        foreach ($configTypes as $key => $type) {
                            // Capitalize each word in the label
                            $options[$key] = ucwords($type['label']);
                        }

                        return $options;
                    }),
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->label('Import Portfolios')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->form([
                        Forms\Components\FileUpload::make('zip_file')
                            ->label('Zip files only')
                            ->acceptedFileTypes(['application/zip'])
                            ->required(),
                    ])
                    ->action(fn (array $data) => static::importFromZip($data['zip_file']))
                    ->requiresConfirmation(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export_csv_and_images')
                        ->label('Export Portfolios (csv)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(fn ($records) => static::exportToZip($records))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('export_migration')
                        ->label('Export Portfolios (migration)')
                        ->icon('heroicon-o-code-bracket')
                        ->color('info')
                        ->action(fn ($records) => static::exportMigration($records))
                        ->requiresConfirmation()
                        ->modalDescription('Export portfolios as a migration file package that can be copied to another project. Includes migration file, YAML files, images, and installation instructions.'),
                ]),
            ])
            ->defaultSort('order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Export portfolios to ZIP file with CSV and images
     *
     * Delegates to PortfolioExportAction for cleaner, testable code.
     */
    public static function exportToZip($records)
    {
        $exportAction = new PortfolioExportAction;

        return $exportAction->execute($records);
    }

    /**
     * Export portfolios as migration file package
     *
     * Delegates to PortfolioExportMigrationAction for cleaner, testable code.
     */
    public static function exportMigration($records)
    {
        $exportAction = new PortfolioExportMigrationAction;

        return $exportAction->execute($records);
    }

    /**
     * Import portfolios from ZIP file containing CSV and images
     *
     * Delegates to PortfolioImportAction for cleaner, testable code.
     */
    public static function importFromZip($zipFile)
    {
        $importAction = new PortfolioImportAction;
        $importAction->execute($zipFile);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortfolios::route('/'),
            'create' => Pages\CreatePortfolio::route('/create'),
            'edit' => Pages\EditPortfolio::route('/{record}/edit'),
        ];
    }
}
