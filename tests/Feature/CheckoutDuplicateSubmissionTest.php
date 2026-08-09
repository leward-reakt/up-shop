<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CheckoutDuplicateSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_checkout_for_same_authenticated_customer_is_rejected_without_side_effects(): void
    {
        $this->useDatabaseCacheLocks();
        $this->createStoreSettings();

        $user = User::factory()->create();

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $cart = $user->cart()->create();

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $lockKey = 'checkout:place-order:user:'.$user->getAuthIdentifier();

        $lock = Cache::lock(
            $lockKey,
            60,
        );

        $this->assertTrue($lock->get());

        // Prove the configured cache driver can see the held lock before
        // exercising the HTTP checkout request.
        $this->assertFalse(
            Cache::lock($lockKey, 60)->get(),
        );

        try {
            $this
                ->actingAs($user)
                ->post(
                    route('checkout.store'),
                    $this->checkoutPayload(),
                )
                ->assertSessionHasErrors([
                    'cart' => 'Your order is already being processed. Please wait a moment before trying again.',
                ]);
        } finally {
            $lock->release();
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_adjustments', 0);

        $this->assertSame(
            5,
            $product->fresh()->stock_quantity,
        );

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_concurrent_checkout_for_same_guest_session_is_rejected_without_side_effects(): void
    {
        $this->useDatabaseCacheLocks();
        $this->createStoreSettings();

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->withSession([
            'cart.items' => [
                $product->id => 2,
            ],
        ]);

        $session = $this->app['session']->driver();
        $sessionId = $session->getId();

        $lockKey = 'checkout:place-order:session:'.$sessionId;

        $lock = Cache::lock(
            $lockKey,
            60,
        );

        $this->assertTrue($lock->get());

        // Prove the configured cache driver can see the held lock before
        // exercising the HTTP checkout request.
        $this->assertFalse(
            Cache::lock($lockKey, 60)->get(),
        );

        try {
            $this
                ->withCookie(
                    $session->getName(),
                    $sessionId,
                )
                ->post(
                    route('checkout.store'),
                    $this->checkoutPayload(),
                )
                ->assertSessionHasErrors([
                    'cart' => 'Your order is already being processed. Please wait a moment before trying again.',
                ]);
        } finally {
            $lock->release();
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_adjustments', 0);

        $this->assertSame(
            5,
            $product->fresh()->stock_quantity,
        );
    }

    private function useDatabaseCacheLocks(): void
    {
        // PHPUnit normally uses the in-memory array cache. This regression
        // specifically verifies the cross-request atomic lock used by the
        // application, which is database-backed in the project configuration.
        config()->set(
            'cache.default',
            'database',
        );
    }

    private function createStoreSettings(): void
    {
        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => null,
            'tax_rate_basis_points' => null,
        ]);
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
            'payment_method' => PaymentMethod::CashOnDelivery->value,
            'customer_notes' => null,
        ];
    }
}
