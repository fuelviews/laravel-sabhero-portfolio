<?php

namespace Fuelviews\SabHeroPortfolio;

use Filament\Contracts\Plugin;
use Filament\Panel;

class SabHeroPortfolio implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'sabhero-portfolio';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Filament\Resources\PortfolioResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
