<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $completedRevenue = (int) Order::query()
            ->where('order_status', OrderStatus::Completed)
            ->sum('grand_total');

        return [
            Stat::make(
                'Total orders',
                Order::query()->count(),
            ),

            Stat::make(
                'Pending orders',
                Order::query()
                    ->where('order_status', OrderStatus::Pending)
                    ->count(),
            )->color('warning'),

            Stat::make(
                'Processing orders',
                Order::query()
                    ->where('order_status', OrderStatus::Processing)
                    ->count(),
            )->color('info'),

            Stat::make(
                'Completed orders',
                Order::query()
                    ->where('order_status', OrderStatus::Completed)
                    ->count(),
            )->color('success'),

            Stat::make(
                'Cancelled orders',
                Order::query()
                    ->where('order_status', OrderStatus::Cancelled)
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
                '₱'.number_format(
                    $completedRevenue / 100,
                    2,
                ),
            )->color('success'),
        ];
    }
}
