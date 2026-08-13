<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingPageSection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_section_content_comes_from_database(): void
    {
        LandingPageSection::query()->update([
            'is_active' => false,
        ]);

        LandingPageSection::query()
            ->where('key', LandingPageSection::HERO)
            ->update([
                'eyebrow' => 'Summer 2026',
                'title' => 'A new expression.',
                'body' => 'Homepage content from the database.',
                'button_label' => 'Explore',
                'button_url' => 'shop',
                'image_path' => 'landing-page/hero.webp',
                'image_alt' => 'Editorial fashion campaign',
                'is_active' => true,
            ]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('home')
                    ->where(
                        'sections.hero.title',
                        'A new expression.',
                    )
                    ->where(
                        'sections.hero.body',
                        'Homepage content from the database.',
                    )
                    ->where(
                        'sections.hero.image_url',
                        Storage::disk('public')->url(
                            'landing-page/hero.webp',
                        ),
                    )
                    ->where(
                        'sections.hero.image_alt',
                        'Editorial fashion campaign',
                    )
                    ->missing('sections.story'),
            );
    }

    public function test_uploaded_category_image_is_used_for_collection_card(): void
    {
        $category = Category::factory()->create([
            'name' => 'Womenswear',
            'slug' => 'womenswear',
            'image_path' => 'categories/womenswear.webp',
            'image_alt' => 'Womenswear collection',
            'is_active' => true,
        ]);

        Product::factory()
            ->for($category)
            ->create([
                'is_active' => true,
            ]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('home')
                    ->where(
                        'categories.0.image_url',
                        Storage::disk('public')->url(
                            'categories/womenswear.webp',
                        ),
                    )
                    ->where(
                        'categories.0.image_alt',
                        'Womenswear collection',
                    ),
            );
    }
}
