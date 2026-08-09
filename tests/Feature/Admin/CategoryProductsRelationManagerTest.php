<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\RelationManagers\ProductsRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryProductsRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_only_products_belonging_to_the_category(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        $categoryProducts = Product::factory()
            ->count(2)
            ->for($category)
            ->create();

        $otherProduct = Product::factory()
            ->for($otherCategory)
            ->create();

        $this->actingAs($admin);

        Livewire::test(EditCategory::class, [
            'record' => $category->getRouteKey(),
        ])
            ->assertSeeLivewire(ProductsRelationManager::class);

        Livewire::test(ProductsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => EditCategory::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords($categoryProducts)
            ->assertCanNotSeeTableRecords([$otherProduct])
            ->assertActionDoesNotExist(
                TestAction::make('create')->table(),
            )
            ->assertActionDoesNotExist(
                TestAction::make('edit')->table(
                    $categoryProducts->first(),
                ),
            )
            ->assertActionDoesNotExist(
                TestAction::make('delete')->table(
                    $categoryProducts->first(),
                ),
            );
    }
}
