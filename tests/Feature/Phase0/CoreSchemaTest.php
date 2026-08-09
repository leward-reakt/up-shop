<?php

namespace Tests\Feature\Phase0;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_commerce_relationships_can_be_persisted(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();

        $product = Product::factory()
            ->for($category)
            ->create([
                'price' => 89_950,
                'stock_quantity' => 10,
            ]);

        $image = $product->images()->create([
            'path' => 'products/example.jpg',
            'alt_text' => 'Example product',
            'sort_order' => 0,
        ]);

        $cart = $user->cart()->create();

        $cartItem = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $address = $user->addresses()->create([
            'label' => 'Home',
            'recipient_name' => $user->name,
            'phone' => '09171234567',
            'address_line_1' => '123 Example Street',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'postal_code' => '1200',
            'country' => 'PH',
            'is_default' => true,
        ]);

        $discount = Discount::factory()->create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
        ]);

        $order = $user->orders()->create([
            'order_number' => 'TEST-000001',
            'discount_id' => $discount->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '09171234567',
            'shipping_address_line_1' => '123 Example Street',
            'shipping_city' => 'Makati',
            'shipping_province' => 'Metro Manila',
            'shipping_postal_code' => '1200',
            'shipping_country' => 'PH',
            'shipping_method' => 'flat_rate',
            'discount_code' => 'WELCOME10',
            'subtotal' => 179_900,
            'discount_total' => 17_990,
            'shipping_total' => 15_000,
            'tax_total' => 0,
            'grand_total' => 176_910,
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,
        ]);

        $orderItem = $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 89_950,
            'subtotal' => 179_900,
        ]);

        $payment = $order->payment()->create([
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'amount' => 176_910,
        ]);

        $adjustment = $product->inventoryAdjustments()->create([
            'user_id' => $user->id,
            'quantity_change' => -2,
            'type' => 'order',
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);

        $this->assertTrue($product->category->is($category));
        $this->assertTrue($image->product->is($product));
        $this->assertTrue($cartItem->product->is($product));
        $this->assertTrue($address->user->is($user));
        $this->assertTrue($orderItem->order->is($order));
        $this->assertTrue($payment->order->is($order));
        $this->assertTrue($adjustment->product->is($product));

        $this->assertSame(89_950, $product->price);
        $this->assertSame(89_950, $orderItem->unit_price);

        $this->assertSame(OrderStatus::Pending, $order->order_status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(
            PaymentMethod::BankTransfer,
            $order->payment_method,
        );
    }
}
