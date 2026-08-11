<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PayMongoCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

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

    public function test_gcash_checkout_creates_order_and_redirects_to_paymongo(): void
    {
        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' =>
                Http::response(
                    $this->checkoutSessionResponse(
                        'cs_gcash_test',
                        'https://checkout.paymongo.com/cs_gcash_test',
                    ),
                    200,
                ),
        ]);

        $product = $this->product();

        $response = $this
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
                $this->checkoutPayload(
                    PaymentMethod::GCash,
                ),
            );

        $response
            ->assertStatus(409)
            ->assertHeader(
                'X-Inertia-Location',
                'https://checkout.paymongo.com/cs_gcash_test',
            );

        $order = Order::query()
            ->with('payment')
            ->sole();

        $this->assertSame(
            PaymentMethod::GCash,
            $order->payment_method,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );

        $this->assertSame(
            115_000,
            $order->grand_total,
        );

        $this->assertNotNull(
            $order->payment,
        );

        $this->assertSame(
            'paymongo',
            $order->payment->provider,
        );

        $this->assertSame(
            'PHP',
            $order->payment->currency,
        );

        $this->assertSame(
            'cs_gcash_test',
            $order->payment->provider_checkout_id,
        );

        $this->assertSame(
            4,
            $product->fresh()->stock_quantity,
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'inventory_adjustments',
            1,
        );

        Http::assertSent(
            function (ClientRequest $request) use (
                $order,
            ): bool {
                return $request->url()
                    === 'https://api.paymongo.com/v2/checkout_sessions'
                    && $request->method() === 'POST'
                    && data_get(
                        $request->data(),
                        'data.attributes.payment_method_types.0',
                    ) === 'gcash'
                    && data_get(
                        $request->data(),
                        'data.attributes.line_items.0.amount',
                    ) === $order->grand_total
                    && data_get(
                        $request->data(),
                        'data.attributes.line_items.0.currency',
                    ) === 'PHP'
                    && data_get(
                        $request->data(),
                        'data.attributes.reference_number',
                    ) === $order->order_number
                    && is_string(
                        $request->header(
                            'Idempotency-Key',
                        )[0] ?? null,
                    );
            },
        );
    }

    public function test_maya_uses_paymaya_provider_identifier(): void
    {
        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' =>
                Http::response(
                    $this->checkoutSessionResponse(
                        'cs_maya_test',
                        'https://checkout.paymongo.com/cs_maya_test',
                    ),
                    200,
                ),
        ]);

        $product = $this->product();

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
                $this->checkoutPayload(
                    PaymentMethod::Maya,
                ),
            )
            ->assertStatus(409);

        Http::assertSent(
            fn (ClientRequest $request): bool =>
                data_get(
                    $request->data(),
                    'data.attributes.payment_method_types.0',
                ) === 'paymaya',
        );
    }

    public function test_provider_failure_keeps_created_order_pending_and_recoverable(): void
    {
        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' =>
                Http::response(
                    [
                        'errors' => [
                            [
                                'code' => 'provider_error',
                                'detail' => 'Temporary test failure.',
                            ],
                        ],
                    ],
                    500,
                ),
        ]);

        $product = $this->product();

        $response = $this
            ->withSession([
                'cart.items' => [
                    $product->id => 1,
                ],
            ])
            ->post(
                route('checkout.store'),
                $this->checkoutPayload(
                    PaymentMethod::GCash,
                ),
            );

        $order = Order::query()
            ->with('payment')
            ->sole();

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(
                'payment',
            );

        $location = $response
            ->headers
            ->get('Location');

        $this->assertIsString($location);

        $this->assertStringContainsString(
            '/checkout/payment/'.$order->order_number,
            $location,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment->status,
        );

        $this->assertNull(
            $order->payment->provider_checkout_id,
        );

        $this->assertSame(
            4,
            $product->fresh()->stock_quantity,
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'inventory_adjustments',
            1,
        );
    }

    public function test_success_return_does_not_mark_payment_paid(): void
    {
        [$order] = $this->placePendingPayMongoOrder();

        $url = URL::signedRoute(
            'checkout.payment.success',
            [
                'order' => $order->order_number,
            ],
        );

        $this
            ->get($url)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/payment-return')
                    ->where(
                        'context',
                        'success',
                    )
                    ->where(
                        'order.payment_status',
                        PaymentStatus::Pending->value,
                    )
                    ->where(
                        'can_resume',
                        true,
                    ),
            );

        $order->refresh();

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment->status,
        );
    }

    public function test_cancel_return_does_not_cancel_payment(): void
    {
        [$order] = $this->placePendingPayMongoOrder();

        $url = URL::signedRoute(
            'checkout.payment.cancelled',
            [
                'order' => $order->order_number,
            ],
        );

        $this
            ->get($url)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/payment-return')
                    ->where(
                        'context',
                        'cancelled',
                    )
                    ->where(
                        'order.payment_status',
                        PaymentStatus::Pending->value,
                    ),
            );

        $order->refresh();

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment->status,
        );
    }

    public function test_resume_reuses_active_checkout_session_without_creating_another_order(): void
    {
        [$order, $product] =
            $this->placePendingPayMongoOrder();

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_initial_test' =>
                Http::response(
                    $this->retrievedCheckoutSessionResponse(
                        checkoutId: 'cs_initial_test',
                        checkoutUrl: 'https://checkout.paymongo.com/cs_initial_test',
                        status: 'active',
                        referenceNumber: $order->order_number,
                    ),
                    200,
                ),
        ]);

        $this
            ->withHeader(
                'X-Inertia',
                'true',
            )
            ->post(
                route(
                    'checkout.payment.resume',
                    [
                        'order' => $order->order_number,
                    ],
                ),
            )
            ->assertStatus(409)
            ->assertHeader(
                'X-Inertia-Location',
                'https://checkout.paymongo.com/cs_initial_test',
            );

        $order->refresh();

        $this->assertSame(
            'cs_initial_test',
            $order->payment->provider_checkout_id,
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'inventory_adjustments',
            1,
        );

        $this->assertSame(
            4,
            $product->fresh()->stock_quantity,
        );
    }

    public function test_resume_replaces_expired_session_without_duplicate_order_or_inventory_deduction(): void
    {
        [$order, $product] =
            $this->placePendingPayMongoOrder();

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_initial_test' =>
                Http::response(
                    $this->retrievedCheckoutSessionResponse(
                        checkoutId: 'cs_initial_test',
                        checkoutUrl: 'https://checkout.paymongo.com/cs_initial_test',
                        status: 'expired',
                        referenceNumber: $order->order_number,
                    ),
                    200,
                ),

            'https://api.paymongo.com/v2/checkout_sessions' =>
                Http::response(
                    $this->checkoutSessionResponse(
                        'cs_replacement_test',
                        'https://checkout.paymongo.com/cs_replacement_test',
                    ),
                    200,
                ),
        ]);

        $this
            ->withHeader(
                'X-Inertia',
                'true',
            )
            ->post(
                route(
                    'checkout.payment.resume',
                    [
                        'order' => $order->order_number,
                    ],
                ),
            )
            ->assertStatus(409)
            ->assertHeader(
                'X-Inertia-Location',
                'https://checkout.paymongo.com/cs_replacement_test',
            );

        $order->refresh();

        $this->assertSame(
            'cs_replacement_test',
            $order->payment->provider_checkout_id,
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'payments',
            1,
        );

        $this->assertDatabaseCount(
            'inventory_adjustments',
            1,
        );

        $this->assertSame(
            4,
            $product->fresh()->stock_quantity,
        );
    }

    /**
     * @return array{0: Order, 1: Product}
     */
    private function placePendingPayMongoOrder(): array
    {
        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' =>
                Http::response(
                    $this->checkoutSessionResponse(
                        'cs_initial_test',
                        'https://checkout.paymongo.com/cs_initial_test',
                    ),
                    200,
                ),
        ]);

        $product = $this->product();

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
                $this->checkoutPayload(
                    PaymentMethod::GCash,
                ),
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

    private function product(): Product
    {
        return Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
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

    /**
     * @return array<string, mixed>
     */
    private function retrievedCheckoutSessionResponse(
        string $checkoutId,
        string $checkoutUrl,
        string $status,
        string $referenceNumber,
    ): array {
        return [
            'data' => [
                'id' => $checkoutId,
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => $checkoutUrl,
                    'status' => $status,
                    'reference_number' => $referenceNumber,
                    'payments' => [],
                ],
            ],
        ];
    }
}
