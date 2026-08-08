<?php

namespace App\Filament\Resources\StoreSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoreSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('store_name')
                    ->label('Store'),

                TextColumn::make('currency'),

                TextColumn::make('default_shipping_fee')
                    ->label('Shipping')
                    ->money('PHP', divideBy: 100),

                TextColumn::make('free_shipping_threshold')
                    ->label('Free shipping from')
                    ->money('PHP', divideBy: 100)
                    ->placeholder('Disabled'),

                TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
