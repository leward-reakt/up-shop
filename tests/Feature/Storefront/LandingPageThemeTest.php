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

    public function test_default_theme_keeps_existing_home_page(): void
    {
        $this->createStoreSettings();

        $this
            ->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('home')
                    ->where(
                        'theme',
                        LandingPageTheme::Default->value,
                    )
                    ->has('featuredProducts')
                    ->has('categories', 0)
                    ->has('newArrivals', 0),
            );
    }

    public function test_fashion_editorial_theme_receives_landing_catalog_data(): void
    {
        $category = Category::factory()->create([
            'name' => 'Bags',
            'slug' => 'bags',
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'name' => 'Editorial Bag',
                'is_active' => true,
                'is_featured' => true,
                'stock_quantity' => 10,
            ]);

        $this->createStoreSettings(
            LandingPageTheme::FashionEditorial->value,
        );

        $this
            ->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('home')
                    ->where(
                        'theme',
                        LandingPageTheme::FashionEditorial->value,
                    )
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

        $this->createStoreSettings(
            LandingPageTheme::FashionEditorial->value,
        );

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

        $this->createStoreSettings(
            LandingPageTheme::FashionEditorial->value,
        );

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

    public function test_default_theme_does_not_share_fashion_navigation_categories(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        Product::factory()
            ->for($category)
            ->create([
                'is_active' => true,
                'stock_quantity' => 10,
            ]);

        $this->createStoreSettings(
            LandingPageTheme::Default->value,
        );

        $this
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('cart/index')
                    ->where(
                        'store.theme',
                        LandingPageTheme::Default->value,
                    )
                    ->has('store.navigation_categories', 0),
            );
    }

    public function test_invalid_theme_value_falls_back_to_default(): void
    {
        $this->createStoreSettings('unknown_theme');

        $this
            ->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('home')
                    ->where(
                        'theme',
                        LandingPageTheme::Default->value,
                    ),
            );

        $this
            ->get('/cart')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('cart/index')
                    ->where(
                        'store.theme',
                        LandingPageTheme::Default->value,
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

        return StoreSetting::query()->create($attributes);
    }
}
