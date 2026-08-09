<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateOrderStatus
{
    public function handle(
        Order $order,
        OrderStatus $status,
    ): Order {
        $statusChanged = false;

        $updatedOrder = DB::transaction(
            function () use (
                $order,
                $status,
                &$statusChanged,
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

                $statusChanged = true;

                return $lockedOrder->refresh();
            },
        );

        if (
            $statusChanged
            && in_array(
                $status,
                [
                    OrderStatus::Processing,
                    OrderStatus::ReadyForPickup,
                    OrderStatus::Shipped,
                    OrderStatus::Completed,
                ],
                true,
            )
        ) {
            $this->notifyCustomer($updatedOrder);
        }

        return $updatedOrder;
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

            OrderStatus::Confirmed => self::canContinueFulfillment(
                $order,
            )
                ? [OrderStatus::Processing]
                : [],

            OrderStatus::Processing => self::canContinueFulfillment(
                $order,
            )
                ? [
                    $order->shipping_method === ShippingMethod::StorePickup
                        ? OrderStatus::ReadyForPickup
                        : OrderStatus::Shipped,
                ]
                : [],

            OrderStatus::ReadyForPickup,
            OrderStatus::Shipped => $order->payment_status === PaymentStatus::Paid
                ? [OrderStatus::Completed]
                : [],

            OrderStatus::Completed,
            OrderStatus::Cancelled => [],
        };
    }

    private static function canContinueFulfillment(
        Order $order,
    ): bool {
        if ($order->payment_status === PaymentStatus::Paid) {
            return true;
        }

        // COD remains pending while fulfillment proceeds until cash is collected.
        return $order->payment_method === PaymentMethod::CashOnDelivery
            && $order->payment_status === PaymentStatus::Pending;
    }

    private function notifyCustomer(Order $order): void
    {
        try {
            Notification::route(
                'mail',
                [
                    $order->customer_email => $order->customer_name,
                ],
            )->notify(
                new OrderStatusChangedNotification($order),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
