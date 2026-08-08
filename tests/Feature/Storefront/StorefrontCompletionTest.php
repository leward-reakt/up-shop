<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StorefrontCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_approved_content_page_is_visible(): void
    {
        Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'content' => 'About Up Shop.',
            'meta_title' => 'About Up Shop',
            'meta_description' => 'Learn about Up Shop.',
            'is_published' => true,
        ]);

        $this
            ->get('/about')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('pages/show')
                    ->where(
                        'contentPage.slug',
                        'about',
                    )
                    ->where(
                        'contentPage.meta_title',
                        'About Up Shop',
                    )
                    ->where(
                        'contentPage.meta_description',
                        'Learn about Up Shop.',
                    ),
            );
    }

    public function test_draft_content_page_is_not_public(): void
    {
        Page::query()->create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => 'Draft privacy content.',
            'is_published' => false,
        ]);

        $this
            ->get('/privacy-policy')
            ->assertNotFound();
    }

    public function test_unapproved_page_slug_is_not_exposed(): void
    {
        Page::query()->create([
            'title' => 'Unexpected Page',
            'slug' => 'unexpected-page',
            'content' => 'This is outside the approved MVP pages.',
            'is_published' => true,
        ]);

        $this
            ->get('/unexpected-page')
            ->assertNotFound();
    }

    public function test_sitemap_contains_visible_products_and_pages(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $visibleProduct = Product::factory()
            ->for($category)
            ->create([
                'slug' => 'visible-product',
                'is_active' => true,
            ]);

        $hiddenProduct = Product::factory()
            ->for($category)
            ->create([
                'slug' => 'hidden-product',
                'is_active' => false,
            ]);

        Page::query()->create([
            'title' => 'Shipping Policy',
            'slug' => 'shipping-policy',
            'content' => 'Shipping content.',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Terms & Conditions',
            'slug' => 'terms-and-conditions',
            'content' => 'Draft terms.',
            'is_published' => false,
        ]);

        $this
            ->get(route('seo.sitemap'))
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/xml; charset=UTF-8',
            )
            ->assertSee(
                route(
                    'products.show',
                    [
                        'product' => $visibleProduct->slug,
                    ],
                ),
                false,
            )
            ->assertDontSee(
                route(
                    'products.show',
                    [
                        'product' => $hiddenProduct->slug,
                    ],
                ),
                false,
            )
            ->assertSee(
                route(
                    'pages.show',
                    [
                        'page' => 'shipping-policy',
                    ],
                ),
                false,
            )
            ->assertDontSee(
                '/terms-and-conditions',
                false,
            );
    }

    public function test_robots_blocks_indexing_when_disabled(): void
    {
        config()->set(
            'seo.indexing_enabled',
            false,
        );

        $this
            ->get(route('seo.robots'))
            ->assertOk()
            ->assertSee(
                'Disallow: /',
                false,
            )
            ->assertDontSee(
                'Sitemap:',
                false,
            );
    }

    public function test_robots_exposes_sitemap_when_indexing_is_enabled(): void
    {
        config()->set(
            'seo.indexing_enabled',
            true,
        );

        $this
            ->get(route('seo.robots'))
            ->assertOk()
            ->assertSee(
                'Allow: /',
                false,
            )
            ->assertSee(
                'Sitemap: '.route('seo.sitemap'),
                false,
            )
            ->assertSee(
                'Disallow: /admin',
                false,
            )
            ->assertSee(
                'Disallow: /checkout',
                false,
            );
    }
}
