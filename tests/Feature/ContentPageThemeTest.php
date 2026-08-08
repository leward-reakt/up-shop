<?php

namespace Tests\Feature;

use App\Enums\LandingPageTheme;
use App\Http\Controllers\ContentPageController;
use App\Models\Page;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContentPageThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_pages_receive_selected_fashion_elegant_theme(): void
    {
        $this->createStoreSettings(
            LandingPageTheme::FashionEditorial,
        );

        foreach (ContentPageController::PUBLIC_SLUGS as $slug) {
            Page::query()->create([
                'title' => str($slug)
                    ->replace('-', ' ')
                    ->title()
                    ->toString(),
                'slug' => $slug,
                'content' => 'Example content for this public page.',
                'meta_title' => null,
                'meta_description' => null,
                'is_published' => true,
            ]);

            $this
                ->get(
                    route('pages.show', [
                        'page' => $slug,
                    ]),
                )
                ->assertOk()
                ->assertInertia(
                    fn (Assert $page) => $page
                        ->component('pages/show')
                        ->where('contentPage.slug', $slug)
                        ->where(
                            'store.theme',
                            LandingPageTheme::FashionEditorial->value,
                        ),
                );
        }
    }

    public function test_content_pages_keep_default_theme_when_default_is_selected(): void
    {
        $this->createStoreSettings(
            LandingPageTheme::Default,
        );

        Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'content' => 'About page content.',
            'meta_title' => null,
            'meta_description' => null,
            'is_published' => true,
        ]);

        $this
            ->get(
                route('pages.show', [
                    'page' => 'about',
                ]),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('pages/show')
                    ->where('contentPage.slug', 'about')
                    ->where(
                        'store.theme',
                        LandingPageTheme::Default->value,
                    ),
            );
    }

    private function createStoreSettings(
        LandingPageTheme $theme,
    ): StoreSetting {
        return StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'store_email' => 'support@example.com',
            'contact_number' => '+63 900 000 0000',
            'business_address' => 'Metro Manila, Philippines',
            'currency' => 'PHP',
            'default_shipping_fee' => 15000,
            'free_shipping_threshold' => 300000,
            'tax_rate_basis_points' => 0,
            'social_links' => [],
            'landing_page_theme' => $theme->value,
        ]);
    }
}
