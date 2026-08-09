<?php

namespace Tests\Feature\Storefront;

use App\Enums\LandingPageTheme;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
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
                    ->where(
                        'theme',
                        LandingPageTheme::FashionEditorial->value,
                    )
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
                    ->where(
                        'theme',
                        LandingPageTheme::FashionEditorial->value,
                    )
                    ->where(
                        'product.id',
                        $product->id,
                    )
                    ->where(
                        'product.name',
                        'Test Product',
                    )
                    ->has('product.images', 1),
            );
    }

    public function test_product_page_exposes_configured_seo_metadata_and_base_url(): void
    {
        config()->set(
            'app.url',
            'https://shop.example.test/',
        );

        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'name' => 'SEO Product',
                'slug' => 'seo-product',
                'description' => 'Product description.',
                'meta_title' => 'SEO Product Title',
                'meta_description' => 'SEO Product Description',
                'is_active' => true,
            ]);

        $this
            ->get('/products/seo-product')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('shop/show')
                    ->where(
                        'product.id',
                        $product->id,
                    )
                    ->where(
                        'product.meta_title',
                        'SEO Product Title',
                    )
                    ->where(
                        'product.meta_description',
                        'SEO Product Description',
                    )
                    ->where(
                        'seo.base_url',
                        'https://shop.example.test',
                    ),
            );
    }

    public function test_product_page_falls_back_to_product_content_for_seo_metadata(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'name' => 'Fallback Product',
                'slug' => 'fallback-product',
                'description' => "Useful product description.\n\nWith extra spacing.",
                'meta_title' => null,
                'meta_description' => null,
                'is_active' => true,
            ]);

        $this
            ->get('/products/fallback-product')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('shop/show')
                    ->where(
                        'product.id',
                        $product->id,
                    )
                    ->where(
                        'product.meta_title',
                        'Fallback Product',
                    )
                    ->where(
                        'product.meta_description',
                        'Useful product description. With extra spacing.',
                    ),
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

    public function test_shop_receives_selected_fashion_theme(): void
    {
        $this->createStoreSettings(
            LandingPageTheme::FashionEditorial->value,
        );

        $this
            ->get('/shop')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('shop/index')
                    ->where(
                        'theme',
                        LandingPageTheme::FashionEditorial->value,
                    ),
            );
    }

    public function test_product_page_receives_selected_fashion_theme_and_navigation_categories(): void
    {
        $category = Category::factory()->create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'slug' => 'fashion-product',
                'is_active' => true,
            ]);

        $this->createStoreSettings(
            LandingPageTheme::FashionEditorial->value,
        );

        $this
            ->get("/products/{$product->slug}")
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('shop/show')
                    ->where(
                        'theme',
                        LandingPageTheme::FashionEditorial->value,
                    )
                    ->where(
                        'product.id',
                        $product->id,
                    )
                    ->has('categories', 1)
                    ->where(
                        'categories.0.id',
                        $category->id,
                    ),
            );
    }

    public function test_invalid_shop_theme_falls_back_to_fashion_elegant(): void
    {
        $this->createStoreSettings(
            'invalid-theme',
        );

        $this
            ->get('/shop')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('shop/index')
                    ->where(
                        'theme',
                        LandingPageTheme::FashionEditorial->value,
                    ),
            );
    }

    private function createStoreSettings(
        ?string $theme = null,
    ): StoreSetting {
        $attributes = [
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 0,
        ];

        if ($theme !== null) {
            $attributes['landing_page_theme'] = $theme;
        }

        return StoreSetting::query()->create(
            $attributes,
        );
    }
}
