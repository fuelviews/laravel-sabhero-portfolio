<?php

namespace Fuelviews\SabHeroPortfolio\Filament\Resources\PortfolioResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Fuelviews\SabHeroPortfolio\Filament\Resources\PortfolioResource;

class CreatePortfolio extends CreateRecord
{
    protected static string $resource = PortfolioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
