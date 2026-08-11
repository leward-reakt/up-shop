<?php

namespace Tests\Feature\Payments;

use App\Actions\Orders\CancelOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayMongoOrderCancellationTest extends TestCase
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

    public function test_pending_paymongo_order_expires_checkout_before_local_cancellation(): void
    {
        [$order, $product] = $this->placeOrder(
            PaymentMethod::GCash,
        );

        config()->set(
            'services.paymongo.available',
            false,
        );

        StoreSetting::query()->update([
            'paymongo_enabled' => false,
        ]);

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_cancel_test' => Http::response(
                $this->retrievedCheckoutSessionResponse(
                    $order,
                    status: 'active',
                ),
                200,
            ),

            'https://api.paymongo.com/v1/checkout_sessions/cs_cancel_test/expire' => Http::response(
                $this->expiredCheckoutSessionResponse(),
                200,
            ),
        ]);

        $actor = User::factory()->create();

        $cancelledOrder = app(CancelOrder::class)->handle(
            order: $order,
            user: $actor,
        );

        $this->assertSame(
            OrderStatus::Cancelled,
            $cancelledOrder->order_status,
        );
        $this->assertSame(
            PaymentStatus::Cancelled,
            $cancelledOrder->payment_status,
        );
        $this->assertSame(
            PaymentStatus::Cancelled,
            $cancelledOrder->payment?->status,
        );
        $this->assertSame(
            5,
            $product->fresh()->stock_quantity,
        );
        $this->assertDatabaseCount(
            'inventory_adjustments',
            2,
        );

        Http::assertSent(
            fn (ClientRequest $request): bool => $request->url()
                    === 'https://api.paymongo.com/v1/checkout_sessions/cs_cancel_test/expire'
                && $request->method() === 'POST',
        );
    }

    public function test_provider_paid_payment_blocks_order_cancellation(): void
    {
        [$order, $product] = $this->placeOrder(
            PaymentMethod::GCash,
        );

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_cancel_test' => Http::response(
                $this->retrievedCheckoutSessionResponse(
                    $order,
                    status: 'active',
                    hasPaidPayment: true,
                ),
                200,
            ),
        ]);

        $actor = User::factory()->create();

        try {
            app(CancelOrder::class)->handle(
                order: $order,
                user: $actor,
            );

            $this->fail(
                'Expected PayMongo paid state to block cancellation.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'order',
                $exception->errors(),
            );
        }

        $order->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $order->order_status,
        );
        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );
        $this->assertSame(
            4,
            $product->fresh()->stock_quantity,
        );
        $this->assertDatabaseCount(
            'inventory_adjustments',
            1,
        );

        Http::assertNotSent(
            fn (ClientRequest $request): bool => str_ends_with(
                $request->url(),
                '/expire',
            ),
        );
    }

    public function test_locally_paid_paymongo_order_cannot_use_unpaid_cancellation_path(): void
    {
        [$order, $product] = $this->placeOrder(
            PaymentMethod::GCash,
        );

        $payment = $order->payment()->firstOrFail();

        $payment->update([
            'status' => PaymentStatus::Paid,
            'reference' => 'pay_paid_local_test',
            'paid_at' => now(),
        ]);

        $order->update([
            'payment_status' => PaymentStatus::Paid,
        ]);

        Http::fake();

        $actor = User::factory()->create();

        try {
            app(CancelOrder::class)->handle(
                order: $order,
                user: $actor,
            );

            $this->fail(
                'Expected local paid state to block cancellation.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'order',
                $exception->errors(),
            );
        }

        $order->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $order->order_status,
        );
        $this->assertSame(
            PaymentStatus::Paid,
            $order->payment_status,
        );
        $this->assertSame(
            4,
            $product->fresh()->stock_quantity,
        );
        $this->assertDatabaseCount(
            'inventory_adjustments',
            1,
        );

        Http::assertNothingSent();
    }

    public function test_provider_failure_fails_closed_without_restoring_stock(): void
    {
        [$order, $product] = $this->placeOrder(
            PaymentMethod::GCash,
        );

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_cancel_test' => Http::response(
                [
                    'errors' => [
                        [
                            'code' => 'provider_error',
                            'detail' => 'Temporary failure.',
                        ],
                    ],
                ],
                500,
            ),
        ]);

        $actor = User::factory()->create();

        try {
            app(CancelOrder::class)->handle(
                order: $order,
                user: $actor,
            );

            $this->fail(
                'Expected provider failure to block cancellation.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'order',
                $exception->errors(),
            );
        }

        $order->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $order->order_status,
        );
        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
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

    public function test_repeated_cancellation_restores_inventory_only_once(): void
    {
        [$order, $product] = $this->placeOrder(
            PaymentMethod::GCash,
        );

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_cancel_test' => Http::response(
                $this->retrievedCheckoutSessionResponse(
                    $order,
                    status: 'expired',
                ),
                200,
            ),
        ]);

        $actor = User::factory()->create();

        app(CancelOrder::class)->handle(
            order: $order,
            user: $actor,
        );

        app(CancelOrder::class)->handle(
            order: $order->fresh(),
            user: $actor,
        );

        $this->assertSame(
            5,
            $product->fresh()->stock_quantity,
        );
        $this->assertDatabaseCount(
            'inventory_adjustments',
            2,
        );
    }

    public function test_manual_payment_cancellation_behavior_remains_unchanged(): void
    {
        [$order, $product] = $this->placeOrder(
            PaymentMethod::CashOnDelivery,
        );

        Http::fake();

        $actor = User::factory()->create();

        $cancelledOrder = app(CancelOrder::class)->handle(
            order: $order,
            user: $actor,
        );

        $this->assertSame(
            OrderStatus::Cancelled,
            $cancelledOrder->order_status,
        );
        $this->assertSame(
            PaymentStatus::Cancelled,
            $cancelledOrder->payment_status,
        );
        $this->assertSame(
            5,
            $product->fresh()->stock_quantity,
        );
        $this->assertDatabaseCount(
            'inventory_adjustments',
            2,
        );

        Http::assertNothingSent();
    }

    /**
     * @return array{0: Order, 1: Product}
     */
    private function placeOrder(
        PaymentMethod $paymentMethod,
    ): array {
        if ($paymentMethod->usesPayMongo()) {
            Http::fake([
                'https://api.paymongo.com/v2/checkout_sessions' => Http::response(
                    $this->checkoutSessionResponse(),
                    200,
                ),
            ]);
        } else {
            Http::fake();
        }

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $request = $this->withSession([
            'cart.items' => [
                $product->id => 1,
            ],
        ]);

        if ($paymentMethod->usesPayMongo()) {
            $request = $request->withHeader(
                'X-Inertia',
                'true',
            );
        }

        $response = $request->post(
            route('checkout.store'),
            $this->checkoutPayload($paymentMethod),
        );

        if ($paymentMethod->usesPayMongo()) {
            $response->assertStatus(409);
        } else {
            $response->assertRedirect(
                route('checkout.success'),
            );
        }

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
    private function retrievedCheckoutSessionResponse(
        Order $order,
        string $status,
        bool $hasPaidPayment = false,
    ): array {
        return [
            'data' => [
                'id' => 'cs_cancel_test',
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/cs_cancel_test',
                    'status' => $status,
                    'reference_number' => $order->order_number,
                    'payments' => $hasPaidPayment
                        ? [
                            [
                                'id' => 'pay_paid_test',
                                'attributes' => [
                                    'status' => 'paid',
                                ],
                            ],
                        ]
                        : [],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function expiredCheckoutSessionResponse(): array
    {
        return [
            'data' => [
                'id' => 'cs_cancel_test',
                'type' => 'checkout_session',
                'attributes' => [
                    'status' => 'expired',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(
        PaymentMethod $paymentMethod,
    ): array {
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
            'payment_method' => $paymentMethod->value,
            'customer_notes' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutSessionResponse(): array
    {
        return [
            'data' => [
                'id' => 'cs_cancel_test',
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/cs_cancel_test',
                ],
            ],
        ];
    }
}
