<?php

namespace Tests\Feature\Admin;

use App\Actions\Orders\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderPaymentAwareStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_bank_transfer_must_be_paid_before_entering_processing(): void
    {
        $order = $this->createOrder([
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Confirmed,
        ]);

        $this->assertSame(
            [],
            UpdateOrderStatus::allowedNextStatuses($order),
        );

        try {
            app(UpdateOrderStatus::class)->handle(
                order: $order,
                status: OrderStatus::Processing,
            );

            $this->fail(
                'Expected an unpaid bank transfer to be blocked from processing.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'order_status',
                $exception->errors(),
            );
        }

        $this->assertSame(
            OrderStatus::Confirmed,
            $order->fresh()->order_status,
        );

        $order->update([
            'payment_status' => PaymentStatus::Paid,
        ]);

        $order->refresh();

        $this->assertSame(
            [OrderStatus::Processing],
            UpdateOrderStatus::allowedNextStatuses($order),
        );

        app(UpdateOrderStatus::class)->handle(
            order: $order,
            status: OrderStatus::Processing,
        );

        $this->assertSame(
            OrderStatus::Processing,
            $order->fresh()->order_status,
        );
    }

    public function test_cash_on_delivery_can_enter_processing_while_payment_is_pending(): void
    {
        $order = $this->createOrder([
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Confirmed,
        ]);

        $this->assertSame(
            [OrderStatus::Processing],
            UpdateOrderStatus::allowedNextStatuses($order),
        );

        app(UpdateOrderStatus::class)->handle(
            order: $order,
            status: OrderStatus::Processing,
        );

        $this->assertSame(
            OrderStatus::Processing,
            $order->fresh()->order_status,
        );
    }

    public function test_unpaid_order_cannot_be_completed(): void
    {
        foreach (
            [
                OrderStatus::ReadyForPickup,
                OrderStatus::Shipped,
            ] as $currentStatus
        ) {
            $order = $this->createOrder([
                'payment_method' => PaymentMethod::CashOnDelivery,
                'payment_status' => PaymentStatus::Pending,
                'order_status' => $currentStatus,
            ]);

            $this->assertSame(
                [],
                UpdateOrderStatus::allowedNextStatuses($order),
            );

            try {
                app(UpdateOrderStatus::class)->handle(
                    order: $order,
                    status: OrderStatus::Completed,
                );

                $this->fail(
                    "Expected unpaid {$currentStatus->value} order to be blocked from completion.",
                );
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey(
                    'order_status',
                    $exception->errors(),
                );
            }

            $this->assertSame(
                $currentStatus,
                $order->fresh()->order_status,
            );

            $order->update([
                'payment_status' => PaymentStatus::Paid,
            ]);

            $order->refresh();

            $this->assertSame(
                [OrderStatus::Completed],
                UpdateOrderStatus::allowedNextStatuses($order),
            );

            app(UpdateOrderStatus::class)->handle(
                order: $order,
                status: OrderStatus::Completed,
            );

            $this->assertSame(
                OrderStatus::Completed,
                $order->fresh()->order_status,
            );
        }
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
