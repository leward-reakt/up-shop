<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductImageOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_uses_sort_order_for_main_image_even_when_is_primary_is_stale(): void
    {
        $this->createStoreSettings();

        $category = Category::factory()->create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'name' => 'Image Order Product',
                'slug' => 'image-order-product',
                'sku' => 'IMAGE-ORDER-001',
                'price' => 100_000,
                'stock_quantity' => 5,
                'is_active' => true,
                'is_featured' => true,
            ]);

        $product->images()->create([
            'path' => 'products/first-by-sort-order.jpg',
            'alt_text' => 'First image by sort order',
            'sort_order' => 0,
            'is_primary' => false,
        ]);

        /*
         * Simulate legacy or externally-written state that contradicts the
         * administrator-controlled image ordering. The storefront must ignore
         * this flag and continue to use sort_order as its source of truth.
         */
        $product->images()->create([
            'path' => 'products/stale-primary.jpg',
            'alt_text' => 'Stale primary image',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $mainImageUrl = Storage::disk('public')->url(
            'products/first-by-sort-order.jpg',
        );

        $secondaryImageUrl = Storage::disk('public')->url(
            'products/stale-primary.jpg',
        );

        $this
            ->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('home')
                    ->has('featuredProducts', 1)
                    ->where(
                        'featuredProducts.0.image_url',
                        $mainImageUrl,
                    )
                    ->has('newArrivals', 1)
                    ->where(
                        'newArrivals.0.image_url',
                        $mainImageUrl,
                    )
                    ->has('categories', 1)
                    ->where(
                        'categories.0.image_url',
                        $mainImageUrl,
                    ),
            );

        $this
            ->get('/shop')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('shop/index')
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.id',
                        $product->id,
                    )
                    ->where(
                        'products.data.0.image_url',
                        $mainImageUrl,
                    ),
            );

        $this
            ->get("/products/{$product->slug}")
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('shop/show')
                    ->where(
                        'product.id',
                        $product->id,
                    )
                    ->where(
                        'product.image_url',
                        $mainImageUrl,
                    )
                    ->has('product.images', 2)
                    ->where(
                        'product.images.0.url',
                        $mainImageUrl,
                    )
                    ->where(
                        'product.images.1.url',
                        $secondaryImageUrl,
                    ),
            );

        $cartSession = [
            'cart' => [
                'items' => [
                    $product->id => 1,
                ],
            ],
        ];

        $this
            ->withSession($cartSession)
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
                        'items.0.image_url',
                        $mainImageUrl,
                    ),
            );

        $this
            ->withSession($cartSession)
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
                    ->where(
                        'items.0.image_url',
                        $mainImageUrl,
                    ),
            );
    }

    private function createStoreSettings(): StoreSetting
    {
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
        ]);
    }
}
