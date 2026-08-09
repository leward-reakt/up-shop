<?php

namespace Tests\Feature\Admin;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\InventoryAdjustment;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderWorkflowEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_order_status_transition_is_rejected(): void
    {
        $order = $this->createOrder([
            'order_status' => OrderStatus::Pending,
        ]);

        try {
            app(UpdateOrderStatus::class)->handle(
                order: $order,
                status: OrderStatus::Processing,
            );

            $this->fail(
                'Expected an invalid order status transition to be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'order_status',
                $exception->errors(),
            );
        }

        $this->assertSame(
            OrderStatus::Pending,
            $order->fresh()->order_status,
        );
    }

    public function test_paid_order_cannot_be_cancelled_or_restocked(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $order = $this->createOrder([
            'payment_status' => PaymentStatus::Paid,
        ]);

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
            'status' => PaymentStatus::Paid,
            'amount' => $order->grand_total,
            'reference' => 'BANK-PAID-001',
            'paid_at' => now(),
        ]);

        try {
            app(CancelOrder::class)->handle(
                order: $order,
                user: $admin,
            );

            $this->fail(
                'Expected a paid order cancellation to be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'order',
                $exception->errors(),
            );
        }

        $this->assertSame(
            3,
            $product->fresh()->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Pending,
            $order->fresh()->order_status,
        );

        $this->assertSame(
            PaymentStatus::Paid,
            $payment->fresh()->status,
        );

        $this->assertDatabaseCount(
            'inventory_adjustments',
            0,
        );
    }

    public function test_shipped_cod_order_cannot_be_cancelled_or_restocked(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $order = $this->createOrder([
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Shipped,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => $product->price,
            'subtotal' => $product->price * 2,
        ]);

        $payment = $order->payment()->create([
            'method' => PaymentMethod::CashOnDelivery,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
        ]);

        try {
            app(CancelOrder::class)->handle(
                order: $order,
                user: $admin,
            );

            $this->fail(
                'Expected a shipped COD order cancellation to be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'order',
                $exception->errors(),
            );
        }

        $this->assertSame(
            3,
            $product->fresh()->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Shipped,
            $order->fresh()->order_status,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $order->fresh()->payment_status,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
        );

        $this->assertDatabaseCount(
            'inventory_adjustments',
            0,
        );
    }

    public function test_completed_unpaid_order_cannot_be_cancelled_or_restocked(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $order = $this->createOrder([
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Completed,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => $product->price,
            'subtotal' => $product->price * 2,
        ]);

        $payment = $order->payment()->create([
            'method' => PaymentMethod::CashOnDelivery,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
        ]);

        try {
            app(CancelOrder::class)->handle(
                order: $order,
                user: $admin,
            );

            $this->fail(
                'Expected a completed order cancellation to be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'order',
                $exception->errors(),
            );
        }

        $this->assertSame(
            3,
            $product->fresh()->stock_quantity,
        );

        $this->assertSame(
            OrderStatus::Completed,
            $order->fresh()->order_status,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
        );

        $this->assertDatabaseCount(
            'inventory_adjustments',
            0,
        );
    }

    public function test_processing_cod_order_can_be_cancelled_and_restocked(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $order = $this->createOrder([
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Processing,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => $product->price,
            'subtotal' => $product->price * 2,
        ]);

        $payment = $order->payment()->create([
            'method' => PaymentMethod::CashOnDelivery,
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
            $order->fresh()->payment_status,
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
                'reference_type' => 'order',
                'reference_id' => $order->id,
            ],
        );
    }

    public function test_cancelling_same_order_twice_restores_inventory_only_once(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
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

        $order->payment()->create([
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
        ]);

        app(CancelOrder::class)->handle(
            order: $order,
            user: $admin,
        );

        app(CancelOrder::class)->handle(
            order: $order->fresh(),
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
            1,
            InventoryAdjustment::query()
                ->where('product_id', $product->id)
                ->where('type', 'order_cancelled')
                ->where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->count(),
        );
    }

    public function test_processing_status_respects_shipping_method(): void
    {
        $deliveryOrder = $this->createOrder([
            'payment_status' => PaymentStatus::Paid,
            'order_status' => OrderStatus::Processing,
            'shipping_method' => ShippingMethod::FlatRate,
        ]);

        $pickupOrder = $this->createOrder([
            'payment_status' => PaymentStatus::Paid,
            'order_status' => OrderStatus::Processing,
            'shipping_method' => ShippingMethod::StorePickup,
        ]);

        $this->assertSame(
            [OrderStatus::Shipped],
            UpdateOrderStatus::allowedNextStatuses(
                $deliveryOrder,
            ),
        );

        $this->assertSame(
            [OrderStatus::ReadyForPickup],
            UpdateOrderStatus::allowedNextStatuses(
                $pickupOrder,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createOrder(
        array $overrides = [],
    ): Order {
        return Order::query()->create([
            'order_number' => 'TEST-'.fake()
                ->unique()
                ->numerify('######'),

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
