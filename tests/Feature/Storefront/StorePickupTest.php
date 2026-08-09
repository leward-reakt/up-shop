<?php

namespace Tests\Feature\Storefront;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StorePickupTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_LOCATION = '100 Up Shop Avenue, Makati City, Metro Manila 1200';

    public function test_checkout_exposes_configured_pickup_location_and_store_pickup_option(): void
    {
        $this->createStoreSettings();

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->get('/checkout')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/index')
                    ->where(
                        'pickup_location',
                        self::PICKUP_LOCATION,
                    )
                    ->has('shipping_options', 2)
                    ->where(
                        'shipping_options.0.value',
                        ShippingMethod::FlatRate->value,
                    )
                    ->where(
                        'shipping_options.1.value',
                        ShippingMethod::StorePickup->value,
                    ),
            );
    }

    public function test_checkout_hides_store_pickup_and_normalizes_direct_query_when_address_is_missing(): void
    {
        $this->createStoreSettings([
            'business_address' => '   ',
        ]);

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->get('/checkout?shipping_method=store_pickup')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/index')
                    ->where('pickup_location', null)
                    ->where(
                        'selected_shipping_method',
                        ShippingMethod::FlatRate->value,
                    )
                    ->has('shipping_options', 1)
                    ->where(
                        'shipping_options.0.value',
                        ShippingMethod::FlatRate->value,
                    ),
            );
    }

    public function test_store_pickup_submission_is_rejected_without_business_address_and_has_no_side_effects(): void
    {
        Notification::fake();

        $this->createStoreSettings([
            'business_address' => null,
        ]);

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload([
                    'shipping_method' => ShippingMethod::StorePickup->value,
                ]),
            )
            ->assertSessionHasErrors([
                'shipping_method',
            ])
            ->assertSessionHas(
                'cart.items',
                [
                    $product->id => 1,
                ],
            );

        $this->assertDatabaseCount(
            'orders',
            0,
        );

        $this->assertDatabaseCount(
            'order_items',
            0,
        );

        $this->assertDatabaseCount(
            'payments',
            0,
        );

        $this->assertDatabaseCount(
            'inventory_adjustments',
            0,
        );

        $this->assertSame(
            5,
            $product->fresh()->stock_quantity,
        );
    }

    public function test_store_pickup_success_exposes_store_address_while_preserving_customer_snapshot(): void
    {
        Notification::fake();

        $this->createStoreSettings();

        $product = Product::factory()->create([
            'price' => 50_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload([
                    'shipping_method' => ShippingMethod::StorePickup->value,
                ]),
            )
            ->assertRedirect(
                route('checkout.success'),
            );

        $order = Order::query()->firstOrFail();

        $this->assertSame(
            ShippingMethod::StorePickup,
            $order->shipping_method,
        );

        $this->assertSame(
            '123 Customer Street',
            $order->shipping_address_line_1,
        );

        $this->assertSame(
            0,
            $order->shipping_total,
        );

        $this
            ->get(route('checkout.success'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/success')
                    ->where(
                        'pickup_location',
                        self::PICKUP_LOCATION,
                    )
                    ->where(
                        'order.shipping_method',
                        ShippingMethod::StorePickup->value,
                    )
                    ->where(
                        'order.shipping_address.address_line_1',
                        '123 Customer Street',
                    ),
            );
    }

    public function test_customer_pickup_order_details_expose_store_pickup_location(): void
    {
        $this->createStoreSettings();

        $customer = User::factory()->create();

        $order = Order::query()->create([
            'order_number' => 'PICKUP-ORDER-001',

            'user_id' => $customer->id,

            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '09171234567',

            'shipping_address_line_1' => '456 Customer Home Road',
            'shipping_address_line_2' => null,
            'shipping_city' => 'Quezon City',
            'shipping_province' => 'Metro Manila',
            'shipping_postal_code' => '1100',
            'shipping_country' => 'PH',

            'shipping_method' => ShippingMethod::StorePickup,

            'discount_code' => null,

            'subtotal' => 100_000,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100_000,

            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,

            'customer_notes' => null,
            'admin_notes' => null,
        ]);

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'account.orders.show',
                    $order,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('account/orders/show')
                    ->where(
                        'pickup_location',
                        self::PICKUP_LOCATION,
                    )
                    ->where(
                        'order.shipping_method',
                        ShippingMethod::StorePickup->value,
                    )
                    ->where(
                        'order.shipping_address.address_line_1',
                        '456 Customer Home Road',
                    ),
            );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function checkoutPayload(
        array $overrides = [],
    ): array {
        return [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '09171234567',

            'shipping_address_id' => null,
            'shipping_address_line_1' => '123 Customer Street',
            'shipping_address_line_2' => null,
            'shipping_city' => 'Makati',
            'shipping_province' => 'Metro Manila',
            'shipping_postal_code' => '1200',

            'shipping_method' => ShippingMethod::FlatRate->value,
            'payment_method' => PaymentMethod::CashOnDelivery->value,

            'customer_notes' => null,

            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function guestCartSession(
        Product $product,
    ): array {
        return [
            'cart' => [
                'items' => [
                    $product->id => 1,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createStoreSettings(
        array $overrides = [],
    ): StoreSetting {
        return StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'store_email' => 'hello@example.com',
            'contact_number' => '09171234567',
            'business_address' => self::PICKUP_LOCATION,
            'bank_transfer_instructions' => null,
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 300_000,
            'tax_rate_basis_points' => null,
            'social_links' => [],
            ...$overrides,
        ]);
    }
}
