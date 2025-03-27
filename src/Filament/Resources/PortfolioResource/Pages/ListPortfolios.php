<?php

namespace Fuelviews\SabHeroPortfolio\Filament\Resources\PortfolioResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Fuelviews\SabHeroPortfolio\Filament\Resources\PortfolioResource;

class ListPortfolios extends ListRecords
{
    protected static string $resource = PortfolioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
