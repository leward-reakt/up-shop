<?php

namespace Tests\Feature\Storefront;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_checkout_with_cart_items(): void
    {
        $this->createStoreSettings();

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this
            ->withSession(
                $this->guestCartSession(
                    product: $product,
                    quantity: 2,
                ),
            )
            ->get('/checkout')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/index')
                    ->has('items', 1)
                    ->where(
                        'items.0.product_id',
                        $product->id,
                    )
                    ->where('items.0.quantity', 2)
                    ->where('totals.subtotal', 200_000)
                    ->where('totals.shipping_total', 15_000)
                    ->where('totals.grand_total', 215_000),
            );
    }

    public function test_guest_can_place_cash_on_delivery_order(): void
    {
        $this->createStoreSettings();

        $product = Product::factory()->create([
            'name' => 'Checkout Product',
            'sku' => 'CHECKOUT-001',
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession(
                $this->guestCartSession(
                    product: $product,
                    quantity: 2,
                ),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload(),
            );

        $response
            ->assertRedirect(route('checkout.success'))
            ->assertSessionHas('checkout.order_id')
            ->assertSessionMissing('cart.items');

        $order = Order::query()->firstOrFail();

        $this->assertNull($order->user_id);

        $this->assertSame(
            200_000,
            $order->subtotal,
        );

        $this->assertSame(
            15_000,
            $order->shipping_total,
        );

        $this->assertSame(
            215_000,
            $order->grand_total,
        );

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $order->payment_method,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );

        $this->assertSame(
            OrderStatus::Pending,
            $order->order_status,
        );

        $this->assertCount(
            1,
            $order->items()->get(),
        );

        $orderItem = $order->items()->firstOrFail();

        $this->assertSame(
            'Checkout Product',
            $orderItem->product_name,
        );

        $this->assertSame(
            'CHECKOUT-001',
            $orderItem->sku,
        );

        $this->assertSame(
            2,
            $orderItem->quantity,
        );

        $this->assertSame(
            100_000,
            $orderItem->unit_price,
        );

        $this->assertSame(
            200_000,
            $orderItem->subtotal,
        );

        $payment = $order->payment()->firstOrFail();

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $payment->method,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->status,
        );

        $this->assertSame(
            215_000,
            $payment->amount,
        );

        $this->assertSame(
            3,
            $product->fresh()->stock_quantity,
        );

        $this->assertDatabaseHas(
            'inventory_adjustments',
            [
                'product_id' => $product->id,
                'quantity_change' => -2,
                'type' => 'order',
                'reference_type' => 'order',
                'reference_id' => $order->id,
            ],
        );
    }

    public function test_authenticated_checkout_clears_database_cart(): void
    {
        $this->createStoreSettings();

        $user = User::factory()->create([
            'phone' => '09171234567',
        ]);

        $product = Product::factory()->create([
            'price' => 75_000,
            'stock_quantity' => 4,
        ]);

        $cart = $user->cart()->create();

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this
            ->actingAs($user)
            ->post(
                '/checkout',
                $this->checkoutPayload([
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'customer_phone' => '09171234567',
                ]),
            )
            ->assertRedirect(route('checkout.success'));

        $order = Order::query()->firstOrFail();

        $this->assertSame(
            $user->id,
            $order->user_id,
        );

        $this->assertDatabaseMissing(
            'cart_items',
            [
                'cart_id' => $cart->id,
                'product_id' => $product->id,
            ],
        );
    }

    public function test_store_pickup_has_zero_shipping_fee(): void
    {
        $this->createStoreSettings();

        $product = Product::factory()->create([
            'price' => 50_000,
            'stock_quantity' => 5,
        ]);

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload([
                    'shipping_method' => 'store_pickup',
                ]),
            )
            ->assertRedirect(route('checkout.success'));

        $order = Order::query()->firstOrFail();

        $this->assertSame(
            0,
            $order->shipping_total,
        );

        $this->assertSame(
            50_000,
            $order->grand_total,
        );
    }

    public function test_free_shipping_threshold_is_applied(): void
    {
        $this->createStoreSettings([
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 100_000,
        ]);

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
        ]);

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload(),
            )
            ->assertRedirect(route('checkout.success'));

        $order = Order::query()->firstOrFail();

        $this->assertSame(
            0,
            $order->shipping_total,
        );

        $this->assertSame(
            100_000,
            $order->grand_total,
        );
    }

    public function test_discount_is_revalidated_during_checkout(): void
    {
        $this->createStoreSettings([
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => null,
        ]);

        Discount::factory()->create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'minimum_purchase' => 100_000,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'price' => 200_000,
            'stock_quantity' => 5,
        ]);

        $this
            ->withSession(
                $this->guestCartSession(
                    product: $product,
                    discountCode: 'WELCOME10',
                ),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload(),
            )
            ->assertRedirect(route('checkout.success'));

        $order = Order::query()->firstOrFail();

        $this->assertSame(
            'WELCOME10',
            $order->discount_code,
        );

        $this->assertSame(
            20_000,
            $order->discount_total,
        );

        $this->assertSame(
            195_000,
            $order->grand_total,
        );
    }

    public function test_checkout_fails_if_stock_is_no_longer_available(): void
    {
        $this->createStoreSettings();

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 1,
        ]);

        $this
            ->withSession(
                $this->guestCartSession(
                    product: $product,
                    quantity: 2,
                ),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload(),
            )
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount(
            'orders',
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
            1,
            $product->fresh()->stock_quantity,
        );
    }

    public function test_order_item_keeps_historical_product_snapshot(): void
    {
        $this->createStoreSettings();

        $product = Product::factory()->create([
            'name' => 'Original Product',
            'sku' => 'ORIGINAL-SKU',
            'price' => 90_000,
            'stock_quantity' => 5,
        ]);

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload(),
            )
            ->assertRedirect(route('checkout.success'));

        $product->update([
            'name' => 'Renamed Product',
            'sku' => 'NEW-SKU',
            'price' => 150_000,
        ]);

        $order = Order::query()
            ->with('items')
            ->firstOrFail();

        $item = $order->items->firstOrFail();

        $this->assertSame(
            'Original Product',
            $item->product_name,
        );

        $this->assertSame(
            'ORIGINAL-SKU',
            $item->sku,
        );

        $this->assertSame(
            90_000,
            $item->unit_price,
        );
    }

    public function test_client_cannot_choose_unsupported_payment_or_shipping_methods(): void
    {
        $this->createStoreSettings();

        $product = Product::factory()->create([
            'stock_quantity' => 5,
        ]);

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload([
                    'shipping_method' => 'same_day_drone',
                    'payment_method' => 'credit_card',
                ]),
            )
            ->assertSessionHasErrors([
                'shipping_method',
                'payment_method',
            ]);

        $this->assertDatabaseCount(
            'orders',
            0,
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

            'shipping_address_line_1' => '123 Test Street',
            'shipping_address_line_2' => null,
            'shipping_city' => 'Makati',
            'shipping_province' => 'Metro Manila',
            'shipping_postal_code' => '1200',

            'shipping_method' => 'flat_rate',
            'payment_method' => 'cash_on_delivery',

            'customer_notes' => null,

            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function guestCartSession(
        Product $product,
        int $quantity = 1,
        ?string $discountCode = null,
    ): array {
        $cart = [
            'items' => [
                $product->id => $quantity,
            ],
        ];

        if ($discountCode !== null) {
            $cart['discount_code'] = $discountCode;
        }

        return [
            'cart' => $cart,
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
            'contact_number' => null,
            'business_address' => null,
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 300_000,
            'tax_rate_basis_points' => null,
            'social_links' => [],
            ...$overrides,
        ]);
    }
}
