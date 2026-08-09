<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class InventoryAlerts extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereColumn(
                        'stock_quantity',
                        '<=',
                        'low_stock_threshold',
                    )
                    ->orderBy('stock_quantity')
                    ->orderBy('name'),
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable(),

                TextColumn::make('sku')
                    ->label('SKU'),

                TextColumn::make('stock_quantity')
                    ->label('Stock status')
                    ->badge()
                    ->formatStateUsing(
                        fn (int $state): string => $state === 0
                            ? 'Out of stock'
                            : "Low stock ({$state})",
                    )
                    ->color(
                        fn (int $state): string => $state === 0
                            ? 'danger'
                            : 'warning',
                    )
                    ->sortable(),

                TextColumn::make('low_stock_threshold')
                    ->label('Threshold')
                    ->numeric(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime(),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([
                5,
                10,
                25,
            ]);
    }
}
