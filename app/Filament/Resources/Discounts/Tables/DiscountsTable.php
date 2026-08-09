<?php

namespace App\Filament\Resources\Discounts\Tables;

use App\Models\Discount;
use App\Models\StoreSetting;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        $currency = StoreSetting::currentCurrency();

        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->formatStateUsing(
                        fn (
                            string $state,
                        ): string => $state
                            === 'percentage'
                            ? 'Percentage'
                            : 'Fixed amount',
                    ),

                TextColumn::make('value')
                    ->formatStateUsing(
                        fn (
                            int $state,
                            Discount $record,
                        ): string => $record->type
                            === 'percentage'
                            ? "{$state}%"
                            : Number::currency(
                                $state / 100,
                                in: $currency,
                            ),
                    ),

                TextColumn::make(
                    'minimum_purchase',
                )
                    ->label('Minimum')
                    ->money(
                        $currency,
                        divideBy: 100,
                    )
                    ->placeholder('None'),

                TextColumn::make('starts_at')
                    ->dateTime()
                    ->placeholder('Immediately'),

                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('No expiry'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort(
                'created_at',
                'desc',
            );
    }
}
