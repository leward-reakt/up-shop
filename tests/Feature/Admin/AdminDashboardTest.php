<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\InventoryAlerts;
use App\Filament\Widgets\RecentOrders;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_stats_overview_reports_revenue_from_paid_orders_only(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        StoreSetting::query()->create([
            'store_name' => 'USD Test Store',
            'currency' => 'USD',
            'default_shipping_fee' => 0,
        ]);

        User::factory()
            ->count(2)
            ->create();

        Product::factory()
            ->count(2)
            ->create();

        $this->createOrder([
            'order_number' => 'PENDING-001',
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,
            'grand_total' => 50_000,
        ]);

        $this->createOrder([
            'order_number' => 'PAID-001',
            'payment_status' => PaymentStatus::Paid,
            'order_status' => OrderStatus::Confirmed,
            'grand_total' => 115_000,
        ]);

        $this->createOrder([
            'order_number' => 'UNPAID-COMPLETED-001',
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Completed,
            'grand_total' => 200_000,
        ]);

        $this->createOrder([
            'order_number' => 'REFUNDED-COMPLETED-001',
            'payment_status' => PaymentStatus::Refunded,
            'order_status' => OrderStatus::Completed,
            'grand_total' => 300_000,
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminStatsOverview::class)
            ->assertSee('Total orders')
            ->assertSee('Pending orders')
            ->assertSee('Processing orders')
            ->assertSee('Completed orders')
            ->assertSee('Cancelled orders')
            ->assertSee('Total customers')
            ->assertSee('Total products')
            ->assertSee('Total revenue')
            ->assertSee('$1,150.00');
    }

    public function test_customer_spending_query_sums_paid_orders_only(): void
    {
        $customer = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->createOrder([
            'order_number' => 'CUSTOMER-PAID-001',
            'user_id' => $customer->id,
            'payment_status' => PaymentStatus::Paid,
            'order_status' => OrderStatus::Confirmed,
            'grand_total' => 80_000,
        ]);

        $this->createOrder([
            'order_number' => 'CUSTOMER-PAID-002',
            'user_id' => $customer->id,
            'payment_status' => PaymentStatus::Paid,
            'order_status' => OrderStatus::Completed,
            'grand_total' => 45_000,
        ]);

        $this->createOrder([
            'order_number' => 'CUSTOMER-PENDING-001',
            'user_id' => $customer->id,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Completed,
            'grand_total' => 200_000,
        ]);

        $this->createOrder([
            'order_number' => 'CUSTOMER-REFUNDED-001',
            'user_id' => $customer->id,
            'payment_status' => PaymentStatus::Refunded,
            'order_status' => OrderStatus::Completed,
            'grand_total' => 300_000,
        ]);

        $reportedCustomer = UserResource::getEloquentQuery()
            ->findOrFail($customer->id);

        $this->assertSame(
            4,
            (int) $reportedCustomer->getAttribute('orders_count'),
        );

        $this->assertSame(
            125_000,
            (int) $reportedCustomer->getAttribute('paid_spending'),
        );
    }

    public function test_recent_orders_widget_displays_latest_orders(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        StoreSetting::query()->create([
            'store_name' => 'USD Test Store',
            'currency' => 'USD',
            'default_shipping_fee' => 0,
        ]);

        $order = $this->createOrder([
            'order_number' => 'RECENT-001',
        ]);

        $this->actingAs($admin);

        Livewire::test(RecentOrders::class)
            ->assertSee($order->order_number)
            ->assertSee($order->customer_name)
            ->assertSee('$1,150.00');
    }

    public function test_inventory_alerts_show_low_and_out_of_stock_products(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $outOfStock = Product::factory()->create([
            'name' => 'Out Of Stock Product',
            'stock_quantity' => 0,
            'low_stock_threshold' => 5,
        ]);

        $lowStock = Product::factory()->create([
            'name' => 'Low Stock Product',
            'stock_quantity' => 3,
            'low_stock_threshold' => 5,
        ]);

        $healthyStock = Product::factory()->create([
            'name' => 'Healthy Stock Product',
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
        ]);

        $this->actingAs($admin);

        Livewire::test(InventoryAlerts::class)
            ->assertSee($outOfStock->name)
            ->assertSee($lowStock->name)
            ->assertDontSee($healthyStock->name)
            ->assertSee('Out of stock')
            ->assertSee('Low stock (3)');
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

            'user_id' => null,

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
