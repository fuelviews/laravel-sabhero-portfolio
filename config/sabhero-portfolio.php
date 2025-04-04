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
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | This is the storage disk Filament will use to store files. You may use
    | any of the disks defined in the `config/filesystems.php`.
    |
    */

    'media_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),
];
