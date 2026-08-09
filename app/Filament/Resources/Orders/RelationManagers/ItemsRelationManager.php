<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\StoreSetting;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function table(Table $table): Table
    {
        $currency = StoreSetting::currentCurrency();

        return $table
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product'),

                TextColumn::make('sku')
                    ->label('SKU'),

                TextColumn::make('quantity')
                    ->numeric(),

                TextColumn::make('unit_price')
                    ->label('Unit price')
                    ->money(
                        $currency,
                        divideBy: 100,
                    ),

                TextColumn::make('subtotal')
                    ->money(
                        $currency,
                        divideBy: 100,
                    ),
            ])
            ->defaultSort('id');
    }
}
