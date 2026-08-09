<?php

namespace Tests\Feature\Account;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BankTransferOrderDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_bank_transfer_order_details_include_configured_instructions(): void
    {
        $instructions = $this->configureBankTransfer();

        $customer = User::factory()->create();

        $order = $this->createOrder(
            customer: $customer,
            paymentMethod: PaymentMethod::BankTransfer,
            paymentStatus: PaymentStatus::Pending,
        );

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
                    ->where(
                        'order.id',
                        $order->id,
                    )
                    ->where(
                        'order.payment_method',
                        PaymentMethod::BankTransfer->value,
                    )
                    ->where(
                        'order.payment_status',
                        PaymentStatus::Pending->value,
                    )
                    ->where(
                        'bank_transfer_instructions',
                        $instructions,
                    ),
            );
    }

    public function test_bank_transfer_order_details_hide_instructions_after_payment_is_no_longer_pending(): void
    {
        $this->configureBankTransfer();

        $customer = User::factory()->create();

        $order = $this->createOrder(
            customer: $customer,
            paymentMethod: PaymentMethod::BankTransfer,
            paymentStatus: PaymentStatus::Paid,
        );

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
                    ->where(
                        'order.payment_status',
                        PaymentStatus::Paid->value,
                    )
                    ->where(
                        'bank_transfer_instructions',
                        null,
                    ),
            );
    }

    public function test_pending_cash_on_delivery_order_details_do_not_include_bank_transfer_instructions(): void
    {
        $this->configureBankTransfer();

        $customer = User::factory()->create();

        $order = $this->createOrder(
            customer: $customer,
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Pending,
        );

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
                    ->where(
                        'order.payment_method',
                        PaymentMethod::CashOnDelivery->value,
                    )
                    ->where(
                        'bank_transfer_instructions',
                        null,
                    ),
            );
    }

    private function configureBankTransfer(): string
    {
        $instructions = <<<'TEXT'
Bank: BDO
Account Name: Up Shop Trading
Account Number: 1234-5678-9012
TEXT;

        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'store_email' => 'hello@example.com',
            'contact_number' => null,
            'business_address' => null,
            'bank_transfer_instructions' => $instructions,
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 300_000,
            'tax_rate_basis_points' => null,
            'social_links' => [],
        ]);

        return $instructions;
    }

    private function createOrder(
        User $customer,
        PaymentMethod $paymentMethod,
        PaymentStatus $paymentStatus,
    ): Order {
        $order = Order::query()->create([
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

            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => OrderStatus::Pending,

            'customer_notes' => null,
            'admin_notes' => null,
        ]);

        $isPaid = $paymentStatus === PaymentStatus::Paid;

        $order->payment()->create([
            'method' => $paymentMethod,
            'status' => $paymentStatus,
            'amount' => $order->grand_total,
            'reference' => $isPaid
                ? 'BANK-123'
                : null,
            'paid_at' => $isPaid
                ? now()
                : null,
        ]);

        return $order;
    }
}
