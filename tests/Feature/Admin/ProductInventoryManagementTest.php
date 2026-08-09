<?php

namespace Tests\Feature\Admin;

use App\Actions\Inventory\AdjustInventory;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductInventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_quantity_can_be_set_on_create_but_not_changed_from_edit_form(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 12,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateProduct::class)
            ->assertFormFieldEnabled('stock_quantity');

        Livewire::test(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ])
            ->assertFormFieldDisabled('stock_quantity')
            ->fillForm([
                'stock_quantity' => 99,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            12,
            $product->refresh()->stock_quantity,
        );
    }

    public function test_manual_stock_adjustment_updates_inventory_and_creates_audit_record(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $adjustedProduct = app(AdjustInventory::class)->handle(
            product: $product,
            quantityChange: 5,
            user: $admin,
            notes: 'Received five replacement units.',
        );

        $this->assertSame(
            15,
            $adjustedProduct->stock_quantity,
        );

        $this->assertDatabaseCount(
            'inventory_adjustments',
            1,
        );

        $this->assertDatabaseHas('inventory_adjustments', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'quantity_change' => 5,
            'type' => 'manual',
            'reference_type' => null,
            'reference_id' => null,
            'notes' => 'Received five replacement units.',
        ]);
    }
}
