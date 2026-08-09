<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryArchivingTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_edit_page_does_not_expose_force_delete(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        Livewire::test(EditCategory::class, [
            'record' => $category->getKey(),
        ])
            ->assertActionExists('delete')
            ->assertActionDoesNotExist('forceDelete');
    }

    public function test_category_can_be_archived_and_restored(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        Livewire::test(EditCategory::class, [
            'record' => $category->getKey(),
        ])
            ->callAction('delete')
            ->assertNotified()
            ->assertRedirect();

        $this->assertSoftDeleted('categories', [
            'id' => $category->getKey(),
        ]);

        Livewire::test(EditCategory::class, [
            'record' => $category->getKey(),
        ])
            ->assertActionExists('restore')
            ->assertActionDoesNotExist('forceDelete')
            ->callAction('restore')
            ->assertNotified();

        $restoredCategory = Category::query()->findOrFail(
            $category->getKey(),
        );

        $this->assertFalse($restoredCategory->trashed());
    }

    public function test_category_list_exposes_archived_filter(): void
    {
        $this->actingAsAdmin();

        Livewire::test(ListCategories::class)
            ->assertTableFilterExists('trashed');
    }

    public function test_archiving_category_preserves_product_assignment_and_hides_product_from_storefront(): void
    {
        $category = Category::factory()->create([
            'name' => 'Archived Category',
            'slug' => 'archived-category',
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'name' => 'Archived Category Product',
                'slug' => 'archived-category-product',
                'is_active' => true,
            ]);

        $this
            ->get('/shop')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.id',
                        $product->getKey(),
                    ),
            );

        $category->delete();

        $this->assertSoftDeleted('categories', [
            'id' => $category->getKey(),
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $this
            ->get('/shop')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->has('products.data', 0),
            );

        $this
            ->get("/products/{$product->slug}")
            ->assertNotFound();
    }

    private function actingAsAdmin(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Filament::setCurrentPanel('admin');
    }
}
