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
    private const CREATE_CHECKOUT_SESSION_URL =
        'https://api.paymongo.com/v2/checkout_sessions';

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
    ): array {
        if (! $method->usesPayMongo()) {
            throw new InvalidArgumentException(
                'The selected payment method is not handled by PayMongo.',
            );
        }

        if ($amount < 1) {
            throw new InvalidArgumentException(
                'PayMongo checkout amount must be greater than zero.',
            );
        }

        $currency = strtoupper(
            trim($currency),
        );

        if ($currency !== 'PHP') {
            throw new InvalidArgumentException(
                'PayMongo checkout currently requires PHP currency.',
            );
        }

        $referenceNumber = trim($referenceNumber);

        if ($referenceNumber === '') {
            throw new InvalidArgumentException(
                'PayMongo checkout requires a reference number.',
            );
        }

        $this->assertValidUrl(
            $successUrl,
            'success URL',
        );

        $this->assertValidUrl(
            $cancelUrl,
            'cancel URL',
        );

        $response = $this
            ->request()
            ->post(
                self::CREATE_CHECKOUT_SESSION_URL,
                [
                    'data' => [
                        'attributes' => [
                            'line_items' => [
                                [
                                    'name' => "Order {$referenceNumber}",
                                    'amount' => $amount,
                                    'currency' => $currency,
                                    'quantity' => 1,
                                ],
                            ],
                            'payment_method_types' => [
                                $this->providerPaymentMethod(
                                    $method,
                                ),
                            ],
                            'success_url' => $successUrl,
                            'cancel_url' => $cancelUrl,
                            'reference_number' => $referenceNumber,
                            'send_email_receipt' => false,
                        ],
                    ],
                ],
            )
            ->throw();

        $checkoutId = $response->json(
            'data.id',
        );

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
                'PayMongo returned an invalid checkout session response.',
            );
        }

        return [
            'checkout_id' => $checkoutId,
            'checkout_url' => $checkoutUrl,
        ];
    }

    private function request(): PendingRequest
    {
        $secretKey = trim(
            (string) config(
                'services.paymongo.secret_key',
                '',
            ),
        );

        if ($secretKey === '') {
            throw new LogicException(
                'PayMongo secret key is not configured.',
            );
        }

        return Http::withBasicAuth(
            $secretKey,
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

            PaymentMethod::CashOnDelivery,
            PaymentMethod::BankTransfer => throw new InvalidArgumentException(
                'The selected payment method is not handled by PayMongo.',
            ),
        };
    }

    private function assertValidUrl(
        string $url,
        string $label,
    ): void {
        $url = trim($url);

        $scheme = parse_url(
            $url,
            PHP_URL_SCHEME,
        );

        if (
            filter_var(
                $url,
                FILTER_VALIDATE_URL,
            ) === false
            || ! in_array(
                $scheme,
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                "PayMongo {$label} must be a valid absolute HTTP URL.",
            );
        }
    }
}
