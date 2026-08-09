<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PageResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
    }

    public function test_public_slug_set_matches_locked_mvp_scope(): void
    {
        $this->assertSame(
            [
                'about',
                'contact',
                'privacy-policy',
                'terms-and-conditions',
                'shipping-policy',
                'return-refund-policy',
            ],
            Page::publicSlugs(),
        );
    }

    public function test_admin_can_create_page_with_approved_slug(): void
    {
        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'About',
                'slug' => 'about',
                'content' => 'About the store.',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', [
            'title' => 'About',
            'slug' => 'about',
        ]);
    }

    public function test_admin_cannot_create_page_with_unapproved_slug(): void
    {
        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Summer Sale',
                'slug' => 'summer-sale',
                'content' => 'Unapproved content page.',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'slug',
            ]);

        $this->assertDatabaseMissing('pages', [
            'slug' => 'summer-sale',
        ]);
    }

    public function test_admin_cannot_rename_page_to_unapproved_slug(): void
    {
        $page = Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'content' => 'About the store.',
            'is_published' => true,
        ]);

        Livewire::test(EditPage::class, [
            'record' => $page->getRouteKey(),
        ])
            ->fillForm([
                'slug' => 'summer-sale',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'slug',
            ]);

        $this->assertSame(
            'about',
            $page->refresh()->slug,
        );
    }
}
