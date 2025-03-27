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
    | Media Storage Disk
    |--------------------------------------------------------------------------
    |
    | You can specify which disk to use for storing media files.
    | If not specified, it will use the default disk configured in the
    | Spatie Media Library config ('media-library.disk').
    |
    | Example values: 'public', 's3', 'cloudinary', etc.
    |
    */
    'media_disk' => null,
];
