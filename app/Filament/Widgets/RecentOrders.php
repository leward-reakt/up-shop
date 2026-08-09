<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrders extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->latest('created_at')
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order'),

                TextColumn::make('customer_name')
                    ->label('Customer'),

                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('PHP', divideBy: 100),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(
                        fn (PaymentStatus $state): string => $state->label(),
                    ),

                TextColumn::make('order_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (OrderStatus $state): string => $state->label(),
                    ),

                TextColumn::make('created_at')
                    ->label('Ordered')
                    ->dateTime(),
            ])
            ->paginated(false);
    }
}
