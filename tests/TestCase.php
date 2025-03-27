<?php

namespace Fuelviews\SabHeroPortfolio\Tests;

use Fuelviews\SabHeroPortfolio\SabHeroPortfolioServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Fuelviews\\SabHeroPortfolio\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            SabHeroPortfolioServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Set config values for the package
        config()->set('sabhero-portfolio.portfolio_types', [
            'all' => [
                'label' => 'all',
                'color' => 'gray',
            ],
            'residential' => [
                'label' => 'residential',
                'color' => 'blue',
            ],
            'commercial' => [
                'label' => 'commercial',
                'color' => 'green',
            ],
        ]);
    }
}
