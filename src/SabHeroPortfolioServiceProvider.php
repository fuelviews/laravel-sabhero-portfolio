<?php

namespace Fuelviews\SabHeroPortfolio;

use Fuelviews\SabHeroPortfolio\Livewire\BeforeAfterSlider;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SabHeroPortfolioServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('sabhero-portfolio')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_sabhero_portfolio_table');
    }

    public function bootingPackage(): void
    {
        Livewire::component('sabhero-portfolio::before-after-slider', BeforeAfterSlider::class);
    }
}
