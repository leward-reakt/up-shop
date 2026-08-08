<?php

namespace App\Filament\Resources\StoreSettings\Schemas;

use App\Enums\LandingPageTheme;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StoreSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('store_name')
                    ->required()
                    ->maxLength(255),

                FileUpload::make('store_logo_path')
                    ->label('Store logo')
                    ->image()
                    ->disk('public')
                    ->directory('store')
                    ->visibility('public')
                    ->maxSize(5120),

                Select::make('landing_page_theme')
                    ->label('Storefront theme')
                    ->options(LandingPageTheme::options())
                    ->default(LandingPageTheme::Default->value)
                    ->required()
                    ->helperText(
                        'Controls the public Home, Shop, and Product Detail '
                        .'pages. Cart, checkout, and account pages remain unchanged.',
                    ),

                TextInput::make('store_email')
                    ->email()
                    ->maxLength(255),

                TextInput::make('contact_number')
                    ->maxLength(50),

                Textarea::make('business_address')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('currency')
                    ->required()
                    ->default('PHP')
                    ->minLength(3)
                    ->maxLength(3),

                TextInput::make('default_shipping_fee')
                    ->label('Default shipping fee')
                    ->prefix('₱')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->formatStateUsing(
                        fn (int|string|null $state): ?string => $state === null
                            ? null
                            : number_format(
                                ((int) $state) / 100,
                                2,
                                '.',
                                '',
                            ),
                    )
                    ->dehydrateStateUsing(
                        fn (
                            int|float|string|null $state,
                        ): int => (int) round(
                            ((float) $state) * 100,
                        ),
                    ),

                TextInput::make('free_shipping_threshold')
                    ->label('Free shipping threshold')
                    ->prefix('₱')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->formatStateUsing(
                        fn (int|string|null $state): ?string => $state === null
                            ? null
                            : number_format(
                                ((int) $state) / 100,
                                2,
                                '.',
                                '',
                            ),
                    )
                    ->dehydrateStateUsing(
                        fn (
                            int|float|string|null $state,
                        ): ?int => $state === null || $state === ''
                            ? null
                            : (int) round(
                                ((float) $state) * 100,
                            ),
                    ),

                TextInput::make('tax_rate_basis_points')
                    ->label('Tax rate')
                    ->suffix('%')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->formatStateUsing(
                        fn (
                            int|string|null $state,
                        ): ?string => $state === null
                            ? null
                            : number_format(
                                ((int) $state) / 100,
                                2,
                                '.',
                                '',
                            ),
                    )
                    ->dehydrateStateUsing(
                        fn (
                            int|float|string|null $state,
                        ): ?int => $state === null || $state === ''
                            ? null
                            : (int) round(
                                ((float) $state) * 100,
                            ),
                    ),

                KeyValue::make('social_links')
                    ->label('Social links')
                    ->keyLabel('Platform')
                    ->valueLabel('URL')
                    ->columnSpanFull(),
            ]);
    }
}
