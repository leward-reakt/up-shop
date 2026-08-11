<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

class PayMongoGateway
{
    private const BASE_URL = 'https://api.paymongo.com';

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

        $idempotencyKey = trim($idempotencyKey);

        if (
            $idempotencyKey === ''
            || strlen($idempotencyKey) > 255
        ) {
            throw new InvalidArgumentException(
                'PayMongo idempotency key must contain 1 to 255 characters.',
            );
        }

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

    private function request(): PendingRequest
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

        return Http::baseUrl(self::BASE_URL)
            ->withBasicAuth(
                trim($secretKey),
                '',
            )
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15);
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
