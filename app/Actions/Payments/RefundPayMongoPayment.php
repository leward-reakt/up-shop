<?php

namespace App\Actions\Payments;

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
        return (bool) config(
            'services.paymongo.available',
            false,
        )
            && $payment->method->usesPayMongo()
            && $payment->isPayMongoManaged()
            && $payment->status === PaymentStatus::Paid
            && $payment->amount > 0
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

                $attempt = $this->nextRefundAttempt(
                    $lockedPayment,
                );

                return [
                    'payment_id' => $lockedPayment->id,
                    'provider_payment_id' => (string) $lockedPayment
                        ->provider_payment_id,
                    'amount' => $lockedPayment->amount,
                    'attempt' => $attempt,
                    'idempotency_key' => $this->idempotencyKey(
                        payment: $lockedPayment,
                        attempt: $attempt,
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
            attempt: $refundRequest['attempt'],
        );

        if ($refund['status'] === 'failed') {
            throw ValidationException::withMessages([
                'refund' => 'PayMongo reported that the refund failed. The local payment remains paid.',
            ]);
        }

        return $updatedPayment;
    }

    /**
     * Reconcile a normalized PayMongo Refund resource.
     *
     * This intentionally does not check the StoreSetting PayMongo checkout
     * toggle or PAYMONGO_ADMIN_ENABLED. Historical provider state must remain
     * reconcilable after new PayMongo operations have been disabled.
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
        ?int $attempt = null,
    ): Payment {
        $becameRefunded = false;

        $updatedPayment = DB::transaction(
            function () use (
                $payment,
                $refund,
                $attempt,
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

                $order = $lockedPayment->order;

                if (! $order instanceof Order) {
                    throw ValidationException::withMessages([
                        'refund' => 'The payment is not attached to a valid order.',
                    ]);
                }

                if (
                    $lockedPayment->status
                    === PaymentStatus::Refunded
                ) {
                    return $lockedPayment
                        ->refresh()
                        ->load('order');
                }

                if (
                    $lockedPayment->status
                    !== PaymentStatus::Paid
                ) {
                    throw ValidationException::withMessages([
                        'refund' => 'Only a paid PayMongo payment can be reconciled as refunded.',
                    ]);
                }

                /*
                 * A failed webhook from an older refund attempt must not
                 * overwrite or clear a newer active refund request.
                 */
                if (
                    $refund['status'] === 'failed'
                    && $lockedPayment->refund_reference !== null
                    && $lockedPayment->refund_reference !== $refund['id']
                ) {
                    return $lockedPayment
                        ->refresh()
                        ->load('order');
                }

                $metadata = $this->withRefundMetadata(
                    payment: $lockedPayment,
                    refund: $refund,
                    attempt: $attempt,
                );

                if ($refund['status'] === 'failed') {
                    $updates = [
                        'metadata' => $metadata,
                    ];

                    if (
                        $lockedPayment->refund_reference
                        === $refund['id']
                    ) {
                        $updates['refund_reference'] = null;
                    }

                    $lockedPayment->update($updates);

                    return $lockedPayment
                        ->refresh()
                        ->load('order');
                }

                if (
                    in_array(
                        $refund['status'],
                        [
                            'pending',
                            'processing',
                        ],
                        true,
                    )
                ) {
                    if (
                        $lockedPayment->refund_reference !== null
                        && $lockedPayment->refund_reference !== $refund['id']
                    ) {
                        throw ValidationException::withMessages([
                            'refund' => 'Another PayMongo refund is already being processed for this payment.',
                        ]);
                    }

                    $lockedPayment->update([
                        'refund_reference' => $refund['id'],
                        'metadata' => $metadata,
                    ]);

                    return $lockedPayment
                        ->refresh()
                        ->load('order');
                }

                /*
                 * A succeeded provider refund is authoritative once payment
                 * ID, amount, and currency have all been verified.
                 */
                $lockedPayment->update([
                    'status' => PaymentStatus::Refunded,
                    'refund_reference' => $refund['id'],
                    'refunded_at' => $lockedPayment->refunded_at
                        ?? now(),
                    'metadata' => $metadata,
                ]);

                $order->update([
                    'payment_status' => PaymentStatus::Refunded,
                ]);

                $becameRefunded = true;

                return $lockedPayment
                    ->refresh()
                    ->load('order');
            },
            3,
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
        if (
            ! $payment->method->usesPayMongo()
            || ! $payment->isPayMongoManaged()
        ) {
            throw ValidationException::withMessages([
                'refund' => 'The payment is not a PayMongo payment.',
            ]);
        }

        if (! $this->hasProviderPaymentReference($payment)) {
            throw ValidationException::withMessages([
                'refund' => 'The PayMongo payment reference is missing.',
            ]);
        }

        if (trim($refund['id']) === '') {
            throw ValidationException::withMessages([
                'refund' => 'The PayMongo refund reference is invalid.',
            ]);
        }

        if (
            $refund['payment_id']
            !== $payment->provider_payment_id
        ) {
            throw ValidationException::withMessages([
                'refund' => 'The PayMongo refund belongs to a different payment.',
            ]);
        }

        if ($refund['amount'] !== $payment->amount) {
            throw ValidationException::withMessages([
                'refund' => 'The PayMongo refund amount does not match the full local payment amount.',
            ]);
        }

        $localCurrency = is_string($payment->currency)
            ? strtoupper(trim($payment->currency))
            : null;

        if (
            $localCurrency !== 'PHP'
            || strtoupper($refund['currency']) !== $localCurrency
        ) {
            throw ValidationException::withMessages([
                'refund' => 'The PayMongo refund currency does not match the local payment.',
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

    private function nextRefundAttempt(
        Payment $payment,
    ): int {
        $status = data_get(
            $payment->metadata,
            'paymongo_refund.status',
        );

        $attempt = data_get(
            $payment->metadata,
            'paymongo_refund.attempt',
            1,
        );

        if (
            is_string($attempt)
            && ctype_digit($attempt)
        ) {
            $attempt = (int) $attempt;
        }

        if (! is_int($attempt) || $attempt < 1) {
            $attempt = 1;
        }

        return $status === 'failed'
            ? $attempt + 1
            : $attempt;
    }

    private function idempotencyKey(
        Payment $payment,
        int $attempt,
    ): string {
        return 'up-shop-full-refund-'.$attempt.'-'.hash(
            'sha256',
            implode(
                ':',
                [
                    $payment->id,
                    $payment->provider_payment_id,
                    $payment->amount,
                ],
            ),
        );
    }

    /**
     * @param  array{
     *     id: string,
     *     payment_id: string,
     *     amount: int,
     *     currency: string,
     *     status: string
     * }  $refund
     * @return array<string, mixed>
     */
    private function withRefundMetadata(
        Payment $payment,
        array $refund,
        ?int $attempt,
    ): array {
        $metadata = is_array($payment->metadata)
            ? $payment->metadata
            : [];

        $existingAttempt = data_get(
            $metadata,
            'paymongo_refund.attempt',
            1,
        );

        if (
            is_string($existingAttempt)
            && ctype_digit($existingAttempt)
        ) {
            $existingAttempt = (int) $existingAttempt;
        }

        if (
            ! is_int($existingAttempt)
            || $existingAttempt < 1
        ) {
            $existingAttempt = 1;
        }

        $metadata['paymongo_refund'] = [
            'id' => $refund['id'],
            'payment_id' => $refund['payment_id'],
            'amount' => $refund['amount'],
            'currency' => $refund['currency'],
            'status' => $refund['status'],
            'attempt' => $attempt ?? $existingAttempt,
            'reconciled_at' => now()->toIso8601String(),
        ];

        return $metadata;
    }

    private function hasProviderPaymentReference(
        Payment $payment,
    ): bool {
        return is_string($payment->provider_payment_id)
            && trim($payment->provider_payment_id) !== '';
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
