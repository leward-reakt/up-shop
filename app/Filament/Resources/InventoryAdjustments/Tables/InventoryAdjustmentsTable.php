<?php

namespace App\Filament\Resources\InventoryAdjustments\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),

                TextColumn::make('quantity_change')
                    ->label('Change')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge(),

                TextColumn::make('reference_type')
                    ->label('Reference')
                    ->placeholder('—'),

                TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->placeholder('—'),

                TextColumn::make('user.name')
                    ->label('Performed by')
                    ->placeholder('System'),

                TextColumn::make('notes')
                    ->limit(80)
                    ->wrap(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->options([
                        'order' => 'Order',
                        'manual' => 'Manual',
                        'order_cancelled' => 'Order cancelled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
