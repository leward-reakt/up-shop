<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(
                        fn (?string $state): string => Str::upper(
                            trim((string) $state),
                        ),
                    ),

                Select::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed amount',
                    ])
                    ->required()
                    ->live(),

                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->formatStateUsing(
                        function (
                            int|string|null $state,
                            Get $get,
                        ): int|string|null {
                            if (
                                $state === null
                                || $get('type') !== 'fixed'
                            ) {
                                return $state;
                            }

                            return number_format(
                                ((int) $state) / 100,
                                2,
                                '.',
                                '',
                            );
                        },
                    )
                    ->dehydrateStateUsing(
                        function (
                            int|float|string|null $state,
                            Get $get,
                        ): int {
                            if ($get('type') === 'fixed') {
                                return (int) round(
                                    ((float) $state) * 100,
                                );
                            }

                            return (int) $state;
                        },
                    )
                    ->rules(
                        fn (Get $get): array => $get('type') === 'percentage'
                            ? [
                                'integer',
                                'min:1',
                                'max:100',
                            ]
                            : [
                                'numeric',
                                'min:0.01',
                                'decimal:0,2',
                            ],
                    )
                    ->helperText('Percentage uses 1–100. Fixed amounts are entered in pesos.'),

                TextInput::make('minimum_purchase')
                    ->label('Minimum purchase')
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

                DateTimePicker::make('starts_at')
                    ->label('Starts at'),

                DateTimePicker::make('expires_at')
                    ->label('Expires at')
                    ->rules([
                        'nullable',
                        'after_or_equal:starts_at',
                    ]),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
