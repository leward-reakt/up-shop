<?php

namespace Tests\Feature\Notifications;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\UpdateOrderStatus;
use App\Actions\Payments\UpdatePaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusChangedNotification;
use App\Notifications\PaymentConfirmedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Number;
use Tests\TestCase;

class TransactionalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactional_notifications_remain_synchronous(): void
    {
        $notificationClasses = [
            OrderPlacedNotification::class,
            PaymentConfirmedNotification::class,
            OrderStatusChangedNotification::class,
        ];

        foreach ($notificationClasses as $notificationClass) {
            $this->assertFalse(
                is_subclass_of(
                    $notificationClass,
                    ShouldQueue::class,
                ),
                "{$notificationClass} must remain synchronous for the MVP.",
            );
        }
    }

    public function test_placing_order_sends_order_confirmation(): void
    {
        Notification::fake();

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        app(PlaceOrder::class)->handle(
            user: null,
            cartQuantities: [
                $product->id => 1,
            ],
            data: [
                'customer_name' => 'Test Customer',
                'customer_email' => 'customer@example.com',
                'customer_phone' => '09171234567',
                'shipping_address_line_1' => '123 Test Street',
                'shipping_address_line_2' => null,
                'shipping_city' => 'Manila',
                'shipping_province' => 'Metro Manila',
                'shipping_postal_code' => '1000',
                'shipping_method' => ShippingMethod::FlatRate->value,
                'payment_method' => PaymentMethod::CashOnDelivery->value,
                'customer_notes' => null,
            ],
            discountCode: null,
        );

        Notification::assertSentOnDemand(
            OrderPlacedNotification::class,
        );
    }

    public function test_order_confirmation_uses_configured_store_currency(): void
    {
        StoreSetting::query()->create([
            'store_name' => 'USD Test Store',
            'currency' => 'USD',
            'default_shipping_fee' => 0,
        ]);

        $order = $this->createOrder();

        $message = (new OrderPlacedNotification(
            $order,
        ))->toMail($order);

        $this->assertContains(
            'Order total: '.Number::currency(
                $order->grand_total / 100,
                in: 'USD',
            ),
            $message->introLines,
        );
    }

    public function test_marking_payment_paid_sends_confirmation_once(): void
    {
        Notification::fake();

        $order = $this->createOrder();

        $payment = $order->payment()->create([
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'reference' => null,
            'paid_at' => null,
        ]);

        app(UpdatePaymentStatus::class)->handle(
            payment: $payment,
            status: PaymentStatus::Paid,
            reference: 'BANK-123',
            notes: 'Verified manually.',
        );

        app(UpdatePaymentStatus::class)->handle(
            payment: $payment->fresh(),
            status: PaymentStatus::Paid,
            reference: 'BANK-123',
            notes: 'Verified manually.',
        );

        Notification::assertSentOnDemandTimes(
            PaymentConfirmedNotification::class,
            1,
        );
    }

    public function test_payment_confirmation_uses_configured_store_currency(): void
    {
        StoreSetting::query()->create([
            'store_name' => 'USD Test Store',
            'currency' => 'USD',
            'default_shipping_fee' => 0,
        ]);

        $order = $this->createOrder();

        $payment = $order->payment()->create([
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Paid,
            'amount' => $order->grand_total,
            'reference' => 'BANK-123',
            'paid_at' => now(),
        ]);

        $message = (new PaymentConfirmedNotification(
            $payment,
        ))->toMail($payment);

        $this->assertContains(
            'Amount received: '.Number::currency(
                $payment->amount / 100,
                in: 'USD',
            ),
            $message->introLines,
        );
    }

    public function test_processing_order_sends_status_notification(): void
    {
        Notification::fake();

        $order = $this->createOrder([
            'order_status' => OrderStatus::Confirmed,
        ]);

        app(UpdateOrderStatus::class)->handle(
            order: $order,
            status: OrderStatus::Processing,
        );

        Notification::assertSentOnDemand(
            OrderStatusChangedNotification::class,
        );
    }

    public function test_shipped_order_sends_status_notification(): void
    {
        Notification::fake();

        $order = $this->createOrder([
            'order_status' => OrderStatus::Processing,
            'shipping_method' => ShippingMethod::FlatRate,
        ]);

        app(UpdateOrderStatus::class)->handle(
            order: $order,
            status: OrderStatus::Shipped,
        );

        Notification::assertSentOnDemand(
            OrderStatusChangedNotification::class,
        );
    }

    public function test_completed_order_sends_status_notification(): void
    {
        Notification::fake();

        $order = $this->createOrder([
            'order_status' => OrderStatus::Shipped,
        ]);

        app(UpdateOrderStatus::class)->handle(
            order: $order,
            status: OrderStatus::Completed,
        );

        Notification::assertSentOnDemand(
            OrderStatusChangedNotification::class,
        );
    }

    public function test_order_cancellation_sends_status_notification(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $order = $this->createOrder();

        app(CancelOrder::class)->handle(
            order: $order,
            user: $admin,
        );

        Notification::assertSentOnDemand(
            OrderStatusChangedNotification::class,
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
