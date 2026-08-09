<?php

namespace Tests\Feature\Storefront;

use App\Enums\LandingPageTheme;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingPageThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_settings_database_default_is_fashion_elegant(): void
    {
        $settings = $this->createStoreSettings();

        $this->assertSame(
            LandingPageTheme::FashionEditorial->value,
            $settings->refresh()->landing_page_theme,
        );
    }

    public function test_home_receives_fashion_elegant_landing_catalog_data(): void
    {
        $category = Category::factory()->create([
            'name' => 'Bags',
            'slug' => 'bags',
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'name' => 'Elegant Bag',
                'is_active' => true,
                'is_featured' => true,
                'stock_quantity' => 10,
            ]);

        $this->createStoreSettings();

        $this
            ->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('home')
                    ->has('categories', 1)
                    ->where(
                        'categories.0.id',
                        $category->id,
                    )
                    ->has('newArrivals', 1)
                    ->where(
                        'newArrivals.0.id',
                        $product->id,
                    )
                    ->has('featuredProducts', 1),
            );
    }

    public function test_fashion_theme_is_shared_with_cart_page(): void
    {
        $category = Category::factory()->create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);

        Product::factory()
            ->for($category)
            ->create([
                'is_active' => true,
                'stock_quantity' => 10,
            ]);

        $this->createStoreSettings();

        $this
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('cart/index')
                    ->where(
                        'store.theme',
                        LandingPageTheme::FashionEditorial->value,
                    )
                    ->has('store.navigation_categories', 1)
                    ->where(
                        'store.navigation_categories.0.id',
                        $category->id,
                    )
                    ->where(
                        'store.navigation_categories.0.name',
                        'Accessories',
                    )
                    ->where(
                        'store.navigation_categories.0.slug',
                        'accessories',
                    ),
            );
    }

    public function test_fashion_theme_is_shared_with_checkout_page(): void
    {
        $category = Category::factory()->create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'is_active' => true,
                'stock_quantity' => 10,
            ]);

        $this->createStoreSettings();

        $this
            ->withSession([
                'cart.items' => [
                    $product->id => 1,
                ],
            ])
            ->get('/checkout')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/index')
                    ->where(
                        'store.theme',
                        LandingPageTheme::FashionEditorial->value,
                    )
                    ->has('store.navigation_categories', 1)
                    ->where(
                        'store.navigation_categories.0.id',
                        $category->id,
                    )
                    ->where(
                        'store.navigation_categories.0.name',
                        'Accessories',
                    )
                    ->where(
                        'store.navigation_categories.0.slug',
                        'accessories',
                    )
                    ->has('items', 1),
            );
    }

    public function test_missing_store_settings_fall_back_to_fashion_elegant(): void
    {
        $this
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('cart/index')
                    ->where(
                        'store.theme',
                        LandingPageTheme::FashionEditorial->value,
                    ),
            );
    }

    public function test_invalid_theme_value_falls_back_to_fashion_elegant(): void
    {
        $category = Category::factory()->create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);

        Product::factory()
            ->for($category)
            ->create([
                'is_active' => true,
                'stock_quantity' => 10,
            ]);

        $this->createStoreSettings('unknown_theme');

        $this
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('cart/index')
                    ->where(
                        'store.theme',
                        LandingPageTheme::FashionEditorial->value,
                    )
                    ->has('store.navigation_categories', 1),
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

        return StoreSetting::query()->create($attributes);
    }
}
