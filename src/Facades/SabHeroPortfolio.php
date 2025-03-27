<?php

namespace Fuelviews\SabHeroPortfolio\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Fuelviews\SabHeroPortfolio\SabHeroPortfolio
 */
class SabHeroPortfolio extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Fuelviews\SabHeroPortfolio\SabHeroPortfolio::class;
    }
}
