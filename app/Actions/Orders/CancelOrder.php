<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class CancelOrder
{
    public function handle(
        Order $order,
        User $user,
    ): Order {
        $cancelledNow = false;

        $updatedOrder = DB::transaction(
            function () use (
                $order,
                $user,
                &$cancelledNow,
            ): Order {
                $lockedOrder = Order::query()
                    ->with('items')
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                if (
                    $lockedOrder->order_status
                    === OrderStatus::Cancelled
                ) {
                    return $lockedOrder;
                }

                if (! $lockedOrder->order_status->canBeCancelled()) {
                    throw ValidationException::withMessages([
                        'order' => sprintf(
                            '%s orders cannot be cancelled.',
                            $lockedOrder->order_status->label(),
                        ),
                    ]);
                }

                $payment = Payment::query()
                    ->where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->first();

                if (
                    $payment !== null
                    && $payment->status === PaymentStatus::Paid
                ) {
                    throw ValidationException::withMessages([
                        'order' => 'Resolve or refund the paid payment before cancelling this order.',
                    ]);
                }

                foreach ($lockedOrder->items as $item) {
                    if ($item->product_id === null) {
                        continue;
                    }

                    $product = Product::withTrashed()
                        ->lockForUpdate()
                        ->find($item->product_id);

                    if ($product === null) {
                        continue;
                    }

                    $product->increment(
                        'stock_quantity',
                        $item->quantity,
                    );

                    $product
                        ->inventoryAdjustments()
                        ->create([
                            'user_id' => $user->id,
                            'quantity_change' => $item->quantity,
                            'type' => 'order_cancelled',
                            'reference_type' => 'order',
                            'reference_id' => $lockedOrder->id,
                            'notes' => "Stock restored after cancellation of {$lockedOrder->order_number}.",
                        ]);
                }

                $paymentStatus = $lockedOrder->payment_status;

                if ($payment !== null) {
                    if (
                        $payment->status
                        !== PaymentStatus::Refunded
                    ) {
                        $payment->update([
                            'status' => PaymentStatus::Cancelled,
                        ]);
                    }

                    $paymentStatus = $payment->status;
                }

                $lockedOrder->update([
                    'order_status' => OrderStatus::Cancelled,
                    'payment_status' => $paymentStatus,
                ]);

                $cancelledNow = true;

                return $lockedOrder
                    ->refresh()
                    ->load([
                        'items',
                        'payment',
                    ]);
            },
        );

        if ($cancelledNow) {
            $this->notifyCustomer($updatedOrder);
        }

        return $updatedOrder;
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
