<?php

namespace Fuelviews\SabHeroPortfolio\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Facades\Config;

/**
 * Portfolio Type Enum with dynamic config-based cases
 *
 * Base cases:
 *
 * @method static self ALL()
 * @method static self RESIDENTIAL()
 * @method static self COMMERCIAL()
 */
enum PortfolioType: string implements HasColor, HasLabel
{
    case ALL = 'all';
    case RESIDENTIAL = 'residential';
    case COMMERCIAL = 'commercial';

    /**
     * Get all available portfolio types from config
     */
    public static function getTypes(): array
    {
        $configTypes = Config::get('sabhero-portfolio.portfolio_types', []);

        // Always include the default types if not present in config
        if (! isset($configTypes['all'])) {
            $configTypes['all'] = [
                'label' => 'All',
                'color' => 'gray',
            ];
        }

        if (! isset($configTypes['residential'])) {
            $configTypes['residential'] = [
                'label' => 'Residential',
                'color' => 'success',
            ];
        }

        if (! isset($configTypes['commercial'])) {
            $configTypes['commercial'] = [
                'label' => 'Commercial',
                'color' => 'info',
            ];
        }

        return $configTypes;
    }

    /**
     * Get color for the portfolio type
     */
    public function getColor(): string
    {
        $types = self::getTypes();

        return $types[$this->value]['color'] ?? match ($this) {
            self::ALL => 'gray',
            self::RESIDENTIAL => 'success',
            self::COMMERCIAL => 'info',
            default => 'gray',
        };
    }

    /**
     * Get label for the portfolio type
     */
    public function getLabel(): string
    {
        $types = self::getTypes();

        return $types[$this->value]['label'] ?? match ($this) {
            self::ALL => 'All',
            self::RESIDENTIAL => 'Residential',
            self::COMMERCIAL => 'Commercial',
            default => ucfirst($this->value),
        };
    }

    /**
     * Get all options as an array for Select components
     */
    public static function getOptions(): array
    {
        $options = [];

        // Get all cases including dynamic ones
        foreach (self::getAllCases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    /**
     * Get all cases including dynamically configured ones
     */
    public static function getAllCases(): array
    {
        $cases = self::cases();
        $configTypes = self::getTypes();
        $dynamicCases = [];

        // Create dynamic cases for types that don't exist in enum
        foreach (array_keys($configTypes) as $type) {
            // Skip if it's one of the default cases
            if (in_array($type, ['all', 'residential', 'commercial'])) {
                continue;
            }

            // Create a dynamic enum-like object
            $dynamicCases[] = new class($type) implements HasColor, HasLabel {
                public string $name;

                public string $value;

                public function __construct(string $value)
                {
                    $this->name = $value;
                    $this->value = $value;
                }

                public function getLabel(): string
                {
                    $types = PortfolioType::getTypes();

                    return $types[$this->value]['label'] ?? ucfirst($this->value);
                }

                public function getColor(): string
                {
                    $types = PortfolioType::getTypes();

                    return $types[$this->value]['color'] ?? 'gray';
                }

                // Used for string casting
                public function __toString(): string
                {
                    return $this->value;
                }
            };
        }

        return array_merge($cases, $dynamicCases);
    }

    /**
     * Try to get a case from the enum by value, including dynamic cases
     *
     * @return self|object|null
     */
    public static function tryFromConfig(string $value)
    {
        // Try getting from native enum cases first
        $case = self::tryFrom($value);

        if ($case !== null) {
            return $case;
        }

        // Check if it exists in config
        $configTypes = self::getTypes();

        if (isset($configTypes[$value])) {
            // Create a dynamic case
            return new class($value) implements HasColor, HasLabel {
                public string $name;

                public string $value;

                public function __construct(string $value)
                {
                    $this->name = $value;
                    $this->value = $value;
                }

                public function getLabel(): string
                {
                    $types = PortfolioType::getTypes();

                    return $types[$this->value]['label'] ?? ucfirst($this->value);
                }

                public function getColor(): string
                {
                    $types = PortfolioType::getTypes();

                    return $types[$this->value]['color'] ?? 'gray';
                }

                // Used for string casting
                public function __toString(): string
                {
                    return $this->value;
                }
            };
        }

        return null;
    }
}
