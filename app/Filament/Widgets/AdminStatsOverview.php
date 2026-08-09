<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class AdminStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array|null
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $currency = StoreSetting::currentCurrency();

        $completedRevenue = (int) Order::query()
            ->where(
                'order_status',
                OrderStatus::Completed,
            )
            ->sum('grand_total');

        return [
            Stat::make(
                'Total orders',
                Order::query()->count(),
            ),

            Stat::make(
                'Pending orders',
                Order::query()
                    ->where(
                        'order_status',
                        OrderStatus::Pending,
                    )
                    ->count(),
            )->color('warning'),

            Stat::make(
                'Processing orders',
                Order::query()
                    ->where(
                        'order_status',
                        OrderStatus::Processing,
                    )
                    ->count(),
            )->color('info'),

            Stat::make(
                'Completed orders',
                Order::query()
                    ->where(
                        'order_status',
                        OrderStatus::Completed,
                    )
                    ->count(),
            )->color('success'),

            Stat::make(
                'Cancelled orders',
                Order::query()
                    ->where(
                        'order_status',
                        OrderStatus::Cancelled,
                    )
                    ->count(),
            )->color('danger'),

            Stat::make(
                'Total customers',
                User::query()
                    ->where('is_admin', false)
                    ->count(),
            ),

            Stat::make(
                'Total products',
                Product::query()->count(),
            ),

            Stat::make(
                'Completed revenue',
                Number::currency(
                    $completedRevenue / 100,
                    in: $currency,
                ),
            )->color('success'),
        ];
    }
}
