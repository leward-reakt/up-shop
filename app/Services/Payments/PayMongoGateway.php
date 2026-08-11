<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

class PayMongoGateway
{
    private const BASE_URL = 'https://api.paymongo.com';

    private const REFUNDS_PATH = '/refunds';

    /**
     * @return array{
     *     checkout_id: string,
     *     checkout_url: string
     * }
     */
    public function createCheckoutSession(
        PaymentMethod $method,
        int $amount,
        string $currency,
        string $referenceNumber,
        string $successUrl,
        string $cancelUrl,
        string $idempotencyKey,
    ): array {
        if (! $method->usesPayMongo()) {
            throw new InvalidArgumentException(
                'The selected payment method is not handled by PayMongo.',
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'PayMongo checkout amount must be greater than zero.',
            );
        }

        $currency = strtoupper(trim($currency));

        if ($currency !== 'PHP') {
            throw new InvalidArgumentException(
                'PayMongo checkout currently requires PHP currency.',
            );
        }

        $referenceNumber = trim($referenceNumber);

        if ($referenceNumber === '') {
            throw new InvalidArgumentException(
                'PayMongo checkout requires an order reference.',
            );
        }

        $this->assertAbsoluteHttpUrl(
            $successUrl,
            'success URL',
        );

        $this->assertAbsoluteHttpUrl(
            $cancelUrl,
            'cancel URL',
        );

        $this->assertIdempotencyKey($idempotencyKey);

        $response = $this
            ->request()
            ->withHeaders([
                'Idempotency-Key' => $idempotencyKey,
            ])
            ->post(
                '/v2/checkout_sessions',
                [
                    'data' => [
                        'attributes' => [
                            // The local order remains the source of truth for
                            // item, discount, shipping, and tax snapshots.
                            // PayMongo receives one final payable amount.
                            'line_items' => [
                                [
                                    'name' => "Order {$referenceNumber}",
                                    'amount' => $amount,
                                    'currency' => $currency,
                                    'quantity' => 1,
                                ],
                            ],

                            'payment_method_types' => [
                                $this->providerPaymentMethod($method),
                            ],

                            'success_url' => $successUrl,
                            'cancel_url' => $cancelUrl,

                            'reference_number' => $referenceNumber,

                            'send_email_receipt' => false,
                            'show_description' => true,
                            'show_line_items' => true,

                            'description' => "Payment for {$referenceNumber}",

                            'metadata' => [
                                'order_number' => $referenceNumber,
                                'payment_method' => $method->value,
                            ],
                        ],
                    ],
                ],
            );

        $response->throw();

        $checkoutId = $response->json('data.id');
        $checkoutUrl = $response->json(
            'data.attributes.checkout_url',
        );

        if (
            ! is_string($checkoutId)
            || trim($checkoutId) === ''
            || ! is_string($checkoutUrl)
            || trim($checkoutUrl) === ''
        ) {
            throw new UnexpectedValueException(
                'PayMongo returned an invalid Checkout Session response.',
            );
        }

        $this->assertAbsoluteHttpUrl(
            $checkoutUrl,
            'checkout URL',
        );

        return [
            'checkout_id' => trim($checkoutId),
            'checkout_url' => trim($checkoutUrl),
        ];
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
    public function retrieveCheckoutSession(
        string $checkoutId,
    ): array {
        $checkoutId = trim($checkoutId);

        if ($checkoutId === '') {
            throw new InvalidArgumentException(
                'PayMongo Checkout Session ID is required.',
            );
        }

        $response = $this
            ->request()
            ->get(
                '/v1/checkout_sessions/'.rawurlencode($checkoutId),
            );

        $response->throw();

        $returnedId = $response->json('data.id');
        $checkoutUrl = $response->json(
            'data.attributes.checkout_url',
        );
        $status = $response->json(
            'data.attributes.status',
        );
        $referenceNumber = $response->json(
            'data.attributes.reference_number',
        );
        $payments = $response->json(
            'data.attributes.payments',
            [],
        );

        if (
            ! is_string($returnedId)
            || trim($returnedId) === ''
            || ! is_string($checkoutUrl)
            || trim($checkoutUrl) === ''
            || ! is_string($status)
            || ! in_array($status, ['active', 'expired'], true)
        ) {
            throw new UnexpectedValueException(
                'PayMongo returned an invalid Checkout Session resource.',
            );
        }

        $this->assertAbsoluteHttpUrl(
            $checkoutUrl,
            'checkout URL',
        );

        $hasPaidPayment = false;

        if (is_array($payments)) {
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
                    $hasPaidPayment = true;

                    break;
                }
            }
        }

        return [
            'checkout_id' => trim($returnedId),
            'checkout_url' => trim($checkoutUrl),
            'status' => $status,
            'reference_number' => is_string($referenceNumber)
                ? trim($referenceNumber)
                : null,
            'has_paid_payment' => $hasPaidPayment,
        ];
    }

    public function expireCheckoutSession(
        string $checkoutId,
    ): void {
        $checkoutId = trim($checkoutId);

        if ($checkoutId === '') {
            throw new InvalidArgumentException(
                'PayMongo Checkout Session ID is required.',
            );
        }

        $response = $this
            ->request()
            ->post(
                '/v1/checkout_sessions/'
                .rawurlencode($checkoutId)
                .'/expire',
            );

        $response->throw();
    }

    /**
     * @return array{
     *     id: string,
     *     payment_id: string,
     *     amount: int,
     *     currency: string,
     *     status: string
     * }
     */
    public function refundPayment(
        string $paymentId,
        int $amount,
        string $idempotencyKey,
        ?string $notes = null,
    ): array {
        $paymentId = trim($paymentId);

        if ($paymentId === '') {
            throw new InvalidArgumentException(
                'PayMongo Payment ID is required.',
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'PayMongo refund amount must be greater than zero.',
            );
        }

        $this->assertIdempotencyKey($idempotencyKey);

        $notes = $notes === null
            ? null
            : trim($notes);

        if ($notes !== null && strlen($notes) > 255) {
            throw new InvalidArgumentException(
                'PayMongo refund notes may not exceed 255 characters.',
            );
        }

        $attributes = [
            'amount' => $amount,
            'payment_id' => $paymentId,
            'reason' => 'others',
        ];

        if ($notes !== null && $notes !== '') {
            $attributes['notes'] = $notes;
        }

        $response = $this
            ->request()
            ->withHeaders([
                'Idempotency-Key' => $idempotencyKey,
            ])
            ->post(
                self::REFUNDS_PATH,
                [
                    'data' => [
                        'attributes' => $attributes,
                    ],
                ],
            );

        $response->throw();

        return $this->normalizeRefundResponse($response);
    }

    /**
     * @return array{
     *     id: string,
     *     payment_id: string,
     *     amount: int,
     *     currency: string,
     *     status: string
     * }
     */
    public function retrieveRefund(
        string $refundId,
    ): array {
        $refundId = trim($refundId);

        if ($refundId === '') {
            throw new InvalidArgumentException(
                'PayMongo Refund ID is required.',
            );
        }

        $response = $this
            ->request()
            ->get(
                self::REFUNDS_PATH.'/'.rawurlencode($refundId),
            );

        $response->throw();

        return $this->normalizeRefundResponse($response);
    }

    public function webhookLiveMode(): bool
    {
        $secretKey = $this->secretKey();

        if (str_starts_with($secretKey, 'sk_live_')) {
            return true;
        }

        if (str_starts_with($secretKey, 'sk_test_')) {
            return false;
        }

        throw new LogicException(
            'PayMongo secret key does not identify test or live mode.',
        );
    }

    public function verifyWebhookSignature(
        string $rawPayload,
        string $signatureHeader,
        bool $liveMode,
    ): bool {
        if ($rawPayload === '') {
            return false;
        }

        $webhookSecret = config(
            'services.paymongo.webhook_secret',
        );

        if (
            ! is_string($webhookSecret)
            || trim($webhookSecret) === ''
        ) {
            throw new LogicException(
                'PayMongo webhook secret is not configured.',
            );
        }

        $signatureParts = [];

        foreach (explode(',', $signatureHeader) as $part) {
            $pair = explode('=', trim($part), 2);

            if (count($pair) !== 2) {
                continue;
            }

            [$key, $value] = $pair;

            $signatureParts[trim($key)] = trim($value);
        }

        $timestamp = $signatureParts['t'] ?? null;
        $providedSignature = $signatureParts[
            $liveMode ? 'li' : 'te'
        ] ?? null;

        if (
            ! is_string($timestamp)
            || ! ctype_digit($timestamp)
            || ! is_string($providedSignature)
            || $providedSignature === ''
        ) {
            return false;
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $timestamp.'.'.$rawPayload,
            trim($webhookSecret),
        );

        return hash_equals(
            $expectedSignature,
            $providedSignature,
        );
    }

    /**
     * @return array{
     *     id: string,
     *     payment_id: string,
     *     amount: int,
     *     currency: string,
     *     status: string
     * }
     */
    private function normalizeRefundResponse(
        Response $response,
    ): array {
        $id = $response->json('data.id');
        $type = $response->json('data.type');
        $paymentId = $response->json(
            'data.attributes.payment_id',
        );
        $amount = $this->integerValue(
            $response->json(
                'data.attributes.amount',
            ),
            'PayMongo refund amount',
        );
        $currency = $response->json(
            'data.attributes.currency',
        );
        $status = $response->json(
            'data.attributes.status',
        );

        if (
            ! is_string($id)
            || trim($id) === ''
            || ($type !== null && $type !== 'refund')
            || ! is_string($paymentId)
            || trim($paymentId) === ''
            || $amount <= 0
            || ! is_string($currency)
            || strtoupper(trim($currency)) !== 'PHP'
            || ! is_string($status)
            || ! in_array(
                $status,
                [
                    'pending',
                    'processing',
                    'succeeded',
                    'failed',
                ],
                true,
            )
        ) {
            throw new UnexpectedValueException(
                'PayMongo returned an invalid Refund resource.',
            );
        }

        return [
            'id' => trim($id),
            'payment_id' => trim($paymentId),
            'amount' => $amount,
            'currency' => strtoupper(trim($currency)),
            'status' => $status,
        ];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withBasicAuth(
                $this->secretKey(),
                '',
            )
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15);
    }

    private function secretKey(): string
    {
        $secretKey = config(
            'services.paymongo.secret_key',
        );

        if (
            ! is_string($secretKey)
            || trim($secretKey) === ''
        ) {
            throw new LogicException(
                'PayMongo secret key is not configured.',
            );
        }

        return trim($secretKey);
    }

    private function providerPaymentMethod(
        PaymentMethod $method,
    ): string {
        return match ($method) {
            PaymentMethod::GCash => 'gcash',
            PaymentMethod::Maya => 'paymaya',

            default => throw new InvalidArgumentException(
                'The selected payment method is not handled by PayMongo.',
            ),
        };
    }

    private function assertIdempotencyKey(
        string $idempotencyKey,
    ): void {
        $idempotencyKey = trim($idempotencyKey);

        if (
            $idempotencyKey === ''
            || strlen($idempotencyKey) > 255
        ) {
            throw new InvalidArgumentException(
                'PayMongo idempotency key must contain 1 to 255 characters.',
            );
        }
    }

    private function integerValue(
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

    private function assertAbsoluteHttpUrl(
        string $url,
        string $name,
    ): void {
        $url = trim($url);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(
                "PayMongo {$name} must be a valid absolute URL.",
            );
        }

        $scheme = strtolower(
            (string) parse_url(
                $url,
                PHP_URL_SCHEME,
            ),
        );

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException(
                "PayMongo {$name} must use HTTP or HTTPS.",
            );
        }
    }
}
