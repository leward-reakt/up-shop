<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentMethod;
use App\Services\Payments\PayMongoGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;
use UnexpectedValueException;

class PayMongoGatewayTest extends TestCase
{
    private const CHECKOUT_URL =
        'https://api.paymongo.com/v2/checkout_sessions';

    private const REFUNDS_URL =
        'https://api.paymongo.com/refunds';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config()->set(
            'services.paymongo.secret_key',
            'sk_test_example',
        );
    }

    public function test_it_creates_a_gcash_checkout_session(): void
    {
        Http::fake([
            self::CHECKOUT_URL => Http::response(
                [
                    'data' => [
                        'id' => 'cs_test_123',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'checkout_url' => 'https://checkout.paymongo.com/cs_test_123',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $result = app(
            PayMongoGateway::class,
        )->createCheckoutSession(
            method: PaymentMethod::GCash,
            amount: 125_050,
            currency: 'PHP',
            referenceNumber: 'UP-TEST-001',
            successUrl: 'https://shop.example.com/checkout/payment/success',
            cancelUrl: 'https://shop.example.com/checkout/payment/cancelled',
            idempotencyKey: 'checkout-test-gcash',
        );

        $this->assertSame(
            [
                'checkout_id' => 'cs_test_123',
                'checkout_url' => 'https://checkout.paymongo.com/cs_test_123',
            ],
            $result,
        );

        Http::assertSent(
            function (Request $request): bool {
                return $request->method() === 'POST'
                    && $request->url() === self::CHECKOUT_URL
                    && $request->hasHeader(
                        'Authorization',
                        'Basic '.base64_encode(
                            'sk_test_example:',
                        ),
                    )
                    && $request->hasHeader(
                        'Idempotency-Key',
                        'checkout-test-gcash',
                    )
                    && $request['data']['attributes']['line_items'] === [
                        [
                            'name' => 'Order UP-TEST-001',
                            'amount' => 125_050,
                            'currency' => 'PHP',
                            'quantity' => 1,
                        ],
                    ]
                    && $request['data']['attributes']['payment_method_types']
                        === ['gcash']
                    && $request['data']['attributes']['reference_number']
                        === 'UP-TEST-001'
                    && $request['data']['attributes']['send_email_receipt']
                        === false;
            },
        );
    }

    public function test_it_maps_maya_to_paymongos_paymaya_identifier(): void
    {
        Http::fake([
            self::CHECKOUT_URL => Http::response(
                [
                    'data' => [
                        'id' => 'cs_test_maya',
                        'attributes' => [
                            'checkout_url' => 'https://checkout.paymongo.com/cs_test_maya',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        app(
            PayMongoGateway::class,
        )->createCheckoutSession(
            method: PaymentMethod::Maya,
            amount: 100_000,
            currency: 'PHP',
            referenceNumber: 'UP-TEST-MAYA',
            successUrl: 'https://shop.example.com/success',
            cancelUrl: 'https://shop.example.com/cancel',
            idempotencyKey: 'checkout-test-maya',
        );

        Http::assertSent(
            fn (Request $request): bool => (
                $request['data']['attributes']['payment_method_types']
                === ['paymaya']
            ),
        );
    }

    public function test_it_rejects_manual_payment_methods(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        app(
            PayMongoGateway::class,
        )->createCheckoutSession(
            method: PaymentMethod::CashOnDelivery,
            amount: 100_000,
            currency: 'PHP',
            referenceNumber: 'UP-TEST-COD',
            successUrl: 'https://shop.example.com/success',
            cancelUrl: 'https://shop.example.com/cancel',
            idempotencyKey: 'checkout-test-cod',
        );
    }

    public function test_it_rejects_non_php_currency(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        app(
            PayMongoGateway::class,
        )->createCheckoutSession(
            method: PaymentMethod::GCash,
            amount: 100_000,
            currency: 'USD',
            referenceNumber: 'UP-TEST-USD',
            successUrl: 'https://shop.example.com/success',
            cancelUrl: 'https://shop.example.com/cancel',
            idempotencyKey: 'checkout-test-usd',
        );
    }

    public function test_it_rejects_non_positive_amounts(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        app(
            PayMongoGateway::class,
        )->createCheckoutSession(
            method: PaymentMethod::GCash,
            amount: 0,
            currency: 'PHP',
            referenceNumber: 'UP-TEST-ZERO',
            successUrl: 'https://shop.example.com/success',
            cancelUrl: 'https://shop.example.com/cancel',
            idempotencyKey: 'checkout-test-zero',
        );
    }

    public function test_it_requires_a_secret_key(): void
    {
        config()->set(
            'services.paymongo.secret_key',
            '',
        );

        try {
            app(
                PayMongoGateway::class,
            )->createCheckoutSession(
                method: PaymentMethod::GCash,
                amount: 100_000,
                currency: 'PHP',
                referenceNumber: 'UP-TEST-NOKEY',
                successUrl: 'https://shop.example.com/success',
                cancelUrl: 'https://shop.example.com/cancel',
                idempotencyKey: 'checkout-test-no-key',
            );

            $this->fail(
                'Expected missing PayMongo credentials to be rejected.',
            );
        } catch (LogicException) {
            Http::assertNothingSent();
        }
    }

    public function test_provider_errors_are_not_silently_accepted(): void
    {
        Http::fake([
            self::CHECKOUT_URL => Http::response(
                [
                    'errors' => [
                        [
                            'code' => 'provider_error',
                            'detail' => 'Unable to create checkout session.',
                        ],
                    ],
                ],
                500,
            ),
        ]);

        $this->expectException(
            RequestException::class,
        );

        app(
            PayMongoGateway::class,
        )->createCheckoutSession(
            method: PaymentMethod::GCash,
            amount: 100_000,
            currency: 'PHP',
            referenceNumber: 'UP-TEST-ERROR',
            successUrl: 'https://shop.example.com/success',
            cancelUrl: 'https://shop.example.com/cancel',
            idempotencyKey: 'checkout-test-error',
        );
    }

    public function test_malformed_success_response_is_rejected(): void
    {
        Http::fake([
            self::CHECKOUT_URL => Http::response(
                [
                    'data' => [
                        'id' => 'cs_test_missing_url',
                        'attributes' => [],
                    ],
                ],
                200,
            ),
        ]);

        $this->expectException(
            UnexpectedValueException::class,
        );

        app(
            PayMongoGateway::class,
        )->createCheckoutSession(
            method: PaymentMethod::GCash,
            amount: 100_000,
            currency: 'PHP',
            referenceNumber: 'UP-TEST-MALFORMED',
            successUrl: 'https://shop.example.com/success',
            cancelUrl: 'https://shop.example.com/cancel',
            idempotencyKey: 'checkout-test-malformed',
        );
    }

    public function test_it_creates_a_full_refund(): void
    {
        Http::fake([
            self::REFUNDS_URL => Http::response(
                $this->refundResponse(
                    id: 'ref_test_123',
                    paymentId: 'pay_test_123',
                    amount: 125_050,
                    status: 'processing',
                ),
                200,
            ),
        ]);

        $result = app(
            PayMongoGateway::class,
        )->refundPayment(
            paymentId: 'pay_test_123',
            amount: 125_050,
            idempotencyKey: 'refund-test-123',
            notes: 'Full refund for UP-TEST-001.',
        );

        $this->assertSame(
            [
                'id' => 'ref_test_123',
                'payment_id' => 'pay_test_123',
                'amount' => 125_050,
                'currency' => 'PHP',
                'status' => 'processing',
            ],
            $result,
        );

        Http::assertSent(
            function (Request $request): bool {
                return $request->method() === 'POST'
                    && $request->url() === self::REFUNDS_URL
                    && $request->hasHeader(
                        'Idempotency-Key',
                        'refund-test-123',
                    )
                    && $request['data']['attributes']['payment_id']
                        === 'pay_test_123'
                    && $request['data']['attributes']['amount']
                        === 125_050
                    && $request['data']['attributes']['reason']
                        === 'others'
                    && $request['data']['attributes']['notes']
                        === 'Full refund for UP-TEST-001.';
            },
        );
    }

    public function test_it_retrieves_a_refund(): void
    {
        Http::fake([
            self::REFUNDS_URL.'/ref_test_123' => Http::response(
                $this->refundResponse(
                    id: 'ref_test_123',
                    paymentId: 'pay_test_123',
                    amount: 125_050,
                    status: 'succeeded',
                ),
                200,
            ),
        ]);

        $result = app(
            PayMongoGateway::class,
        )->retrieveRefund(
            'ref_test_123',
        );

        $this->assertSame(
            [
                'id' => 'ref_test_123',
                'payment_id' => 'pay_test_123',
                'amount' => 125_050,
                'currency' => 'PHP',
                'status' => 'succeeded',
            ],
            $result,
        );
    }

    public function test_refund_provider_errors_are_not_silently_accepted(): void
    {
        Http::fake([
            self::REFUNDS_URL => Http::response(
                [
                    'errors' => [
                        [
                            'code' => 'payment_not_refundable',
                            'detail' => 'Payment cannot be refunded.',
                        ],
                    ],
                ],
                422,
            ),
        ]);

        $this->expectException(
            RequestException::class,
        );

        app(
            PayMongoGateway::class,
        )->refundPayment(
            paymentId: 'pay_test_123',
            amount: 125_050,
            idempotencyKey: 'refund-test-error',
        );
    }

    public function test_malformed_refund_response_is_rejected(): void
    {
        Http::fake([
            self::REFUNDS_URL => Http::response(
                [
                    'data' => [
                        'id' => 'ref_bad',
                        'type' => 'refund',
                        'attributes' => [
                            'payment_id' => 'pay_test_123',
                            'amount' => 125_050,
                            'currency' => 'PHP',
                            'status' => 'unknown',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $this->expectException(
            UnexpectedValueException::class,
        );

        app(
            PayMongoGateway::class,
        )->refundPayment(
            paymentId: 'pay_test_123',
            amount: 125_050,
            idempotencyKey: 'refund-test-malformed',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function refundResponse(
        string $id,
        string $paymentId,
        int $amount,
        string $status,
    ): array {
        return [
            'data' => [
                'id' => $id,
                'type' => 'refund',
                'attributes' => [
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                    'currency' => 'PHP',
                    'status' => $status,
                    'reason' => 'others',
                    'notes' => 'Full refund.',
                ],
            ],
        ];
    }
}
