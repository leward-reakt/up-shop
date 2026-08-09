<?php

namespace App\Filament\Resources\StoreSettings\Schemas;

use App\Enums\LandingPageTheme;
use App\Models\Order;
use App\Models\StoreSetting;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StoreSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        $currencyPrefix = static function (Get $get): string {
            $currency = $get('currency');

            return StoreSetting::normalizeCurrency(
                is_string($currency)
                    ? $currency
                    : null,
            );
        };

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
                    ->default(
                        LandingPageTheme::FashionEditorial->value,
                    )
                    ->required()
                    ->helperText(
                        'Fashion Elegant is the active customer-facing '
                        .'storefront theme.',
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
                    ->default(StoreSetting::DEFAULT_CURRENCY)
                    ->minLength(3)
                    ->maxLength(3)
                    ->rules([
                        'regex:/^[A-Za-z]{3}$/',
                        fn (?StoreSetting $record): Closure => function (
                            string $attribute,
                            mixed $value,
                            Closure $fail,
                        ) use ($record): void {
                            if (
                                ! is_string($value)
                                || preg_match(
                                    '/^[A-Za-z]{3}$/',
                                    $value,
                                ) !== 1
                            ) {
                                return;
                            }

                            $currency = StoreSetting::normalizeCurrency(
                                $value,
                            );

                            $currentCurrency = $record?->currencyCode()
                                ?? StoreSetting::currentCurrency();

                            if (
                                $currency === $currentCurrency
                                || ! Order::query()->exists()
                            ) {
                                return;
                            }

                            $fail(
                                'The store currency cannot be changed after '
                                .'the first order is created because existing '
                                .'order amounts do not store a currency '
                                .'snapshot and no currency conversion is '
                                .'available.',
                            );
                        },
                    ])
                    ->live()
                    ->helperText(
                        'Single store-wide three-letter currency code. It '
                        .'can be changed only before the first order is '
                        .'created because existing orders do not store '
                        .'currency snapshots and the MVP does not convert '
                        .'historical amounts.',
                    ),

                TextInput::make('default_shipping_fee')
                    ->label('Default shipping fee')
                    ->prefix($currencyPrefix)
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->helperText(
                        'Set to 0 for store-wide free shipping. '
                        .'Otherwise, use the free shipping threshold below '
                        .'for subtotal-based free shipping.',
                    )
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
                    ->prefix($currencyPrefix)
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
