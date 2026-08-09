<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductArchivingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);

        Filament::setCurrentPanel('admin');
    }

    public function test_product_edit_page_does_not_expose_force_delete(): void
    {
        $product = Product::factory()->create();

        Livewire::test(EditProduct::class, [
            'record' => $product->getKey(),
        ])
            ->assertActionExists('delete')
            ->assertActionDoesNotExist('forceDelete');
    }

    public function test_product_can_be_archived_and_restored(): void
    {
        $product = Product::factory()->create();

        Livewire::test(EditProduct::class, [
            'record' => $product->getKey(),
        ])
            ->callAction('delete')
            ->assertNotified()
            ->assertRedirect();

        $this->assertSoftDeleted('products', [
            'id' => $product->getKey(),
        ]);

        Livewire::test(EditProduct::class, [
            'record' => $product->getKey(),
        ])
            ->assertActionExists('restore')
            ->assertActionDoesNotExist('forceDelete')
            ->callAction('restore')
            ->assertNotified();

        $restoredProduct = Product::query()->findOrFail(
            $product->getKey(),
        );

        $this->assertFalse($restoredProduct->trashed());
    }

    public function test_product_list_exposes_archived_filter(): void
    {
        Livewire::test(ListProducts::class)
            ->assertTableFilterExists('trashed');
    }

    public function test_archiving_product_preserves_order_snapshot_and_inventory_history(): void
    {
        $product = Product::factory()->create([
            'name' => 'Snapshot Product',
            'sku' => 'SNAPSHOT-001',
            'price' => 125000,
            'stock_quantity' => 10,
        ]);

        $order = Order::query()->create([
            'order_number' => 'UP-ARCHIVE-TEST',
            'user_id' => null,
            'discount_id' => null,

            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '09171234567',

            'shipping_address_line_1' => '123 Test Street',
            'shipping_address_line_2' => null,
            'shipping_city' => 'Manila',
            'shipping_province' => 'Metro Manila',
            'shipping_postal_code' => '1000',
            'shipping_country' => 'PH',

            'shipping_method' => ShippingMethod::FlatRate,
            'discount_code' => null,

            'subtotal' => 125000,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 125000,

            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,

            'customer_notes' => null,
            'admin_notes' => null,
        ]);

        $orderItem = $order->items()->create([
            'product_id' => $product->getKey(),
            'product_name' => 'Snapshot Product',
            'sku' => 'SNAPSHOT-001',
            'quantity' => 1,
            'unit_price' => 125000,
            'subtotal' => 125000,
        ]);

        $inventoryAdjustment = $product
            ->inventoryAdjustments()
            ->create([
                'user_id' => $this->admin->getKey(),
                'quantity_change' => -1,
                'type' => 'order',
                'reference_type' => 'order',
                'reference_id' => $order->getKey(),
                'notes' => 'Historical inventory adjustment.',
            ]);

        $product->delete();

        $this->assertSoftDeleted('products', [
            'id' => $product->getKey(),
        ]);

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => 'Snapshot Product',
            'sku' => 'SNAPSHOT-001',
            'unit_price' => 125000,
            'subtotal' => 125000,
        ]);

        $this->assertDatabaseHas('inventory_adjustments', [
            'id' => $inventoryAdjustment->getKey(),
            'product_id' => $product->getKey(),
            'quantity_change' => -1,
            'reference_type' => 'order',
            'reference_id' => $order->getKey(),
        ]);
    }
}
