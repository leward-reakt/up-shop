<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderDateFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_orders_by_optional_from_and_to_dates(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $olderOrder = $this->createOrder(
            orderNumber: 'ORDER-OLDER',
            createdAt: '2026-08-03 23:59:59',
        );

        $matchingOrder = $this->createOrder(
            orderNumber: 'ORDER-MATCHING',
            createdAt: '2026-08-05 14:30:00',
        );

        $newerOrder = $this->createOrder(
            orderNumber: 'ORDER-NEWER',
            createdAt: '2026-08-08 00:00:01',
        );

        $this->actingAs($admin);

        Livewire::test(ListOrders::class)
            ->filterTable('created_at', [
                'from' => '2026-08-04',
                'to' => '2026-08-07',
            ])
            ->assertCanSeeTableRecords([
                $matchingOrder,
            ])
            ->assertCanNotSeeTableRecords([
                $olderOrder,
                $newerOrder,
            ]);

        Livewire::test(ListOrders::class)
            ->filterTable('created_at', [
                'from' => '2026-08-05',
                'to' => null,
            ])
            ->assertCanSeeTableRecords([
                $matchingOrder,
                $newerOrder,
            ])
            ->assertCanNotSeeTableRecords([
                $olderOrder,
            ]);

        Livewire::test(ListOrders::class)
            ->filterTable('created_at', [
                'from' => null,
                'to' => '2026-08-05',
            ])
            ->assertCanSeeTableRecords([
                $olderOrder,
                $matchingOrder,
            ])
            ->assertCanNotSeeTableRecords([
                $newerOrder,
            ]);
    }

    private function createOrder(
        string $orderNumber,
        string $createdAt,
    ): Order {
        $order = Order::query()->create([
            'order_number' => $orderNumber,

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
        ]);

        $order->forceFill([
            'created_at' => $createdAt,
        ])->saveQuietly();

        return $order->refresh();
    }
}
