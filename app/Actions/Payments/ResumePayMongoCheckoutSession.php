<?php

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StoreSetting;
use App\Services\Payments\PayMongoGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use LogicException;
use UnexpectedValueException;

class ResumePayMongoCheckoutSession
{
    private const RESUME_LOCK_SECONDS = 60;

    public function __construct(
        private readonly PayMongoGateway $payMongoGateway,
        private readonly CreatePayMongoCheckoutSession $createCheckoutSession,
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

        $result = Cache::lock(
            "paymongo:resume:payment:{$payment->id}",
            self::RESUME_LOCK_SECONDS,
        )->get(
            function () use ($order): array {
                $freshOrder = Order::query()
                    ->with('payment')
                    ->findOrFail($order->id);

                $this->assertCanResume($freshOrder);

                $payment = $freshOrder->payment;

                if (! $payment instanceof Payment) {
                    throw new LogicException(
                        'The order does not have a payment record.',
                    );
                }

                if ($payment->provider_checkout_id === null) {
                    return $this
                        ->createCheckoutSession
                        ->handle($freshOrder);
                }

                $checkoutSession = $this
                    ->payMongoGateway
                    ->retrieveCheckoutSession(
                        $payment->provider_checkout_id,
                    );

                if (
                    $checkoutSession['checkout_id']
                    !== $payment->provider_checkout_id
                ) {
                    throw new UnexpectedValueException(
                        'PayMongo returned a different Checkout Session ID.',
                    );
                }

                if (
                    $checkoutSession['reference_number'] !== null
                    && $checkoutSession['reference_number']
                        !== $freshOrder->order_number
                ) {
                    throw new UnexpectedValueException(
                        'PayMongo Checkout Session does not belong to this order.',
                    );
                }

                // A provider lookup may reveal that payment already completed
                // before the webhook reached us. Do not mutate local status
                // here and do not initiate another payment. Ticket 8 remains
                // authoritative for local Paid reconciliation.
                if ($checkoutSession['has_paid_payment']) {
                    throw ValidationException::withMessages([
                        'payment' => 'Payment is already being verified. Refresh this page shortly.',
                    ]);
                }

                if ($checkoutSession['status'] === 'active') {
                    return [
                        'checkout_id' => $checkoutSession['checkout_id'],
                        'checkout_url' => $checkoutSession['checkout_url'],
                    ];
                }

                if ($checkoutSession['status'] === 'expired') {
                    return $this
                        ->createCheckoutSession
                        ->handle($freshOrder);
                }

                throw new UnexpectedValueException(
                    'PayMongo returned an unsupported Checkout Session status.',
                );
            },
        );

        if (
            ! is_array($result)
            || ! isset(
                $result['checkout_id'],
                $result['checkout_url'],
            )
            || ! is_string($result['checkout_id'])
            || ! is_string($result['checkout_url'])
        ) {
            throw ValidationException::withMessages([
                'payment' => 'Payment is already being resumed. Please wait a moment.',
            ]);
        }

        return [
            'checkout_id' => $result['checkout_id'],
            'checkout_url' => $result['checkout_url'],
        ];
    }

    private function assertCanResume(Order $order): void
    {
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
            throw ValidationException::withMessages([
                'payment' => 'This order does not use online payment.',
            ]);
        }

        if (
            $order->payment_status !== PaymentStatus::Pending
            || $payment->status !== PaymentStatus::Pending
        ) {
            throw ValidationException::withMessages([
                'payment' => 'This payment can no longer be resumed.',
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
    }
}
