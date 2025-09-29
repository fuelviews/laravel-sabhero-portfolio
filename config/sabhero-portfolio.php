<?php

// config for Fuelviews/SabHeroPortfolio
return [
    /*
    |--------------------------------------------------------------------------
    | Portfolio Types
    |--------------------------------------------------------------------------
    |
    | Define your portfolio types here. Each type should have:
    | - key: The unique identifier for the type (used in database)
    | - label: The display name
    | - color: The color used in the Filament admin panel (supports Filament colors)
    |
    | The 'all' type is required and will always be included.
    |
    */

    'portfolio_types' => [
        'all' => [
            'label' => 'all',
            'color' => 'gray',
        ],
        'residential' => [
            'label' => 'residential',
            'color' => 'success',
        ],
        'commercial' => [
            'label' => 'commercial',
            'color' => 'info',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Configuration
    |--------------------------------------------------------------------------
    |
    | Configure media storage settings including the filesystem disk.
    | You may use any of the disks defined in `config/filesystems.php`.
    |
    */

    'media' => [
        'disk' => 'public',
    ],
];
