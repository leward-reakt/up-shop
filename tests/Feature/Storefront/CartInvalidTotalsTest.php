<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartInvalidTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_purchasable_lines_remain_visible_but_are_excluded_from_totals_and_discount_eligibility(): void
    {
        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 150_000,
        ]);

        Discount::query()->create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'minimum_purchase' => 150_000,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $validProduct = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $inactiveProduct = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 10,
            'is_active' => false,
        ]);

        $outOfStockProduct = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $insufficientStockProduct = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 1,
            'is_active' => true,
        ]);

        $inactiveCategory = Category::factory()->create([
            'is_active' => false,
        ]);

        $inactiveCategoryProduct = Product::factory()->create([
            'category_id' => $inactiveCategory->id,
            'price' => 100_000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $archivedProduct = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $archivedProduct->delete();

        $this
            ->withSession([
                'cart.items' => [
                    $validProduct->id => 1,
                    $inactiveProduct->id => 1,
                    $outOfStockProduct->id => 1,
                    $insufficientStockProduct->id => 2,
                    $inactiveCategoryProduct->id => 1,
                    $archivedProduct->id => 1,
                ],
                'cart.discount_code' => 'WELCOME10',
            ])
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('cart/index')
                    ->has('items', 6)
                    ->where(
                        'items.0.product_id',
                        $validProduct->id,
                    )
                    ->where(
                        'items.0.is_available',
                        true,
                    )
                    ->where(
                        'items.1.product_id',
                        $inactiveProduct->id,
                    )
                    ->where(
                        'items.1.is_available',
                        false,
                    )
                    ->where(
                        'items.2.product_id',
                        $outOfStockProduct->id,
                    )
                    ->where(
                        'items.2.is_available',
                        false,
                    )
                    ->where(
                        'items.3.product_id',
                        $insufficientStockProduct->id,
                    )
                    ->where(
                        'items.3.is_available',
                        false,
                    )
                    ->where(
                        'items.4.product_id',
                        $inactiveCategoryProduct->id,
                    )
                    ->where(
                        'items.4.is_available',
                        false,
                    )
                    ->where(
                        'items.5.product_id',
                        $archivedProduct->id,
                    )
                    ->where(
                        'items.5.is_available',
                        false,
                    )
                    ->where(
                        'totals.subtotal',
                        100_000,
                    )
                    ->where(
                        'totals.discount_total',
                        0,
                    )
                    ->where(
                        'totals.shipping_total',
                        15_000,
                    )
                    ->where(
                        'totals.grand_total',
                        115_000,
                    )
                    ->where(
                        'totals.discount_code',
                        'WELCOME10',
                    )
                    ->where(
                        'totals.discount_error',
                        'Your cart does not meet the minimum purchase required for this discount.',
                    ),
            );
    }
}
