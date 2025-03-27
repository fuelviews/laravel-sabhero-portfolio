<?php

namespace Fuelviews\SabHeroPortfolio\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortfolios::route('/'),
            'create' => Pages\CreatePortfolio::route('/create'),
            'edit' => Pages\EditPortfolio::route('/{record}/edit'),
        ];
    }
}
