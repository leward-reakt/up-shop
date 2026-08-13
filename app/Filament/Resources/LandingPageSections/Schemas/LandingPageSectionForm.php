<?php

namespace App\Filament\Resources\LandingPageSections\Schemas;

use App\Models\LandingPageSection;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LandingPageSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('key')
                    ->label('Section')
                    ->options(LandingPageSection::options())
                    ->disabled(),

                TextInput::make('eyebrow')
                    ->maxLength(255),

                TextInput::make('title')
                    ->maxLength(255),

                Textarea::make('body')
                    ->rows(5)
                    ->maxLength(5000)
                    ->columnSpanFull(),

                TextInput::make('button_label')
                    ->maxLength(255),

                TextInput::make('button_url')
                    ->label('Button path')
                    ->prefix('/')
                    ->maxLength(2048)
                    ->helperText(
                        'Use an internal storefront path, e.g. shop or shop?sort=newest.',
                    ),

                FileUpload::make('image_path')
                    ->label('Section image')
                    ->image()
                    ->disk('public')
                    ->directory('landing-page')
                    ->visibility('public')
                    ->maxSize(5120)
                    ->visible(
                        fn (Get $get): bool => in_array(
                            $get('key'),
                            [
                                LandingPageSection::HERO,
                                LandingPageSection::STORY,
                            ],
                            true,
                        ),
                    ),

                TextInput::make('image_alt')
                    ->label('Image alt text')
                    ->maxLength(255)
                    ->visible(
                        fn (Get $get): bool => in_array(
                            $get('key'),
                            [
                                LandingPageSection::HERO,
                                LandingPageSection::STORY,
                            ],
                            true,
                        ),
                    ),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText(
                        'Disable this section to hide it from the homepage.',
                    ),
            ]);
    }
}
