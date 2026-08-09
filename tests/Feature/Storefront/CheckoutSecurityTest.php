<?php

namespace Tests\Feature\Storefront;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_success_requires_session_order_reference(): void
    {
        $this
            ->get(route('checkout.success'))
            ->assertNotFound();
    }

    public function test_guest_cannot_view_registered_order_confirmation_with_forged_session_reference(): void
    {
        $owner = User::factory()->create();

        $order = $this->createOrder($owner);

        $this
            ->withSession([
                'checkout.order_id' => $order->id,
            ])
            ->get(route('checkout.success'))
            ->assertForbidden();
    }

    public function test_customer_cannot_view_another_customers_checkout_confirmation(): void
    {
        $owner = User::factory()->create();

        $otherCustomer = User::factory()->create();

        $order = $this->createOrder($owner);

        $this
            ->actingAs($otherCustomer)
            ->withSession([
                'checkout.order_id' => $order->id,
            ])
            ->get(route('checkout.success'))
            ->assertForbidden();
    }

    public function test_checkout_page_redirects_to_cart_when_requested_quantity_exceeds_current_stock(): void
    {
        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 1,
            'is_active' => true,
        ]);

        $this
            ->withSession([
                'cart.items' => [
                    $product->id => 2,
                ],
            ])
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors([
                'cart' => "{$product->name} only has 1 unit(s) available. Update the quantity before checkout.",
            ]);
    }

    public function test_failed_guest_checkout_keeps_cart_and_does_not_create_purchase_records(): void
    {
        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 1,
            'is_active' => true,
        ]);

        $this
            ->withSession([
                'cart.items' => [
                    $product->id => 2,
                ],
            ])
            ->from('/checkout')
            ->post(
                route('checkout.store'),
                $this->checkoutPayload(),
            )
            ->assertRedirect('/checkout')
            ->assertSessionHasErrors('cart')
            ->assertSessionHas(
                "cart.items.{$product->id}",
                2,
            );

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_adjustments', 0);

        $this->assertSame(
            1,
            $product->fresh()->stock_quantity,
        );
    }

    public function test_bank_transfer_checkout_creates_pending_bank_transfer_payment(): void
    {
        Notification::fake();

        $this->configureBankTransfer();

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this
            ->withSession([
                'cart.items' => [
                    $product->id => 1,
                ],
            ])
            ->post(
                route('checkout.store'),
                $this->checkoutPayload([
                    'payment_method' => PaymentMethod::BankTransfer->value,
                ]),
            )
            ->assertRedirect(route('checkout.success'));

        $order = Order::query()
            ->with('payment')
            ->sole();

        $this->assertSame(
            PaymentMethod::BankTransfer,
            $order->payment_method,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );

        $this->assertNotNull($order->payment);

        $this->assertSame(
            PaymentMethod::BankTransfer,
            $order->payment->method,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment->status,
        );
    }

    private function configureBankTransfer(): void
    {
        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'store_email' => 'hello@example.com',
            'contact_number' => null,
            'business_address' => null,
            'bank_transfer_instructions' => <<<'TEXT'
Bank: BDO
Account Name: Up Shop Trading
Account Number: 1234-5678-9012
TEXT,
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 300_000,
            'tax_rate_basis_points' => null,
            'social_links' => [],
        ]);
    }

    private function createOrder(User $customer): Order
    {
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

            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,

            'customer_notes' => null,
            'admin_notes' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function checkoutPayload(
        array $overrides = [],
    ): array {
        return [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '09171234567',

            'shipping_address_line_1' => '123 Test Street',
            'shipping_address_line_2' => null,
            'shipping_city' => 'Makati',
            'shipping_province' => 'Metro Manila',
            'shipping_postal_code' => '1200',

            'shipping_method' => ShippingMethod::FlatRate->value,
            'payment_method' => PaymentMethod::CashOnDelivery->value,

            'customer_notes' => null,

            ...$overrides,
        ];
    }
}
