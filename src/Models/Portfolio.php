<?php

namespace Fuelviews\SabHeroPortfolio\Models;

use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Fuelviews\SabHeroPortfolio\Enums\PortfolioType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Portfolio extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /*    protected static function newFactory()
        {
            return \Fuelviews\SabHeroPortfolio\Database\Factories\PortfolioFactory::new();
        }*/

    protected $fillable = [
        'title',
        'description',
        'spacing',
        'order',
        'is_published',
        'type',
        'display_mode',
        'before_alt',
        'after_alt',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    // We're no longer casting to enum objects
    // Instead we store the string value and handle display in the Filament components

    public static function getForm(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Section::make('Portfolio Item')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Textarea::make('description')
                                ->required()
                                ->autosize()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('spacing')
                                ->options([
                                    'yes' => 'Top and Bottom',
                                    'no' => 'No Spacing',
                                    'top' => 'Top Only',
                                    'bottom' => 'Bottom Only',
                                ])
                                ->default('yes')
                                ->required(),

                            Forms\Components\Select::make('type')
                                ->options(function () {
                                    // Get all types and convert to simple key-value array format
                                    $options = [];
                                    $configTypes = PortfolioType::getTypes();

                                    foreach ($configTypes as $key => $type) {
                                        // Capitalize each word in the label
                                        $options[$key] = ucwords($type['label']);
                                    }

                                    return $options;
                                })
                                ->default('all')
                                ->required(),

                            Forms\Components\TextInput::make('order')
                                ->numeric()
                                ->default(0),

                            Forms\Components\Toggle::make('is_published')
                                ->label('Published')
                                ->inline(false)
                                ->default(true),
                        ])
                        ->columnSpan(['lg' => 2]),

                    Forms\Components\Section::make('Images')
                        ->schema([
                            Forms\Components\Select::make('display_mode')
                                ->label('Display Mode')
                                ->options([
                                    'before_after' => 'Before/After Slider',
                                    'images' => 'Gallery',
                                ])
                                ->default('before_after')
                                ->required()
                                ->reactive()
                                ->columnSpanFull()
                                ->visible(fn (): bool => Schema::hasColumn('portfolios', 'display_mode')),

                            Forms\Components\Grid::make(1)
                                ->schema([
                                    SpatieMediaLibraryFileUpload::make('before_image')
                                        ->collection('before_image')
                                        ->label('Before Image')
                                        ->required(fn (Forms\Get $get): bool => ! Schema::hasColumn('portfolios', 'display_mode') || $get('display_mode') === 'before_after')
                                        ->image()
                                        ->imageEditor()
                                        ->imageResizeMode('cover')
                                        ->visible(fn (Forms\Get $get): bool => ! Schema::hasColumn('portfolios', 'display_mode') || $get('display_mode') === 'before_after'),

                                    Forms\Components\TextInput::make('before_alt')
                                        ->label('Before Image Alt Text')
                                        ->maxLength(255)
                                        ->visible(fn (Forms\Get $get): bool => ! Schema::hasColumn('portfolios', 'display_mode') || $get('display_mode') === 'before_after'),
                                ])
                                ->visible(fn (Forms\Get $get): bool => ! Schema::hasColumn('portfolios', 'display_mode') || $get('display_mode') === 'before_after'),

                            Forms\Components\Grid::make(1)
                                ->schema([
                                    SpatieMediaLibraryFileUpload::make('after_image')
                                        ->collection('after_image')
                                        ->label('After Image')
                                        ->required(fn (Forms\Get $get): bool => ! Schema::hasColumn('portfolios', 'display_mode') || $get('display_mode') === 'before_after')
                                        ->image()
                                        ->imageEditor()
                                        ->imageResizeMode('cover')
                                        ->visible(fn (Forms\Get $get): bool => ! Schema::hasColumn('portfolios', 'display_mode') || $get('display_mode') === 'before_after'),

                                    Forms\Components\TextInput::make('after_alt')
                                        ->label('After Image Alt Text')
                                        ->maxLength(255)
                                        ->visible(fn (Forms\Get $get): bool => ! Schema::hasColumn('portfolios', 'display_mode') || $get('display_mode') === 'before_after'),
                                ])
                                ->visible(fn (Forms\Get $get): bool => ! Schema::hasColumn('portfolios', 'display_mode') || $get('display_mode') === 'before_after'),

                            Forms\Components\Grid::make(1)
                                ->schema([
                                    SpatieMediaLibraryFileUpload::make('images')
                                        ->collection('images')
                                        ->label('Gallery Images')
                                        ->required(fn (Forms\Get $get): bool => $get('display_mode') === 'images')
                                        ->multiple()
                                        ->image()
                                        ->imageEditor()
                                        ->imageResizeMode('cover')
                                        ->visible(fn (Forms\Get $get): bool => Schema::hasColumn('portfolios', 'display_mode') && $get('display_mode') === 'images'),
                                ])
                                ->visible(fn (Forms\Get $get): bool => Schema::hasColumn('portfolios', 'display_mode') && $get('display_mode') === 'images'),
                        ])
                        ->columnSpan(['lg' => 2]),
                ])->columns(4),
        ];
    }

    public function registerMediaCollections(): void
    {
        // Get custom disk from config or use default
        $mediaDisk = config('sabhero-portfolio.media.disk');

        $beforeCollection = $this->addMediaCollection('before_image')
            ->withResponsiveImages();

        $afterCollection = $this->addMediaCollection('after_image')
            ->withResponsiveImages();

        $galleryCollection = $this->addMediaCollection('images')
            ->withResponsiveImages();

        // Set custom disk if specified in config
        if ($mediaDisk) {
            $beforeCollection->useDisk($mediaDisk);
            $afterCollection->useDisk($mediaDisk);
            $galleryCollection->useDisk($mediaDisk);
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(300)
            ->height(300)
            ->keepOriginalImageFormat();
    }

    public function getBeforeImageAttribute()
    {
        return $this->getFirstMediaUrl('before_image');
    }

    public function getAfterImageAttribute()
    {
        return $this->getFirstMediaUrl('after_image');
    }

    // Title attribute - convert to title case on get
    public function getTitleAttribute($value)
    {
        return ucwords(strtolower($value));
    }

    // Title attribute - convert to lowercase on set
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = strtolower($value);
    }
}
