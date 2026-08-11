<?php

namespace Tests\Feature\Payments;

use App\Actions\Payments\RefundPayMongoPayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Notifications\PaymentRefundedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use JsonException;
use Tests\TestCase;

class PayMongoRefundWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const CHECKOUT_URL =
        'https://api.paymongo.com/v2/checkout_sessions';

    private const REFUND_URL =
        'https://api.paymongo.com/refunds/ref_webhook_test';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Notification::fake();

        config()->set(
            'services.paymongo.admin_enabled',
            true,
        );
        config()->set(
            'services.paymongo.secret_key',
            'sk_test_example',
        );
        config()->set(
            'services.paymongo.webhook_secret',
            'whsk_test_example',
        );
        config()->set(
            'services.paymongo.available',
            true,
        );

        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 0,
            'free_shipping_threshold' => null,
            'tax_rate_basis_points' => null,
            'paymongo_enabled' => true,
        ]);
    }

    public function test_refund_succeeded_webhook_reconciles_local_payment(): void
    {
        $this->fakeProvider(
            refundStatus: 'succeeded',
        );

        $payment = $this->placePaidPayment();

        $payment->update([
            'refund_reference' => 'ref_webhook_test',
        ]);

        $this
            ->postWebhook(
                $this->refundWebhookPayload(),
            )
            ->assertOk()
            ->assertJson([
                'received' => true,
            ]);

        $payment->refresh();

        $this->assertSame(
            PaymentStatus::Refunded,
            $payment->status,
        );
        $this->assertSame(
            'ref_webhook_test',
            $payment->refund_reference,
        );
        $this->assertNotNull(
            $payment->refunded_at,
        );
        $this->assertSame(
            PaymentStatus::Refunded,
            $payment
                ->order()
                ->firstOrFail()
                ->payment_status,
        );

        Notification::assertSentOnDemandTimes(
            PaymentRefundedNotification::class,
            1,
        );
    }

    public function test_duplicate_refund_webhook_is_idempotent(): void
    {
        $this->fakeProvider(
            refundStatus: 'succeeded',
        );

        $payment = $this->placePaidPayment();

        $payment->update([
            'refund_reference' => 'ref_webhook_test',
        ]);

        $payload = $this->refundWebhookPayload();

        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();

        $payment->refresh();

        $this->assertSame(
            PaymentStatus::Refunded,
            $payment->status,
        );
        $this->assertSame(
            PaymentStatus::Refunded,
            $payment
                ->order()
                ->firstOrFail()
                ->payment_status,
        );

        Notification::assertSentOnDemandTimes(
            PaymentRefundedNotification::class,
            1,
        );
    }

    public function test_refund_reconciliation_continues_when_new_paymongo_operations_are_disabled(): void
    {
        $this->fakeProvider(
            refundStatus: 'succeeded',
        );

        $payment = $this->placePaidPayment();

        $payment->update([
            'refund_reference' => 'ref_webhook_test',
        ]);

        config()->set(
            'services.paymongo.admin_enabled',
            false,
        );
        config()->set(
            'services.paymongo.available',
            false,
        );

        StoreSetting::query()->update([
            'paymongo_enabled' => false,
        ]);

        $this
            ->postWebhook(
                $this->refundWebhookPayload(),
            )
            ->assertOk();

        $payment->refresh();

        $this->assertSame(
            PaymentStatus::Refunded,
            $payment->status,
        );
        $this->assertSame(
            PaymentStatus::Refunded,
            $payment
                ->order()
                ->firstOrFail()
                ->payment_status,
        );
    }

    public function test_failed_refund_update_clears_active_reference_and_allows_retry(): void
    {
        $this->fakeProvider(
            refundStatus: 'failed',
        );

        $payment = $this->placePaidPayment();

        $payment->update([
            'refund_reference' => 'ref_webhook_test',
        ]);

        $this
            ->postWebhook(
                $this->refundWebhookPayload(
                    eventType: 'payment.refund.updated',
                ),
            )
            ->assertOk();

        $payment->refresh();

        $this->assertSame(
            PaymentStatus::Paid,
            $payment->status,
        );
        $this->assertNull(
            $payment->refund_reference,
        );
        $this->assertNull(
            $payment->refunded_at,
        );
        $this->assertSame(
            PaymentStatus::Paid,
            $payment
                ->order()
                ->firstOrFail()
                ->payment_status,
        );
        $this->assertSame(
            'failed',
            data_get(
                $payment->metadata,
                'paymongo_refund.status',
            ),
        );

        $this->assertTrue(
            app(
                RefundPayMongoPayment::class,
            )->isEligible($payment),
        );

        Notification::assertSentOnDemandTimes(
            PaymentRefundedNotification::class,
            0,
        );
    }

    public function test_refund_amount_mismatch_fails_closed(): void
    {
        $this->fakeProvider(
            refundStatus: 'succeeded',
            refundAmount: 100_001,
        );

        $payment = $this->placePaidPayment();

        $payment->update([
            'refund_reference' => 'ref_webhook_test',
        ]);

        $this
            ->postWebhook(
                $this->refundWebhookPayload(),
            )
            ->assertStatus(409);

        $payment->refresh();

        $this->assertSame(
            PaymentStatus::Paid,
            $payment->status,
        );
        $this->assertSame(
            'ref_webhook_test',
            $payment->refund_reference,
        );
        $this->assertNull(
            $payment->refunded_at,
        );
        $this->assertSame(
            PaymentStatus::Paid,
            $payment
                ->order()
                ->firstOrFail()
                ->payment_status,
        );

        Notification::assertSentOnDemandTimes(
            PaymentRefundedNotification::class,
            0,
        );
    }

    private function fakeProvider(
        string $refundStatus,
        int $refundAmount = 100_000,
    ): void {
        Http::fake([
            self::CHECKOUT_URL => Http::response(
                [
                    'data' => [
                        'id' => 'cs_refund_webhook',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'checkout_url' => 'https://checkout.paymongo.com/cs_refund_webhook',
                        ],
                    ],
                ],
                200,
            ),

            self::REFUND_URL => Http::response(
                [
                    'data' => [
                        'id' => 'ref_webhook_test',
                        'type' => 'refund',
                        'attributes' => [
                            'payment_id' => 'pay_refund_webhook',
                            'amount' => $refundAmount,
                            'currency' => 'PHP',
                            'status' => $refundStatus,
                            'reason' => 'others',
                        ],
                    ],
                ],
                200,
            ),
        ]);
    }

    private function placePaidPayment(): Payment
    {
        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this
            ->withHeader(
                'X-Inertia',
                'true',
            )
            ->withSession([
                'cart.items' => [
                    $product->id => 1,
                ],
            ])
            ->post(
                route('checkout.store'),
                $this->checkoutPayload(),
            )
            ->assertStatus(409);

        $order = Order::query()
            ->with('payment')
            ->sole();

        $payment = $order->payment;

        $this->assertInstanceOf(
            Payment::class,
            $payment,
        );

        $payment->update([
            'status' => PaymentStatus::Paid,
            'provider_payment_intent_id' => 'pi_refund_webhook',
            'provider_payment_id' => 'pay_refund_webhook',
            'reference' => 'pay_refund_webhook',
            'paid_at' => now(),
        ]);

        $order->update([
            'payment_status' => PaymentStatus::Paid,
        ]);

        return $payment
            ->refresh()
            ->load('order');
    }

    /**
     * @return array<string, mixed>
     */
    private function refundWebhookPayload(
        string $eventType = 'refund.succeeded',
    ): array {
        return [
            'data' => [
                'id' => 'evt_refund_webhook',
                'type' => 'event',
                'attributes' => [
                    'type' => $eventType,
                    'livemode' => false,
                    'created_at' => now()->timestamp,
                    'updated_at' => now()->timestamp,
                    'data' => [
                        'id' => 'ref_webhook_test',
                        'type' => 'refund',
                        'attributes' => [
                            'payment_id' => 'pay_refund_webhook',
                            'amount' => 100_000,
                            'currency' => 'PHP',
                            'status' => 'succeeded',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postWebhook(
        array $payload,
    ): TestResponse {
        $rawPayload = $this->encodePayload(
            $payload,
        );

        return $this->call(
            'POST',
            route('webhooks.paymongo'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => $this->signature(
                    $rawPayload,
                ),
            ],
            $rawPayload,
        );
    }

    private function signature(
        string $rawPayload,
    ): string {
        $timestamp = (string) now()->timestamp;

        $signature = hash_hmac(
            'sha256',
            $timestamp.'.'.$rawPayload,
            'whsk_test_example',
        );

        return "t={$timestamp},te={$signature},li=";
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws JsonException
     */
    private function encodePayload(
        array $payload,
    ): string {
        return json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '09171234567',
            'shipping_address_line_1' => '123 Test Street',
            'shipping_address_line_2' => null,
            'shipping_city' => 'Manila',
            'shipping_province' => 'Metro Manila',
            'shipping_postal_code' => '1000',
            'shipping_method' => ShippingMethod::FlatRate->value,
            'payment_method' => PaymentMethod::GCash->value,
            'customer_notes' => null,
        ];
    }
}
