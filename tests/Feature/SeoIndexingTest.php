<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoIndexingTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_pages_emit_noindex_nofollow_when_indexing_is_disabled(): void
    {
        config()->set(
            'seo.indexing_enabled',
            false,
        );

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee(
                '<meta name="robots" content="noindex,nofollow">',
                false,
            );
    }

    public function test_inertia_pages_do_not_emit_noindex_when_indexing_is_enabled(): void
    {
        config()->set(
            'seo.indexing_enabled',
            true,
        );

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(
                '<meta name="robots" content="noindex,nofollow">',
                false,
            );
    }

    public function test_robots_blocks_all_crawlers_when_indexing_is_disabled(): void
    {
        config()->set(
            'seo.indexing_enabled',
            false,
        );

        $this
            ->get(route('seo.robots'))
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'text/plain; charset=UTF-8',
            )
            ->assertContent(
                "User-agent: *\nDisallow: /\n",
            );
    }

    public function test_existing_robots_rules_are_preserved_when_indexing_is_enabled(): void
    {
        config()->set(
            'seo.indexing_enabled',
            true,
        );

        $expectedContent = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /dashboard',
            'Disallow: /account',
            'Disallow: /settings',
            '',
            'Sitemap: '.route('seo.sitemap'),
            '',
        ]);

        $this
            ->get(route('seo.robots'))
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'text/plain; charset=UTF-8',
            )
            ->assertContent($expectedContent);
    }
}
