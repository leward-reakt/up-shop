<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_customer_order(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $customer = User::factory()->create();

        $order = $this->createOrder([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'filament.admin.resources.orders.view',
                    ['record' => $order],
                ),
            )
            ->assertOk();
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $customer = User::factory()->create();

        $otherCustomer = User::factory()->create();

        $order = $this->createOrder([
            'user_id' => $otherCustomer->id,
        ]);

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'account.orders.show',
                    $order,
                ),
            )
            ->assertForbidden();
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
            'pickup_location' => null,

            'discount_code' => null,

            'subtotal' => 100_000,
            'discount_total' => 0,
            'shipping_total' => 15_000,
            'tax_total' => 0,
            'grand_total' => 115_000,

            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,

            'customer_notes' => null,
            'admin_notes' => null,

            ...$overrides,
        ]);
    }
}
