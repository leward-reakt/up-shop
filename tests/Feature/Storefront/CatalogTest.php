<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_only_displays_publicly_available_products(): void
    {
        $activeCategory = Category::factory()->create([
            'is_active' => true,
        ]);

        $inactiveCategory = Category::factory()->create([
            'is_active' => false,
        ]);

        $visibleProduct = Product::factory()
            ->for($activeCategory)
            ->create([
                'name' => 'Visible Product',
                'is_active' => true,
            ]);

        Product::factory()
            ->for($activeCategory)
            ->create([
                'name' => 'Inactive Product',
                'is_active' => false,
            ]);

        Product::factory()
            ->for($inactiveCategory)
            ->create([
                'name' => 'Hidden Category Product',
                'is_active' => true,
            ]);

        $this
            ->get('/shop')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('shop/index')
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.id',
                        $visibleProduct->id,
                    ),
            );
    }

    public function test_shop_filters_products(): void
    {
        $category = Category::factory()->create([
            'name' => 'Lifestyle',
            'slug' => 'lifestyle',
            'is_active' => true,
        ]);

        $matchingProduct = Product::factory()
            ->for($category)
            ->create([
                'name' => 'Premium Coffee Mug',
                'price' => 125_000,
                'stock_quantity' => 10,
                'is_active' => true,
            ]);

        Product::factory()
            ->for($category)
            ->create([
                'name' => 'Budget Coffee Mug',
                'price' => 50_000,
                'stock_quantity' => 10,
                'is_active' => true,
            ]);

        Product::factory()
            ->for($category)
            ->create([
                'name' => 'Premium Coffee Beans',
                'price' => 125_000,
                'stock_quantity' => 0,
                'is_active' => true,
            ]);

        $this
            ->get(
                '/shop?search=Premium&category=lifestyle'
                .'&min_price=1000&max_price=1500'
                .'&availability=in_stock&sort=price_asc',
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('shop/index')
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.id',
                        $matchingProduct->id,
                    ),
            );
    }

    public function test_active_product_can_be_viewed(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'name' => 'Test Product',
                'slug' => 'test-product',
                'is_active' => true,
            ]);

        $product->images()->create([
            'path' => 'products/test-product.jpg',
            'alt_text' => 'Test product image',
            'sort_order' => 0,
        ]);

        $this
            ->get('/products/test-product')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('shop/show')
                    ->where('product.id', $product->id)
                    ->where('product.name', 'Test Product')
                    ->has('product.images', 1),
            );
    }

    public function test_inactive_product_cannot_be_viewed_directly(): void
    {
        $product = Product::factory()->create([
            'slug' => 'hidden-product',
            'is_active' => false,
        ]);

        $this
            ->get("/products/{$product->slug}")
            ->assertNotFound();
    }

    public function test_product_in_inactive_category_cannot_be_viewed_directly(): void
    {
        $category = Category::factory()->create([
            'is_active' => false,
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'slug' => 'hidden-category-product',
                'is_active' => true,
            ]);

        $this
            ->get("/products/{$product->slug}")
            ->assertNotFound();
    }
}
