# SabHero Portfolio Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fuelviews/laravel-sabhero-portfolio.svg?style=flat-square)](https://packagist.org/packages/fuelviews/laravel-sabhero-portfolio)
[![Total Downloads](https://img.shields.io/packagist/dt/fuelviews/laravel-sabhero-portfolio.svg?style=flat-square)](https://packagist.org/packages/fuelviews/laravel-sabhero-portfolio)

A Laravel package for managing and displaying before/after image portfolios with a dynamic slider component. Perfect for renovation projects, home improvements, design transformations, or any visual comparison showcase.

## Features

- Filament admin panel integration for easy content management
- Before/After interactive slider component
- Customizable portfolio types with Filament UI integration
- Responsive image handling with transparent PNG support
- Configurable media storage disk support
- Livewire components for seamless frontend integration

## Installation

You can install the package via composer:

```bash
composer require fuelviews/laravel-sabhero-portfolio
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="sabhero-portfolio-migrations"
php artisan migrate
```

Optionally, you can seed the database with sample data:

```bash
php artisan db:seed --class="Fuelviews\\SabHeroPortfolio\\Database\\Seeders\\SabHeroPortfolioSeeder"
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="sabhero-portfolio-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="sabhero-portfolio-views"
```

## Usage

### Admin Panel

After installation, you'll find a new "Portfolio" section in your Filament admin panel where you can

1. Create, edit, and delete portfolio entries
2. Upload before and after images
3. Categorize entries by type
4. Control spacing and ordering
5. Publish/unpublish entries

### Frontend Display

You can display the before/after slider in your Blade views using Livewire:

For all portfolio types

```php
@livewire('sabhero-portfolio::before-after-slider')
```
Or

```bladehtml
<livewire:sabhero-portfolio::before-after-slider />
```

For specific portfolio types

```php
@livewire('sabhero-portfolio::before-after-slider', ['type' => 'commercial'])
```

Or

```bladehtml
<livewire:sabhero-portfolio::before-after-slider type="commercial" />
```

### Custom Portfolio Types

You can define custom portfolio types in the configuration file. Each type requires:
- A unique key (used in the database)
- A label (displayed in the UI, stored in lowercase)
- A color (used in the Filament admin panel)

### Custom Media Storage

By default, the package uses the disk configured in your Spatie Media Library config. You can override this in the package config

```php
// config/sabhero-portfolio.php
'media_disk' => 's3',
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Requirements

- PHP 8.3 or higher
- Laravel 10.0 or higher
- Filament 3.0 or higher
- Spatie Media Library 10.0 or higher

## Credits

- [Joshua Mitchener](https://github.com/thejmitchener)
- [Daniel Clark](https://github.com/sweatybreeze)
- [Fuelviews](https://github.com/fuelviews)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
 
