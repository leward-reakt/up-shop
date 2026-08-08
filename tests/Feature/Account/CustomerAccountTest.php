<?php

namespace Tests\Feature\Account;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_customer_dashboard(): void
    {
        $this
            ->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_customer_dashboard_shows_order_summary(): void
    {
        $customer = User::factory()->create();

        $this->createOrder(
            customer: $customer,
            status: OrderStatus::Pending,
        );

        $this->createOrder(
            customer: $customer,
            status: OrderStatus::Completed,
        );

        $this
            ->actingAs($customer)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('dashboard')
                    ->where('summary.total_orders', 2)
                    ->where('summary.active_orders', 1)
                    ->where('summary.completed_orders', 1)
                    ->has('recent_orders', 2),
            );
    }

    public function test_customer_only_sees_their_own_orders(): void
    {
        $customer = User::factory()->create();

        $otherCustomer = User::factory()->create();

        $ownOrder = $this->createOrder(
            customer: $customer,
        );

        $this->createOrder(
            customer: $otherCustomer,
        );

        $this
            ->actingAs($customer)
            ->get(route('account.orders.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('account/orders/index')
                    ->has('orders.data', 1)
                    ->where(
                        'orders.data.0.id',
                        $ownOrder->id,
                    ),
            );
    }

    public function test_customer_can_view_their_order_details(): void
    {
        $customer = User::factory()->create();

        $order = $this->createOrder(
            customer: $customer,
        );

        $order->payment()->create([
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Paid,
            'amount' => $order->grand_total,
            'reference' => 'BANK-123',
            'paid_at' => now(),
        ]);

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'account.orders.show',
                    $order,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('account/orders/show')
                    ->where('order.id', $order->id)
                    ->where(
                        'order.order_number',
                        $order->order_number,
                    )
                    ->where(
                        'order.payment.status',
                        PaymentStatus::Paid->value,
                    )
                    ->where(
                        'order.payment.reference',
                        'BANK-123',
                    ),
            );
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $customer = User::factory()->create();

        $otherCustomer = User::factory()->create();

        $order = $this->createOrder(
            customer: $otherCustomer,
        );

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

    public function test_first_address_becomes_default_automatically(): void
    {
        $customer = User::factory()->create();

        $this
            ->actingAs($customer)
            ->post(
                route('account.addresses.store'),
                $this->addressPayload(),
            )
            ->assertRedirect(
                route('account.addresses.index'),
            );

        $address = $customer
            ->addresses()
            ->firstOrFail();

        $this->assertTrue(
            $address->is_default,
        );
    }

    public function test_customer_can_change_default_address(): void
    {
        $customer = User::factory()->create();

        $firstAddress = $customer
            ->addresses()
            ->create([
                ...$this->addressPayload([
                    'label' => 'Home',
                ]),
                'country' => 'PH',
                'is_default' => true,
            ]);

        $this
            ->actingAs($customer)
            ->post(
                route('account.addresses.store'),
                $this->addressPayload([
                    'label' => 'Office',
                    'is_default' => true,
                ]),
            )
            ->assertRedirect(
                route('account.addresses.index'),
            );

        $secondAddress = $customer
            ->addresses()
            ->where('label', 'Office')
            ->firstOrFail();

        $this->assertFalse(
            $firstAddress->fresh()->is_default,
        );

        $this->assertTrue(
            $secondAddress->is_default,
        );
    }

    public function test_deleting_default_address_promotes_another_address(): void
    {
        $customer = User::factory()->create();

        $firstAddress = $customer
            ->addresses()
            ->create([
                ...$this->addressPayload([
                    'label' => 'Home',
                ]),
                'country' => 'PH',
                'is_default' => false,
            ]);

        $defaultAddress = $customer
            ->addresses()
            ->create([
                ...$this->addressPayload([
                    'label' => 'Office',
                ]),
                'country' => 'PH',
                'is_default' => true,
            ]);

        $this
            ->actingAs($customer)
            ->delete(
                route(
                    'account.addresses.destroy',
                    $defaultAddress,
                ),
            )
            ->assertRedirect(
                route('account.addresses.index'),
            );

        $this->assertTrue(
            $firstAddress->fresh()->is_default,
        );
    }

    public function test_customer_cannot_update_another_customers_address(): void
    {
        $customer = User::factory()->create();

        $otherCustomer = User::factory()->create();

        $address = $otherCustomer
            ->addresses()
            ->create([
                ...$this->addressPayload(),
                'country' => 'PH',
                'is_default' => true,
            ]);

        $this
            ->actingAs($customer)
            ->patch(
                route(
                    'account.addresses.update',
                    $address,
                ),
                $this->addressPayload(),
            )
            ->assertForbidden();
    }

    public function test_customer_can_update_profile_phone_number(): void
    {
        $customer = User::factory()->create([
            'phone' => null,
        ]);

        $this
            ->actingAs($customer)
            ->patch(
                route('profile.update'),
                [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => '09171234567',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('profile.edit'),
            );

        $this->assertSame(
            '09171234567',
            $customer->fresh()->phone,
        );
    }

    private function createOrder(
        User $customer,
        OrderStatus $status = OrderStatus::Pending,
    ): Order {
        return Order::query()->create([
            'order_number' => 'TEST-'.fake()
                ->unique()
                ->numerify('######'),

            'user_id' => $customer->id,

            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
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
            'order_status' => $status,

            'customer_notes' => null,
            'admin_notes' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function addressPayload(
        array $overrides = [],
    ): array {
        return [
            'label' => 'Home',
            'recipient_name' => 'Test Customer',
            'phone' => '09171234567',
            'address_line_1' => '123 Test Street',
            'address_line_2' => null,
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'postal_code' => '1000',
            ...$overrides,
        ];
    }
}
