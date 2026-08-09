<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_payment_view_displays_payment_context(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $order = $this->createOrder();

        $payment = $order->payment()->create([
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'reference' => null,
            'paid_at' => null,
            'notes' => null,
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'filament.admin.resources.payments.view',
                    ['record' => $payment],
                ),
            )
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Bank Transfer')
            ->assertSee('Not provided')
            ->assertSee('No notes')
            ->assertSee('Not paid yet');
    }

    public function test_admin_payment_edit_displays_order_context(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $order = $this->createOrder();

        $payment = $order->payment()->create([
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'reference' => null,
            'paid_at' => null,
            'notes' => null,
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'filament.admin.resources.payments.edit',
                    ['record' => $payment],
                ),
            )
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Bank Transfer')
            ->assertSee('Enter bank transfer reference')
            ->assertSee('Optional payment notes')
            ->assertSee('Not paid yet');
    }

    private function createOrder(): Order
    {
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

            'subtotal' => 500_000,
            'discount_total' => 0,
            'shipping_total' => 28_300,
            'tax_total' => 0,
            'grand_total' => 528_300,

            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,

            'customer_notes' => null,
            'admin_notes' => null,
        ]);
    }
}
