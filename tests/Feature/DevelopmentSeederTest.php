<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Database\Seeders\DevelopmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_realistic_development_data(): void
    {
        $this->seed(DevelopmentSeeder::class);

        $this->assertSame(
            2,
            User::query()->count(),
        );

        $this->assertSame(
            5,
            Category::query()->count(),
        );

        $this->assertSame(
            25,
            Product::query()->count(),
        );

        $this->assertSame(
            8,
            Product::query()
                ->where('is_active', true)
                ->where('is_featured', true)
                ->count(),
        );

        $this->assertSame(
            2,
            Discount::query()->count(),
        );

        $this->assertSame(
            6,
            Page::query()->count(),
        );

        $this->assertSame(
            1,
            StoreSetting::query()->count(),
        );

        $this->assertDatabaseHas('categories', [
            'name' => 'Footwear',
            'slug' => 'footwear',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Beauty & Self-Care',
            'slug' => 'beauty-self-care',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'UPS-APP-001',
            'slug' => 'linen-blend-overshirt',
            'name' => 'Linen Blend Overshirt',
            'price' => 189_000,
            'stock_quantity' => 18,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'UPS-FOT-005',
            'slug' => 'canvas-slip-on',
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'UPS-LIF-005',
            'slug' => 'compact-desk-organizer',
            'stock_quantity' => 12,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('discounts', [
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'minimum_purchase' => 100_000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('discounts', [
            'code' => 'SAVE250',
            'type' => 'fixed',
            'value' => 25_000,
            'minimum_purchase' => 250_000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('store_settings', [
            'store_name' => 'Up Shop',
            'store_email' => 'hello@upshop.test',
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 300_000,
            'landing_page_theme' => 'fashion_editorial',
        ]);

        $this->assertDatabaseHas('pages', [
            'slug' => 'about',
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('pages', [
            'slug' => 'privacy-policy',
            'is_published' => true,
        ]);

        $admin = User::query()
            ->where('email', 'admin@example.com')
            ->firstOrFail();

        $this->assertTrue($admin->is_admin);
        $this->assertTrue($admin->is_active);
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_it_can_be_run_repeatedly_without_duplicating_seeded_data(): void
    {
        $this->seed(DevelopmentSeeder::class);
        $this->seed(DevelopmentSeeder::class);

        $this->assertSame(
            2,
            User::query()->count(),
        );

        $this->assertSame(
            5,
            Category::query()->count(),
        );

        $this->assertSame(
            25,
            Product::query()->count(),
        );

        $this->assertSame(
            2,
            Discount::query()->count(),
        );

        $this->assertSame(
            6,
            Page::query()->count(),
        );

        $this->assertSame(
            1,
            StoreSetting::query()->count(),
        );
    }

    public function test_it_restores_soft_deleted_seeded_catalog_records(): void
    {
        $this->seed(DevelopmentSeeder::class);

        $product = Product::query()
            ->where('sku', 'UPS-APP-001')
            ->firstOrFail();

        $category = Category::query()
            ->where('slug', 'apparel')
            ->firstOrFail();

        $page = Page::query()
            ->where('slug', 'about')
            ->firstOrFail();

        $product->delete();
        $category->delete();
        $page->delete();

        $this->seed(DevelopmentSeeder::class);

        $this->assertDatabaseHas('products', [
            'sku' => 'UPS-APP-001',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('categories', [
            'slug' => 'apparel',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('pages', [
            'slug' => 'about',
            'deleted_at' => null,
        ]);
    }
}
