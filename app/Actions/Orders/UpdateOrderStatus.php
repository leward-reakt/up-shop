<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderStatus
{
    public function handle(
        Order $order,
        OrderStatus $status,
    ): Order {
        return DB::transaction(function () use (
            $order,
            $status,
        ): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->order_status === $status) {
                return $lockedOrder;
            }

            $allowedStatuses = self::allowedNextStatuses(
                $lockedOrder,
            );

            if (! in_array($status, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'order_status' => 'That order status transition is not allowed.',
                ]);
            }

            $lockedOrder->update([
                'order_status' => $status,
            ]);

            return $lockedOrder->refresh();
        });
    }

    /**
     * @return array<int, OrderStatus>
     */
    public static function allowedNextStatuses(
        Order $order,
    ): array {
        return match ($order->order_status) {
            OrderStatus::Pending => [
                OrderStatus::Confirmed,
            ],

            OrderStatus::Confirmed => [
                OrderStatus::Processing,
            ],

            OrderStatus::Processing => [
                $order->shipping_method === ShippingMethod::StorePickup
                    ? OrderStatus::ReadyForPickup
                    : OrderStatus::Shipped,
            ],

            OrderStatus::ReadyForPickup,
            OrderStatus::Shipped => [
                OrderStatus::Completed,
            ],

            OrderStatus::Completed,
            OrderStatus::Cancelled => [],
        };
    }
}
