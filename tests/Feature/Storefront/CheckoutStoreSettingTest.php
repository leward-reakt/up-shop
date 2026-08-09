<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutStoreSettingTest extends TestCase
{
    use RefreshDatabase;

    private const SETTINGS_ERROR = 'Checkout is temporarily unavailable because store settings have not been configured.';

    public function test_checkout_page_is_unavailable_without_store_settings(): void
    {
        $product = Product::factory()->create([
            'category_id' => null,
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->get('/checkout')
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors([
                'cart' => self::SETTINGS_ERROR,
            ]);

        $this->assertDatabaseCount(
            'orders',
            0,
        );
    }

    public function test_order_is_not_created_without_store_settings(): void
    {
        $product = Product::factory()->create([
            'category_id' => null,
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
                $this->checkoutPayload(),
            )
            ->assertSessionHasErrors([
                'cart' => self::SETTINGS_ERROR,
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

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '09171234567',

            'shipping_address_id' => null,
            'shipping_address_line_1' => '123 Test Street',
            'shipping_address_line_2' => null,
            'shipping_city' => 'Makati',
            'shipping_province' => 'Metro Manila',
            'shipping_postal_code' => '1200',

            'shipping_method' => 'flat_rate',
            'payment_method' => 'cash_on_delivery',

            'customer_notes' => null,
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
}
