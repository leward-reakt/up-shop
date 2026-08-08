<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            [
                'email' => 'admin@example.com',
            ],
            [
                'name' => 'Local Admin',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_active' => true,
            ],
        );

        User::query()->updateOrCreate(
            [
                'email' => 'customer@example.com',
            ],
            [
                'name' => 'Local Customer',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_active' => true,
            ],
        );

        $categories = collect([
            [
                'name' => 'Apparel',
                'slug' => 'apparel',
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
            ],
            [
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
            ],
        ])->map(
            fn (
                array $category,
            ): Category => Category::query()
                ->updateOrCreate(
                    [
                        'slug' => $category['slug'],
                    ],
                    [
                        ...$category,
                        'is_active' => true,
                    ],
                ),
        );

        if (Product::query()->doesntExist()) {
            $categories->each(
                fn (
                    Category $category,
                ) => Product::factory()
                    ->count(4)
                    ->for($category)
                    ->create(),
            );
        }

        Discount::query()->updateOrCreate(
            [
                'code' => 'WELCOME10',
            ],
            [
                'type' => 'percentage',
                'value' => 10,
                'minimum_purchase' => 100_000,
                'starts_at' => now()->startOfDay(),
                'expires_at' => now()
                    ->addYear()
                    ->endOfDay(),
                'is_active' => true,
            ],
        );

        StoreSetting::query()->updateOrCreate(
            [
                'store_name' => 'Up Shop',
            ],
            [
                'store_email' => 'hello@example.com',
                'currency' => 'PHP',
                'default_shipping_fee' => 15_000,
                'free_shipping_threshold' => 300_000,
                'tax_rate_basis_points' => null,
                'social_links' => [],
            ],
        );

        $pages = [
            [
                'title' => 'About',
                'slug' => 'about',
                'content' => 'Learn more about Up Shop and our store.',
                'meta_title' => 'About',
                'meta_description' => 'Learn more about Up Shop and the store behind our product catalog.',
            ],

            [
                'title' => 'Contact',
                'slug' => 'contact',
                'content' => 'Contact Up Shop for questions about products, orders, shipping, or your account.',
                'meta_title' => 'Contact',
                'meta_description' => 'Contact Up Shop for questions about products, orders, shipping, or your account.',
            ],

            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => 'Privacy policy content.',
                'meta_title' => 'Privacy Policy',
                'meta_description' => 'Read the Up Shop privacy policy.',
            ],

            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-and-conditions',
                'content' => 'Terms and conditions content.',
                'meta_title' => 'Terms & Conditions',
                'meta_description' => 'Read the Up Shop terms and conditions.',
            ],

            [
                'title' => 'Shipping Policy',
                'slug' => 'shipping-policy',
                'content' => 'Shipping policy content.',
                'meta_title' => 'Shipping Policy',
                'meta_description' => 'Read information about Up Shop shipping and delivery.',
            ],

            [
                'title' => 'Return / Refund Policy',
                'slug' => 'return-refund-policy',
                'content' => 'Return and refund policy content.',
                'meta_title' => 'Return / Refund Policy',
                'meta_description' => 'Read the Up Shop return and refund policy.',
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->updateOrCreate(
                [
                    'slug' => $page['slug'],
                ],
                [
                    ...$page,
                    'is_published' => true,
                ],
            );
        }
    }
}
