<?php

namespace Tests\Feature\Admin;

use App\Actions\Inventory\AdjustInventory;
use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\UpdateOrderStatus;
use App\Actions\Payments\UpdatePaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_order_edit_form(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $order = $this->createOrder();

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'filament.admin.resources.orders.edit',
                    ['record' => $order],
                ),
            )
            ->assertOk();
    }

    public function test_manual_inventory_adjustment_updates_stock_and_creates_history(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        app(AdjustInventory::class)->handle(
            product: $product,
            quantityChange: 5,
            user: $admin,
            notes: 'Stock count correction.',
        );

        $this->assertSame(
            15,
            $product->fresh()->stock_quantity,
        );

        $this->assertDatabaseHas(
            'inventory_adjustments',
            [
                'product_id' => $product->id,
                'user_id' => $admin->id,
                'quantity_change' => 5,
                'type' => 'manual',
            ],
        );
    }

    public function test_inventory_cannot_be_adjusted_below_zero(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        try {
            app(AdjustInventory::class)->handle(
                product: $product,
                quantityChange: -4,
                user: $admin,
                notes: 'Invalid adjustment.',
            );

            $this->fail(
                'Expected a validation exception.',
            );
        } catch (ValidationException) {
            $this->assertSame(
                3,
                $product->fresh()->stock_quantity,
            );
        }
    }

    public function test_order_status_follows_expected_workflow(): void
    {
        $order = $this->createOrder([
            'order_status' => OrderStatus::Pending,
        ]);

        app(UpdateOrderStatus::class)->handle(
            order: $order,
            status: OrderStatus::Confirmed,
        );

        $this->assertSame(
            OrderStatus::Confirmed,
            $order->fresh()->order_status,
        );
    }

    public function test_cancelling_pending_order_restores_inventory(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $order = $this->createOrder();

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => $product->price,
            'subtotal' => $product->price * 2,
        ]);

        $payment = $order->payment()->create([
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
        ]);

        app(CancelOrder::class)->handle(
            order: $order,
            user: $admin,
        );

        $this->assertSame(
            5,
            $product->fresh()->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Cancelled,
            $order->fresh()->order_status,
        );

        $this->assertSame(
            PaymentStatus::Cancelled,
            $payment->fresh()->status,
        );

        $this->assertDatabaseHas(
            'inventory_adjustments',
            [
                'product_id' => $product->id,
                'quantity_change' => 2,
                'type' => 'order_cancelled',
                'reference_id' => $order->id,
            ],
        );
    }

    public function test_payment_update_keeps_order_payment_status_in_sync(): void
    {
        $order = $this->createOrder();

        $payment = $order->payment()->create([
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
        ]);

        app(UpdatePaymentStatus::class)->handle(
            payment: $payment,
            status: PaymentStatus::Paid,
            reference: 'BANK-123',
            notes: 'Verified manually.',
        );

        $this->assertSame(
            PaymentStatus::Paid,
            $payment->fresh()->status,
        );

        $this->assertNotNull(
            $payment->fresh()->paid_at,
        );

        $this->assertSame(
            PaymentStatus::Paid,
            $order->fresh()->payment_status,
        );
    }

    public function test_inactive_authenticated_customer_is_logged_out(): void
    {
        $customer = User::factory()->create([
            'is_admin' => false,
            'is_active' => false,
        ]);

        $this
            ->actingAs($customer)
            ->get('/')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createOrder(
        array $overrides = [],
    ): Order {
        return Order::query()->create([
            'order_number' => 'TEST-'.fake()->unique()->numerify('######'),
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
            'subtotal' => 100_000,
            'discount_total' => 0,
            'shipping_total' => 15_000,
            'tax_total' => 0,
            'grand_total' => 115_000,
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,
            'customer_notes' => null,
            'admin_notes' => null,
            ...$overrides,
        ]);
    }
}
