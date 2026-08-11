<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Notifications\PaymentConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use JsonException;
use Tests\TestCase;

class PayMongoWebhookTest extends TestCase
{
    use RefreshDatabase;

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
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => null,
            'tax_rate_basis_points' => null,
            'paymongo_enabled' => true,
        ]);
    }

    public function test_valid_paid_webhook_reconciles_local_payment(): void
    {
        [$order, $product] = $this->placePendingPayMongoOrder();

        $response = $this->postWebhook(
            $this->paidWebhookPayload($order),
        );

        $response
            ->assertOk()
            ->assertJson([
                'received' => true,
            ]);

        $order->refresh();
        $payment = $order->payment()->firstOrFail();

        $this->assertSame(
            PaymentStatus::Paid,
            $payment->status,
        );
        $this->assertSame(
            PaymentStatus::Paid,
            $order->payment_status,
        );
        $this->assertSame(
            'pi_webhook_test',
            $payment->provider_payment_intent_id,
        );
        $this->assertSame(
            'pay_webhook_test',
            $payment->provider_payment_id,
        );
        $this->assertSame(
            'pay_webhook_test',
            $payment->reference,
        );
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(
            'checkout_session.payment.paid',
            data_get(
                $payment->metadata,
                'paymongo_reconciliation.event_type',
            ),
        );
        $this->assertSame(
            4,
            $product->fresh()->stock_quantity,
        );
        $this->assertDatabaseCount(
            'inventory_adjustments',
            1,
        );

        Notification::assertSentOnDemandTimes(
            PaymentConfirmedNotification::class,
            1,
        );
    }

    public function test_invalid_signature_is_rejected_without_mutating_payment(): void
    {
        [$order] = $this->placePendingPayMongoOrder();

        $payload = $this->paidWebhookPayload($order);
        $rawPayload = $this->encodePayload($payload);
        $timestamp = (string) now()->timestamp;

        $response = $this->postRawWebhook(
            rawPayload: $rawPayload,
            signature: "t={$timestamp},te=invalid,li=",
        );

        $response->assertUnauthorized();

        $order->refresh();

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );
        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment()->firstOrFail()->status,
        );

        Notification::assertSentOnDemandTimes(
            PaymentConfirmedNotification::class,
            0,
        );
    }

    public function test_amount_mismatch_fails_closed(): void
    {
        [$order, $product] = $this->placePendingPayMongoOrder();

        $payload = $this->paidWebhookPayload(
            $order,
            amount: $order->grand_total + 1,
        );

        $this
            ->postWebhook($payload)
            ->assertStatus(409);

        $order->refresh();

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );
        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment()->firstOrFail()->status,
        );
        $this->assertSame(
            4,
            $product->fresh()->stock_quantity,
        );
        $this->assertDatabaseCount(
            'inventory_adjustments',
            1,
        );
    }

    public function test_unknown_checkout_reference_fails_closed(): void
    {
        [$order] = $this->placePendingPayMongoOrder();

        $payload = $this->paidWebhookPayload($order);
        data_set(
            $payload,
            'data.data.id',
            'cs_unknown_test',
        );

        $this
            ->postWebhook($payload)
            ->assertStatus(409);

        $order->refresh();

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );
        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment()->firstOrFail()->status,
        );
    }

    public function test_currency_mismatch_fails_closed(): void
    {
        [$order] = $this->placePendingPayMongoOrder();

        $payload = $this->paidWebhookPayload($order);
        data_set(
            $payload,
            'data.data.attributes.payments.0.attributes.currency',
            'USD',
        );

        $this
            ->postWebhook($payload)
            ->assertStatus(409);

        $order->refresh();

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );
        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment()->firstOrFail()->status,
        );
    }

    public function test_already_paid_payment_is_idempotent_success(): void
    {
        [$order] = $this->placePendingPayMongoOrder();

        $payment = $order->payment()->firstOrFail();

        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now()->subMinute(),
        ]);

        $order->update([
            'payment_status' => PaymentStatus::Paid,
        ]);

        $this
            ->postWebhook(
                $this->paidWebhookPayload($order),
            )
            ->assertOk();

        $payment->refresh();
        $order->refresh();

        $this->assertSame(
            PaymentStatus::Paid,
            $payment->status,
        );
        $this->assertSame(
            PaymentStatus::Paid,
            $order->payment_status,
        );
        $this->assertSame(
            'pay_webhook_test',
            $payment->reference,
        );

        Notification::assertSentOnDemandTimes(
            PaymentConfirmedNotification::class,
            0,
        );
    }

    public function test_duplicate_paid_webhook_is_idempotent(): void
    {
        [$order, $product] = $this->placePendingPayMongoOrder();

        $payload = $this->paidWebhookPayload($order);

        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();

        $order->refresh();

        $this->assertSame(
            PaymentStatus::Paid,
            $order->payment_status,
        );
        $this->assertSame(
            PaymentStatus::Paid,
            $order->payment()->firstOrFail()->status,
        );
        $this->assertSame(
            4,
            $product->fresh()->stock_quantity,
        );
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount(
            'inventory_adjustments',
            1,
        );

        Notification::assertSentOnDemandTimes(
            PaymentConfirmedNotification::class,
            1,
        );
    }

    public function test_webhook_reconciliation_continues_when_new_paymongo_checkout_is_disabled(): void
    {
        [$order] = $this->placePendingPayMongoOrder();

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
                $this->paidWebhookPayload($order),
            )
            ->assertOk();

        $order->refresh();

        $this->assertSame(
            PaymentStatus::Paid,
            $order->payment_status,
        );
        $this->assertSame(
            PaymentStatus::Paid,
            $order->payment()->firstOrFail()->status,
        );
    }

    /**
     * @return array{0: Order, 1: Product}
     */
    private function placePendingPayMongoOrder(): array
    {
        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' => Http::response(
                $this->checkoutSessionResponse(
                    'cs_webhook_test',
                    'https://checkout.paymongo.com/cs_webhook_test',
                ),
                200,
            ),
        ]);

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

        return [
            $order,
            $product,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paidWebhookPayload(
        Order $order,
        ?int $amount = null,
    ): array {
        return [
            'event_type' => 'send.webhook',
            'data' => [
                'type' => 'checkout_session.payment.paid',
                'resource' => 'checkout_session',
                'livemode' => false,
                'organization_id' => 'org_test',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
                'data' => [
                    'id' => 'cs_webhook_test',
                    'type' => 'checkout_session',
                    'attributes' => [
                        'reference_number' => $order->order_number,
                        'metadata' => [
                            'order_number' => $order->order_number,
                            'payment_method' => PaymentMethod::GCash->value,
                        ],
                        'payment_intent' => [
                            'id' => 'pi_webhook_test',
                        ],
                        'payments' => [
                            [
                                'id' => 'pay_webhook_test',
                                'type' => 'payment',
                                'attributes' => [
                                    'amount' => $amount
                                        ?? $order->grand_total,
                                    'currency' => 'PHP',
                                    'status' => 'paid',
                                    'paid_at' => now()->timestamp,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postWebhook(array $payload): TestResponse
    {
        $rawPayload = $this->encodePayload($payload);

        return $this->postRawWebhook(
            rawPayload: $rawPayload,
            signature: $this->signature($rawPayload),
        );
    }

    private function postRawWebhook(
        string $rawPayload,
        string $signature,
    ): TestResponse {
        return $this->call(
            'POST',
            route('webhooks.paymongo'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => $signature,
            ],
            $rawPayload,
        );
    }

    private function signature(string $rawPayload): string
    {
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
    private function encodePayload(array $payload): string
    {
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

    /**
     * @return array<string, mixed>
     */
    private function checkoutSessionResponse(
        string $checkoutId,
        string $checkoutUrl,
    ): array {
        return [
            'data' => [
                'id' => $checkoutId,
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => $checkoutUrl,
                ],
            ],
        ];
    }
}
