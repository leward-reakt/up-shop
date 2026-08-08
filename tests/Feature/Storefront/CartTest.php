<?php

namespace Tests\Feature\Storefront;

use App\Models\Cart;
use App\Models\Discount;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_update_and_remove_cart_item(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertRedirect('/cart')
            ->assertSessionHas(
                "cart.items.{$product->id}",
                2,
            );

        $this
            ->patch("/cart/items/{$product->id}", [
                'quantity' => 4,
            ])
            ->assertRedirect('/cart')
            ->assertSessionHas(
                "cart.items.{$product->id}",
                4,
            );

        $this
            ->delete("/cart/items/{$product->id}")
            ->assertRedirect('/cart')
            ->assertSessionMissing('cart.items');
    }

    public function test_product_cannot_be_added_beyond_available_stock(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 3,
            'is_active' => true,
        ]);

        $this
            ->from("/products/{$product->slug}")
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 4,
            ])
            ->assertRedirect("/products/{$product->slug}")
            ->assertSessionHasErrors('quantity')
            ->assertSessionMissing('cart.items');
    }

    public function test_cart_quantity_cannot_be_updated_beyond_available_stock(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 3,
            'is_active' => true,
        ]);

        $this->withSession([
            'cart.items' => [
                $product->id => 2,
            ],
        ]);

        $this
            ->from('/cart')
            ->patch("/cart/items/{$product->id}", [
                'quantity' => 4,
            ])
            ->assertRedirect('/cart')
            ->assertSessionHasErrors('quantity')
            ->assertSessionHas(
                "cart.items.{$product->id}",
                2,
            );
    }

    public function test_inactive_product_cannot_be_added_to_cart(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => false,
        ]);

        $this
            ->from('/shop')
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertRedirect('/shop')
            ->assertSessionHasErrors('quantity')
            ->assertSessionMissing('cart.items');
    }

    public function test_authenticated_cart_is_persisted_in_database(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertRedirect('/cart');

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this
            ->actingAs($user)
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('cart/index')
                    ->has('items', 1)
                    ->where(
                        'items.0.product_id',
                        $product->id,
                    )
                    ->where(
                        'items.0.quantity',
                        2,
                    ),
            );
    }

    public function test_adding_existing_authenticated_cart_item_increments_quantity(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $this
            ->actingAs($user)
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 3,
            ])
            ->assertRedirect('/cart');

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->assertSame(
            1,
            $cart->items()->count(),
        );
    }

    public function test_cart_calculates_subtotal_shipping_discount_and_grand_total(): void
    {
        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 300_000,
        ]);

        Discount::query()->create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'minimum_purchase' => 100_000,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this
            ->withSession([
                'cart.items' => [
                    $product->id => 2,
                ],
                'cart.discount_code' => 'WELCOME10',
            ])
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('cart/index')
                    ->has('items', 1)
                    ->where(
                        'items.0.line_total',
                        200_000,
                    )
                    ->where(
                        'totals.subtotal',
                        200_000,
                    )
                    ->where(
                        'totals.discount_total',
                        20_000,
                    )
                    ->where(
                        'totals.shipping_total',
                        15_000,
                    )
                    ->where(
                        'totals.grand_total',
                        195_000,
                    )
                    ->where(
                        'totals.discount_code',
                        'WELCOME10',
                    ),
            );
    }

    public function test_free_shipping_threshold_is_applied(): void
    {
        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 300_000,
        ]);

        $product = Product::factory()->create([
            'price' => 150_000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this
            ->withSession([
                'cart.items' => [
                    $product->id => 2,
                ],
            ])
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->where(
                        'totals.subtotal',
                        300_000,
                    )
                    ->where(
                        'totals.shipping_total',
                        0,
                    )
                    ->where(
                        'totals.grand_total',
                        300_000,
                    ),
            );
    }

    public function test_fixed_discount_is_calculated_in_minor_currency_units(): void
    {
        Discount::query()->create([
            'code' => 'SAVE250',
            'type' => 'fixed',
            'value' => 25_000,
            'minimum_purchase' => null,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this
            ->withSession([
                'cart.items' => [
                    $product->id => 1,
                ],
                'cart.discount_code' => 'SAVE250',
            ])
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->where(
                        'totals.subtotal',
                        100_000,
                    )
                    ->where(
                        'totals.discount_total',
                        25_000,
                    )
                    ->where(
                        'totals.grand_total',
                        75_000,
                    ),
            );
    }

    public function test_expired_discount_cannot_be_applied(): void
    {
        Discount::query()->create([
            'code' => 'EXPIRED10',
            'type' => 'percentage',
            'value' => 10,
            'minimum_purchase' => null,
            'starts_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this
            ->withSession([
                'cart.items' => [
                    $product->id => 1,
                ],
            ])
            ->from('/cart')
            ->post('/cart/discount', [
                'discount_code' => 'expired10',
            ])
            ->assertRedirect('/cart')
            ->assertSessionHasErrors('discount_code')
            ->assertSessionMissing('cart.discount_code');
    }
}
