<?php

namespace App\Actions\Payments;

use App\Models\Payment;
use App\Services\Payments\PayMongoGateway;
use UnexpectedValueException;

class ReconcilePayMongoRefundWebhook
{
    private const EVENT_TYPES = [
        'refund.succeeded',
        'payment.refunded',
        'payment.refund.updated',
    ];

    public function __construct(
        private readonly PayMongoGateway $gateway,
        private readonly RefundPayMongoPayment $refundPayMongoPayment,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        array $payload,
    ): Payment {
        $eventType = $this->eventType($payload);

        if (! in_array($eventType, self::EVENT_TYPES, true)) {
            throw new UnexpectedValueException(
                'Unsupported PayMongo refund webhook event type.',
            );
        }

        $resource = $this->eventResource($payload);

        $resourceType = $this->requiredString(
            $resource['type'] ?? null,
            'PayMongo refund webhook resource type',
        );

        if ($resourceType === 'refund') {
            $refundId = $this->requiredString(
                $resource['id'] ?? null,
                'PayMongo Refund ID',
            );

            $refund = $this->gateway->retrieveRefund(
                $refundId,
            );

            $payment = $this->paymentByProviderId(
                $refund['payment_id'],
            );

            return $this->refundPayMongoPayment->reconcile(
                payment: $payment,
                refund: $refund,
            );
        }

        if ($resourceType !== 'payment') {
            throw new UnexpectedValueException(
                'PayMongo refund webhook contains an unsupported resource.',
            );
        }

        $providerPaymentId = $this->requiredString(
            $resource['id'] ?? null,
            'PayMongo Payment ID',
        );

        $payment = $this->paymentByProviderId(
            $providerPaymentId,
        );

        $refundId = $payment->refund_reference
            ?? $this->matchingFullRefundId(
                resource: $resource,
                payment: $payment,
            );

        if ($refundId === null) {
            throw new UnexpectedValueException(
                'PayMongo refund webhook does not identify one full refund for the local payment.',
            );
        }

        $refund = $this->gateway->retrieveRefund(
            $refundId,
        );

        return $this->refundPayMongoPayment->reconcile(
            payment: $payment,
            refund: $refund,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function eventType(
        array $payload,
    ): string {
        $eventType = data_get(
            $payload,
            'data.attributes.type',
        );

        if (
            ! is_string($eventType)
            || trim($eventType) === ''
        ) {
            $eventType = data_get(
                $payload,
                'data.type',
            );
        }

        return $this->requiredString(
            $eventType,
            'PayMongo webhook event type',
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function eventResource(
        array $payload,
    ): array {
        $resource = data_get(
            $payload,
            'data.attributes.data',
        );

        if (! is_array($resource)) {
            $resource = data_get(
                $payload,
                'data.data',
            );
        }

        if (! is_array($resource)) {
            throw new UnexpectedValueException(
                'PayMongo refund webhook resource is missing or invalid.',
            );
        }

        return $resource;
    }

    private function paymentByProviderId(
        string $providerPaymentId,
    ): Payment {
        $payment = Payment::query()
            ->where(
                'provider_payment_id',
                $providerPaymentId,
            )
            ->first();

        if (! $payment instanceof Payment) {
            throw new UnexpectedValueException(
                'PayMongo refund webhook does not match a local payment.',
            );
        }

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function matchingFullRefundId(
        array $resource,
        Payment $payment,
    ): ?string {
        $refunds = data_get(
            $resource,
            'attributes.refunds',
            [],
        );

        if (! is_array($refunds)) {
            return null;
        }

        $matchingIds = [];

        foreach ($refunds as $refund) {
            if (! is_array($refund)) {
                continue;
            }

            $id = $refund['id'] ?? null;
            $amount = data_get(
                $refund,
                'attributes.amount',
            );

            if (
                is_string($amount)
                && ctype_digit($amount)
            ) {
                $amount = (int) $amount;
            }

            if (
                is_string($id)
                && trim($id) !== ''
                && is_int($amount)
                && $amount === $payment->amount
            ) {
                $matchingIds[] = trim($id);
            }
        }

        $matchingIds = array_values(
            array_unique($matchingIds),
        );

        return count($matchingIds) === 1
            ? $matchingIds[0]
            : null;
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
}
