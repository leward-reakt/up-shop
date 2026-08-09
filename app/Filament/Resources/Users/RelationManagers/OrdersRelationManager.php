<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\StoreSetting;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function table(Table $table): Table
    {
        $currency = StoreSetting::currentCurrency();

        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable(),

                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money(
                        $currency,
                        divideBy: 100,
                    ),

                TextColumn::make(
                    'payment_status',
                )
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            PaymentStatus $state,
                        ): string => $state->label(),
                    ),

                TextColumn::make('order_status')
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            OrderStatus $state,
                        ): string => $state->label(),
                    ),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort(
                'created_at',
                'desc',
            );
    }
}
