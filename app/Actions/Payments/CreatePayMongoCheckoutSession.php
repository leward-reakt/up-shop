<?php

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StoreSetting;
use App\Services\Payments\PayMongoGateway;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use LogicException;
use UnexpectedValueException;

class CreatePayMongoCheckoutSession
{
    public function __construct(
        private readonly PayMongoGateway $payMongoGateway,
    ) {}

    /**
     * @return array{
     *     checkout_id: string,
     *     checkout_url: string
     * }
     */
    public function handle(Order $order): array
    {
        $order->loadMissing('payment');

        $payment = $order->payment;

        if (! $payment instanceof Payment) {
            throw new LogicException(
                'The order does not have a payment record.',
            );
        }

        if (
            ! $order->payment_method->usesPayMongo()
            || ! $payment->method->usesPayMongo()
        ) {
            throw new LogicException(
                'The order is not a PayMongo order.',
            );
        }

        if (
            $order->payment_status !== PaymentStatus::Pending
            || $payment->status !== PaymentStatus::Pending
        ) {
            throw ValidationException::withMessages([
                'payment' => 'This payment can no longer be started.',
            ]);
        }

        if ($order->order_status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages([
                'payment' => 'A cancelled order cannot be paid.',
            ]);
        }

        if (! StoreSetting::payMongoAvailableForNewCheckout()) {
            throw ValidationException::withMessages([
                'payment' => 'Online payment is currently unavailable.',
            ]);
        }

        if ((int) $payment->amount !== (int) $order->grand_total) {
            throw new UnexpectedValueException(
                'Local payment amount does not match the order total.',
            );
        }

        $currency = StoreSetting::currentCurrency();

        if ($currency !== 'PHP') {
            throw new UnexpectedValueException(
                'PayMongo order currency must be PHP.',
            );
        }

        // Reusing this key after an uncertain initial request lets PayMongo
        // return the original resource instead of creating a duplicate.
        //
        // When replacing an explicitly expired session, the previous
        // Checkout Session ID becomes part of the key so the replacement is
        // a distinct logical operation.
        $idempotencyKey = $payment->provider_checkout_id === null
            ? "payment-{$payment->id}-checkout-initial"
            : "payment-{$payment->id}-checkout-after-{$payment->provider_checkout_id}";

        $checkoutSession = $this
            ->payMongoGateway
            ->createCheckoutSession(
                method: $order->payment_method,
                amount: (int) $order->grand_total,
                currency: $currency,
                referenceNumber: $order->order_number,
                successUrl: URL::signedRoute(
                    'checkout.payment.success',
                    [
                        'order' => $order->order_number,
                    ],
                ),
                cancelUrl: URL::signedRoute(
                    'checkout.payment.cancelled',
                    [
                        'order' => $order->order_number,
                    ],
                ),
                idempotencyKey: $idempotencyKey,
            );

        $payment->update([
            'provider' => 'paymongo',
            'currency' => $currency,
            'provider_checkout_id' => $checkoutSession['checkout_id'],
        ]);

        return $checkoutSession;
    }
}
