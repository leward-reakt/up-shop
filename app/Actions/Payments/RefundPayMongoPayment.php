<?php

namespace App\Actions\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentRefundedNotification;
use App\Services\Payments\PayMongoGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class RefundPayMongoPayment
{
    public function __construct(
        private readonly PayMongoGateway $gateway,
    ) {}

    public function isEligible(Payment $payment): bool
    {
        return (bool) config('services.paymongo.available', false)
            && $this->isPayMongoMethod($payment->method)
            && $payment->status === PaymentStatus::Paid
            && $this->hasProviderPaymentReference($payment)
            && $payment->refund_reference === null
            && $payment->refunded_at === null;
    }

    public function handle(Payment $payment): Payment
    {
        $refundRequest = DB::transaction(
            function () use ($payment): array {
                $lockedPayment = Payment::query()
                    ->with('order')
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                $this->ensureEligible($lockedPayment);

                $order = $lockedPayment->order;

                if (! $order instanceof Order) {
                    throw ValidationException::withMessages([
                        'refund' => 'The payment is not attached to a valid order.',
                    ]);
                }

                return [
                    'payment_id' => $lockedPayment->id,
                    'provider_payment_id' => (string) $lockedPayment->reference,
                    'amount' => $lockedPayment->amount,
                    'idempotency_key' => $this->idempotencyKey(
                        $lockedPayment,
                    ),
                    'notes' => sprintf(
                        'Full refund for %s.',
                        $order->order_number,
                    ),
                ];
            },
        );

        try {
            $refund = $this->gateway->refundPayment(
                paymentId: $refundRequest['provider_payment_id'],
                amount: $refundRequest['amount'],
                idempotencyKey: $refundRequest['idempotency_key'],
                notes: $refundRequest['notes'],
            );
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'refund' => 'PayMongo could not process the refund request. The local payment remains paid.',
            ]);
        }

        $updatedPayment = $this->reconcile(
            payment: $payment,
            refund: $refund,
        );

        if (($refund['status'] ?? null) === 'failed') {
            throw ValidationException::withMessages([
                'refund' => 'PayMongo reported that the refund failed. The local payment remains paid.',
            ]);
        }

        return $updatedPayment;
    }

    /**
     * Reconcile a normalized PayMongo Refund resource.
     *
     * This method intentionally does not check the store PayMongo toggle or
     * PAYMONGO_ADMIN_ENABLED because historical provider webhooks must remain
     * reconcilable after new PayMongo operations are disabled.
     *
     * @param  array{
     *     id: string,
     *     payment_id: string,
     *     amount: int,
     *     currency: string,
     *     status: string
     * }  $refund
     */
    public function reconcile(
        Payment $payment,
        array $refund,
    ): Payment {
        $becameRefunded = false;

        $updatedPayment = DB::transaction(
            function () use (
                $payment,
                $refund,
                &$becameRefunded,
            ): Payment {
                $lockedPayment = Payment::query()
                    ->with('order')
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                $this->ensureRefundMatchesPayment(
                    payment: $lockedPayment,
                    refund: $refund,
                );

                if (
                    $lockedPayment->refund_reference !== null
                    && $lockedPayment->refund_reference !== $refund['id']
                ) {
                    throw ValidationException::withMessages([
                        'refund' => 'The PayMongo refund reference does not match the existing refund request.',
                    ]);
                }

                if ($lockedPayment->status === PaymentStatus::Refunded) {
                    return $lockedPayment
                        ->refresh()
                        ->load('order');
                }

                if ($lockedPayment->status !== PaymentStatus::Paid) {
                    throw ValidationException::withMessages([
                        'refund' => 'Only a paid PayMongo payment can be reconciled as refunded.',
                    ]);
                }

                $lockedPayment->update([
                    'refund_reference' => $refund['id'],
                ]);

                if ($refund['status'] !== 'succeeded') {
                    return $lockedPayment
                        ->refresh()
                        ->load('order');
                }

                $lockedPayment->update([
                    'status' => PaymentStatus::Refunded,
                    'refunded_at' => now(),
                ]);

                $lockedPayment
                    ->order()
                    ->update([
                        'payment_status' => PaymentStatus::Refunded,
                    ]);

                $becameRefunded = true;

                return $lockedPayment
                    ->refresh()
                    ->load('order');
            },
        );

        if ($becameRefunded) {
            $this->notifyCustomer($updatedPayment);
        }

        return $updatedPayment;
    }

    private function ensureEligible(
        Payment $payment,
    ): void {
        if (! $this->isEligible($payment)) {
            throw ValidationException::withMessages([
                'refund' => 'This payment is not eligible for a PayMongo full refund.',
            ]);
        }
    }

    /**
     * @param  array{
     *     id: string,
     *     payment_id: string,
     *     amount: int,
     *     currency: string,
     *     status: string
     * }  $refund
     */
    private function ensureRefundMatchesPayment(
        Payment $payment,
        array $refund,
    ): void {
        if (! $this->isPayMongoMethod($payment->method)) {
            throw ValidationException::withMessages([
                'refund' => 'The payment is not a PayMongo payment.',
            ]);
        }

        if (! $this->hasProviderPaymentReference($payment)) {
            throw ValidationException::withMessages([
                'refund' => 'The PayMongo payment reference is missing.',
            ]);
        }

        if ($refund['payment_id'] !== $payment->reference) {
            throw ValidationException::withMessages([
                'refund' => 'The PayMongo refund belongs to a different payment.',
            ]);
        }

        if ($refund['amount'] !== $payment->amount) {
            throw ValidationException::withMessages([
                'refund' => 'The PayMongo refund amount does not match the full local payment amount.',
            ]);
        }

        if ($refund['currency'] !== 'PHP') {
            throw ValidationException::withMessages([
                'refund' => 'The PayMongo refund currency is invalid.',
            ]);
        }

        if (
            ! in_array(
                $refund['status'],
                [
                    'pending',
                    'processing',
                    'succeeded',
                    'failed',
                ],
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'refund' => 'PayMongo returned an unsupported refund status.',
            ]);
        }
    }

    private function idempotencyKey(
        Payment $payment,
    ): string {
        return 'up-shop-full-refund-'.hash(
            'sha256',
            implode(
                ':',
                [
                    $payment->id,
                    $payment->reference,
                    $payment->amount,
                ],
            ),
        );
    }

    private function hasProviderPaymentReference(
        Payment $payment,
    ): bool {
        return is_string($payment->reference)
            && trim($payment->reference) !== '';
    }

    private function isPayMongoMethod(
        PaymentMethod $method,
    ): bool {
        return in_array(
            $method,
            [
                PaymentMethod::GCash,
                PaymentMethod::Maya,
            ],
            true,
        );
    }

    private function notifyCustomer(
        Payment $payment,
    ): void {
        $order = $payment->order;

        if (! $order instanceof Order) {
            return;
        }

        try {
            Notification::route(
                'mail',
                [
                    $order->customer_email => $order->customer_name,
                ],
            )->notify(
                new PaymentRefundedNotification(
                    $payment,
                ),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
