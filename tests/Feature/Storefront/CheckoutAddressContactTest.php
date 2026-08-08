<?php

namespace Tests\Feature\Storefront;

use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckoutAddressContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_authenticated_checkout_saves_complete_default_address(): void
    {
        $this->createStoreSettings();

        $user = User::factory()->create();

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->createCart(
            user: $user,
            product: $product,
        );

        $this
            ->actingAs($user)
            ->post('/checkout', [
                'customer_name' => 'John Checkout',
                'customer_email' => 'delivery@example.com',
                'customer_phone' => '09171234567',

                'shipping_address_id' => null,
                'shipping_address_line_1' => '123 Checkout Street',
                'shipping_address_line_2' => 'Unit 4',
                'shipping_city' => 'Makati',
                'shipping_province' => 'Metro Manila',
                'shipping_postal_code' => '1200',

                'shipping_method' => 'flat_rate',
                'payment_method' => 'cash_on_delivery',
                'customer_notes' => null,
            ])
            ->assertRedirect(route('checkout.success'));

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'recipient_name' => 'John Checkout',
            'email' => 'delivery@example.com',
            'phone' => '09171234567',
            'address_line_1' => '123 Checkout Street',
            'address_line_2' => 'Unit 4',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'postal_code' => '1200',
            'country' => 'PH',
            'is_default' => true,
        ]);
    }

    public function test_default_address_is_exposed_with_complete_contact_information(): void
    {
        $this->createStoreSettings();

        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->createCart(
            user: $user,
            product: $product,
        );

        $address = $user
            ->addresses()
            ->create([
                'label' => 'Home',
                'recipient_name' => 'Default Recipient',
                'email' => 'default@example.com',
                'phone' => '09991234567',
                'address_line_1' => '100 Default Street',
                'address_line_2' => null,
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'postal_code' => '1100',
                'country' => 'PH',
                'is_default' => true,
            ]);

        $this
            ->actingAs($user)
            ->get('/checkout')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/index')
                    ->where(
                        'saved_addresses.0.id',
                        $address->id,
                    )
                    ->where(
                        'saved_addresses.0.recipient_name',
                        'Default Recipient',
                    )
                    ->where(
                        'saved_addresses.0.email',
                        'default@example.com',
                    )
                    ->where(
                        'saved_addresses.0.phone',
                        '09991234567',
                    )
                    ->where(
                        'saved_addresses.0.is_default',
                        true,
                    ),
            );
    }

    public function test_selected_saved_address_is_authoritative_for_order_contact_information(): void
    {
        $this->createStoreSettings();

        $user = User::factory()->create([
            'email' => 'account@example.com',
        ]);

        $product = Product::factory()->create([
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->createCart(
            user: $user,
            product: $product,
        );

        $address = $user
            ->addresses()
            ->create([
                'label' => 'Office',
                'recipient_name' => 'Office Recipient',
                'email' => 'office@example.com',
                'phone' => '09181112222',
                'address_line_1' => '456 Office Avenue',
                'address_line_2' => 'Floor 8',
                'city' => 'Pasig',
                'province' => 'Metro Manila',
                'postal_code' => '1600',
                'country' => 'PH',
                'is_default' => true,
            ]);

        $this
            ->actingAs($user)
            ->post('/checkout', [
                'shipping_address_id' => $address->id,

                // Returning customers do not need to submit editable contact
                // or shipping fields; the selected address is authoritative.
                'shipping_method' => 'flat_rate',
                'payment_method' => 'cash_on_delivery',
                'customer_notes' => null,
            ])
            ->assertRedirect(route('checkout.success'));

        $order = Order::query()->firstOrFail();

        $this->assertSame(
            'Office Recipient',
            $order->customer_name,
        );

        $this->assertSame(
            'office@example.com',
            $order->customer_email,
        );

        $this->assertSame(
            '09181112222',
            $order->customer_phone,
        );

        $this->assertSame(
            '456 Office Avenue',
            $order->shipping_address_line_1,
        );

        $this->assertSame(
            'Floor 8',
            $order->shipping_address_line_2,
        );

        $this->assertSame(
            'Pasig',
            $order->shipping_city,
        );

        $this->assertSame(
            '1600',
            $order->shipping_postal_code,
        );
    }

    private function createCart(
        User $user,
        Product $product,
    ): void {
        $cart = $user->cart()->create();

        $cart
            ->items()
            ->create([
                'product_id' => $product->id,
                'quantity' => 1,
            ]);
    }

    private function createStoreSettings(): void
    {
        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'store_email' => 'hello@example.com',
            'contact_number' => null,
            'business_address' => null,
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 300_000,
            'tax_rate_basis_points' => null,
            'social_links' => [],
        ]);
    }
}
