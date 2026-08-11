<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use App\Services\Payments\PayMongoGateway;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class CancelOrder
{
    public function __construct(
        private readonly PayMongoGateway $payMongoGateway,
    ) {}

    public function handle(
        Order $order,
        User $user,
    ): Order {
        $currentOrder = Order::query()
            ->with('payment')
            ->findOrFail($order->id);

        if (
            $currentOrder->order_status
            === OrderStatus::Cancelled
        ) {
            return $currentOrder;
        }

        if (! $currentOrder->order_status->canBeCancelled()) {
            throw ValidationException::withMessages([
                'order' => sprintf(
                    '%s orders cannot be cancelled.',
                    $currentOrder->order_status->label(),
                ),
            ]);
        }

        $this->assertPayMongoCancellationIsSafe(
            $currentOrder,
        );

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

    private function assertPayMongoCancellationIsSafe(
        Order $order,
    ): void {
        $payment = $order->payment;

        if (
            ! $payment instanceof Payment
            || ! $payment->method->usesPayMongo()
            || $payment->provider !== 'paymongo'
            || $payment->status === PaymentStatus::Refunded
        ) {
            return;
        }

        if ($payment->status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'order' => 'Resolve or refund the paid payment before cancelling this order.',
            ]);
        }

        $checkoutId = $payment->provider_checkout_id;

        if (
            ! is_string($checkoutId)
            || trim($checkoutId) === ''
        ) {
            return;
        }

        $checkoutSession = $this->retrievePayMongoSession(
            $checkoutId,
        );

        $this->assertCheckoutSessionMatchesOrder(
            $checkoutSession,
            $checkoutId,
            $order,
        );

        if ($checkoutSession['has_paid_payment']) {
            throw ValidationException::withMessages([
                'order' => 'PayMongo already reports a paid payment. Wait for payment reconciliation before cancelling this order.',
            ]);
        }

        if ($checkoutSession['status'] === 'expired') {
            return;
        }

        try {
            $this->payMongoGateway->expireCheckoutSession(
                $checkoutId,
            );

            return;
        } catch (RequestException $exception) {
            if ($exception->response->status() !== 400) {
                report($exception);

                throw ValidationException::withMessages([
                    'order' => 'PayMongo payment state could not be secured for cancellation. Please try again later.',
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'order' => 'PayMongo payment state could not be secured for cancellation. Please try again later.',
            ]);
        }

        // A 400 may mean the session was concurrently expired, already paid,
        // or currently processing a payment. Re-read provider state and only
        // proceed if the session is now safely expired with no paid payment.
        $checkoutSession = $this->retrievePayMongoSession(
            $checkoutId,
        );

        $this->assertCheckoutSessionMatchesOrder(
            $checkoutSession,
            $checkoutId,
            $order,
        );

        if ($checkoutSession['has_paid_payment']) {
            throw ValidationException::withMessages([
                'order' => 'PayMongo already reports a paid payment. Wait for payment reconciliation before cancelling this order.',
            ]);
        }

        if ($checkoutSession['status'] !== 'expired') {
            throw ValidationException::withMessages([
                'order' => 'PayMongo payment is still active or processing and this order cannot be cancelled yet.',
            ]);
        }
    }

    /**
     * @return array{
     *     checkout_id: string,
     *     checkout_url: string,
     *     status: string,
     *     reference_number: string|null,
     *     has_paid_payment: bool
     * }
     */
    private function retrievePayMongoSession(
        string $checkoutId,
    ): array {
        try {
            return $this
                ->payMongoGateway
                ->retrieveCheckoutSession(
                    $checkoutId,
                );
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'order' => 'PayMongo payment state could not be verified. Please try again later.',
            ]);
        }
    }

    /**
     * @param  array{
     *     checkout_id: string,
     *     checkout_url: string,
     *     status: string,
     *     reference_number: string|null,
     *     has_paid_payment: bool
     * }  $checkoutSession
     */
    private function assertCheckoutSessionMatchesOrder(
        array $checkoutSession,
        string $checkoutId,
        Order $order,
    ): void {
        if ($checkoutSession['checkout_id'] !== $checkoutId) {
            throw ValidationException::withMessages([
                'order' => 'PayMongo Checkout Session identity could not be verified. Cancellation was blocked.',
            ]);
        }

        if (
            $checkoutSession['reference_number'] !== null
            && $checkoutSession['reference_number']
                !== $order->order_number
        ) {
            throw ValidationException::withMessages([
                'order' => 'PayMongo Checkout Session does not belong to this order. Cancellation was blocked.',
            ]);
        }
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
