<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentConfirmedNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;
use UnexpectedValueException;

class ReconcilePayMongoWebhook
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        array $payload,
        string $payloadHash,
    ): Payment {
        $eventType = $this->requiredString(
            data_get($payload, 'data.type'),
            'PayMongo webhook event type',
        );

        if ($eventType !== 'checkout_session.payment.paid') {
            throw new UnexpectedValueException(
                'Unsupported PayMongo webhook event type.',
            );
        }

        $checkoutId = $this->requiredString(
            data_get($payload, 'data.data.id'),
            'PayMongo Checkout Session ID',
        );

        $referenceNumber = $this->requiredString(
            data_get(
                $payload,
                'data.data.attributes.reference_number',
            ),
            'PayMongo order reference',
        );

        $paymentMethod = $this->requiredString(
            data_get(
                $payload,
                'data.data.attributes.metadata.payment_method',
            ),
            'PayMongo payment method metadata',
        );

        $paymentIntentId = $this->requiredString(
            data_get(
                $payload,
                'data.data.attributes.payment_intent.id',
            ),
            'PayMongo Payment Intent ID',
        );

        $paidPayment = $this->paidPayment($payload);

        $providerPaymentId = $this->requiredString(
            $paidPayment['id'] ?? null,
            'PayMongo Payment ID',
        );

        $amount = $this->requiredInteger(
            data_get(
                $paidPayment,
                'attributes.amount',
            ),
            'PayMongo paid amount',
        );

        $currency = strtoupper(
            $this->requiredString(
                data_get(
                    $paidPayment,
                    'attributes.currency',
                ),
                'PayMongo paid currency',
            ),
        );

        $liveMode = data_get(
            $payload,
            'data.livemode',
        );

        if (! is_bool($liveMode)) {
            throw new UnexpectedValueException(
                'PayMongo webhook livemode is invalid.',
            );
        }

        $paidAt = $this->paidAt($paidPayment);
        $becamePaid = false;

        $updatedPayment = DB::transaction(
            function () use (
                $checkoutId,
                $referenceNumber,
                $paymentMethod,
                $paymentIntentId,
                $providerPaymentId,
                $amount,
                $currency,
                $liveMode,
                $paidAt,
                $eventType,
                $payloadHash,
                &$becamePaid,
            ): Payment {
                $payment = Payment::query()
                    ->where(
                        'provider_checkout_id',
                        $checkoutId,
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $payment instanceof Payment) {
                    throw new UnexpectedValueException(
                        'PayMongo webhook does not match a local payment.',
                    );
                }

                $order = Order::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->order_id);

                if (
                    $payment->provider !== 'paymongo'
                    || ! $payment->method->usesPayMongo()
                    || ! $order->payment_method->usesPayMongo()
                ) {
                    throw new UnexpectedValueException(
                        'PayMongo webhook matched a non-PayMongo payment.',
                    );
                }

                if ($referenceNumber !== $order->order_number) {
                    throw new UnexpectedValueException(
                        'PayMongo order reference does not match the local order.',
                    );
                }

                if ($paymentMethod !== $payment->method->value) {
                    throw new UnexpectedValueException(
                        'PayMongo payment method does not match the local payment.',
                    );
                }

                if (
                    $amount !== (int) $payment->amount
                    || $amount !== (int) $order->grand_total
                ) {
                    throw new UnexpectedValueException(
                        'PayMongo paid amount does not match the local order.',
                    );
                }

                if (
                    ! is_string($payment->currency)
                    || strtoupper($payment->currency) !== $currency
                ) {
                    throw new UnexpectedValueException(
                        'PayMongo paid currency does not match the local payment.',
                    );
                }

                if (
                    $payment->provider_payment_intent_id !== null
                    && $payment->provider_payment_intent_id !== $paymentIntentId
                ) {
                    throw new UnexpectedValueException(
                        'PayMongo Payment Intent ID conflicts with the local payment.',
                    );
                }

                if (
                    $payment->provider_payment_id !== null
                    && $payment->provider_payment_id !== $providerPaymentId
                ) {
                    throw new UnexpectedValueException(
                        'PayMongo Payment ID conflicts with the local payment.',
                    );
                }

                $metadata = is_array($payment->metadata)
                    ? $payment->metadata
                    : [];

                $metadata['paymongo_reconciliation'] = [
                    'event_type' => $eventType,
                    'livemode' => $liveMode,
                    'payload_sha256' => $payloadHash,
                    'reference_number' => $referenceNumber,
                    'reconciled_at' => now()->toIso8601String(),
                ];

                $updates = [
                    'provider_payment_intent_id' => $paymentIntentId,
                    'provider_payment_id' => $providerPaymentId,
                    'reference' => $providerPaymentId,
                    'metadata' => $metadata,
                ];

                // A delayed duplicate paid webhook must never regress a
                // payment that has already completed a refund workflow.
                if ($payment->status !== PaymentStatus::Refunded) {
                    $becamePaid = $payment->status !== PaymentStatus::Paid;

                    $updates['status'] = PaymentStatus::Paid;
                    $updates['paid_at'] = $payment->paid_at ?? $paidAt;
                    $updates['failed_at'] = null;

                    $order->update([
                        'payment_status' => PaymentStatus::Paid,
                    ]);
                }

                $payment->update($updates);

                return $payment
                    ->refresh()
                    ->load('order');
            },
            3,
        );

        $order = $updatedPayment->order;

        if (
            $becamePaid
            && $order instanceof Order
        ) {
            $this->notifyCustomer($updatedPayment);
        }

        return $updatedPayment;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function paidPayment(array $payload): array
    {
        $payments = data_get(
            $payload,
            'data.data.attributes.payments',
            [],
        );

        if (! is_array($payments)) {
            throw new UnexpectedValueException(
                'PayMongo webhook payments payload is invalid.',
            );
        }

        $paidPayments = [];

        foreach ($payments as $payment) {
            if (! is_array($payment)) {
                continue;
            }

            if (
                data_get(
                    $payment,
                    'attributes.status',
                ) === 'paid'
            ) {
                $paidPayments[] = $payment;
            }
        }

        if (count($paidPayments) !== 1) {
            throw new UnexpectedValueException(
                'PayMongo webhook must contain exactly one paid payment.',
            );
        }

        return $paidPayments[0];
    }

    /**
     * @param  array<string, mixed>  $paidPayment
     */
    private function paidAt(array $paidPayment): CarbonInterface
    {
        $paidAt = data_get(
            $paidPayment,
            'attributes.paid_at',
        );

        if (is_int($paidAt)) {
            return Carbon::createFromTimestampUTC($paidAt);
        }

        if (
            is_string($paidAt)
            && ctype_digit($paidAt)
        ) {
            return Carbon::createFromTimestampUTC(
                (int) $paidAt,
            );
        }

        return now();
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
                new PaymentConfirmedNotification(
                    $payment,
                ),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function requiredString(
        mixed $value,
        string $name,
    ): string {
        if (! is_string($value)) {
            throw new UnexpectedValueException(
                "{$name} is missing or invalid.",
            );
        }

        $value = trim($value);

        if ($value === '') {
            throw new UnexpectedValueException(
                "{$name} is missing or invalid.",
            );
        }

        return $value;
    }

    private function requiredInteger(
        mixed $value,
        string $name,
    ): int {
        if (is_int($value)) {
            return $value;
        }

        if (
            is_string($value)
            && ctype_digit($value)
        ) {
            return (int) $value;
        }

        throw new UnexpectedValueException(
            "{$name} is missing or invalid.",
        );
    }
}
