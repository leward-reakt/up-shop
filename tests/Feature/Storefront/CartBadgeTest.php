<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_cart_has_zero_product_count(): void
    {
        $this
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->where('cart.product_count', 0),
            );
    }

    public function test_guest_cart_counts_products_not_quantities(): void
    {
        $products = Product::factory()
            ->count(2)
            ->create([
                'stock_quantity' => 20,
                'is_active' => true,
            ]);

        $firstProduct = $products[0];
        $secondProduct = $products[1];

        $this
            ->withSession([
                'cart.items' => [
                    $firstProduct->id => 8,
                    $secondProduct->id => 3,
                ],
            ])
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->where('cart.product_count', 2)
                    ->where('cart.guest_has_items', true),
            );
    }

    public function test_authenticated_cart_counts_products_not_quantities(): void
    {
        $user = User::factory()->create();

        $products = Product::factory()
            ->count(2)
            ->create([
                'stock_quantity' => 20,
                'is_active' => true,
            ]);

        $firstProduct = $products[0];
        $secondProduct = $products[1];

        $cart = $user->cart()->create();

        $cart->items()->create([
            'product_id' => $firstProduct->id,
            'quantity' => 8,
        ]);

        $cart->items()->create([
            'product_id' => $secondProduct->id,
            'quantity' => 3,
        ]);

        $this
            ->actingAs($user)
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->where('cart.product_count', 2)
                    ->where('cart.guest_has_items', false),
            );
    }
}
