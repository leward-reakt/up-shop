<?php

namespace Tests\Feature\Storefront;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BankTransferCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_transfer_is_hidden_when_instructions_are_not_configured(): void
    {
        $this->createStoreSettings();

        $product = $this->createProduct();

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->get('/checkout')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/index')
                    ->has('payment_options', 1)
                    ->where(
                        'payment_options.0.value',
                        PaymentMethod::CashOnDelivery->value,
                    )
                    ->where(
                        'bank_transfer_instructions',
                        null,
                    ),
            );
    }

    public function test_whitespace_only_instructions_do_not_enable_bank_transfer(): void
    {
        $this->createStoreSettings([
            'bank_transfer_instructions' => '   ',
        ]);

        $product = $this->createProduct();

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->get('/checkout')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->has('payment_options', 1)
                    ->where(
                        'payment_options.0.value',
                        PaymentMethod::CashOnDelivery->value,
                    )
                    ->where(
                        'bank_transfer_instructions',
                        null,
                    ),
            );
    }

    public function test_configured_bank_transfer_is_available_with_instructions(): void
    {
        $instructions = $this->bankTransferInstructions();

        $this->createStoreSettings([
            'bank_transfer_instructions' => $instructions,
        ]);

        $product = $this->createProduct();

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->get('/checkout')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/index')
                    ->has('payment_options', 2)
                    ->where(
                        'payment_options.1.value',
                        PaymentMethod::BankTransfer->value,
                    )
                    ->where(
                        'bank_transfer_instructions',
                        $instructions,
                    ),
            );
    }

    public function test_bank_transfer_submission_fails_closed_without_instructions(): void
    {
        Notification::fake();

        $this->createStoreSettings();

        $product = $this->createProduct();

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload([
                    'payment_method' => PaymentMethod::BankTransfer->value,
                ]),
            )
            ->assertSessionHasErrors(
                'payment_method',
            );

        $this->assertDatabaseCount(
            'orders',
            0,
        );

        $this->assertDatabaseCount(
            'payments',
            0,
        );

        $this->assertDatabaseCount(
            'inventory_adjustments',
            0,
        );

        $this->assertSame(
            5,
            $product->fresh()->stock_quantity,
        );
    }

    public function test_configured_bank_transfer_order_is_created_pending_and_success_page_shows_instructions(): void
    {
        Notification::fake();

        $instructions = $this->bankTransferInstructions();

        $this->createStoreSettings([
            'bank_transfer_instructions' => $instructions,
        ]);

        $product = $this->createProduct();

        $this
            ->withSession(
                $this->guestCartSession($product),
            )
            ->post(
                '/checkout',
                $this->checkoutPayload([
                    'payment_method' => PaymentMethod::BankTransfer->value,
                ]),
            )
            ->assertRedirect(
                route('checkout.success'),
            );

        $order = Order::query()
            ->with('payment')
            ->firstOrFail();

        $this->assertSame(
            PaymentMethod::BankTransfer,
            $order->payment_method,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status,
        );

        $this->assertSame(
            OrderStatus::Pending,
            $order->order_status,
        );

        $payment = $order->payment;

        $this->assertNotNull($payment);

        $this->assertSame(
            PaymentMethod::BankTransfer,
            $payment->method,
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->status,
        );

        $this
            ->get(route('checkout.success'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/success')
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

    public function test_bank_transfer_order_confirmation_contains_configured_instructions(): void
    {
        $instructions = $this->bankTransferInstructions();

        $this->createStoreSettings([
            'bank_transfer_instructions' => $instructions,
        ]);

        $order = $this->createOrder(
            PaymentMethod::BankTransfer,
        );

        $message = (new OrderPlacedNotification(
            $order,
        ))->toMail($order);

        $mailText = $this->normalizeWhitespace(
            implode(' ', $message->introLines),
        );

        $this->assertStringContainsString(
            'Bank transfer instructions:',
            $mailText,
        );

        $this->assertStringContainsString(
            $this->normalizeWhitespace($instructions),
            $mailText,
        );

        $this->assertStringContainsString(
            'Your payment will remain pending until '
            .'the transfer is manually verified.',
            $mailText,
        );
    }

    public function test_cash_on_delivery_confirmation_does_not_include_bank_transfer_instructions(): void
    {
        $instructions = $this->bankTransferInstructions();

        $this->createStoreSettings([
            'bank_transfer_instructions' => $instructions,
        ]);

        $order = $this->createOrder(
            PaymentMethod::CashOnDelivery,
        );

        $message = (new OrderPlacedNotification(
            $order,
        ))->toMail($order);

        $mailText = $this->normalizeWhitespace(
            implode(' ', $message->introLines),
        );

        $this->assertStringNotContainsString(
            'Bank transfer instructions:',
            $mailText,
        );

        $this->assertStringNotContainsString(
            $this->normalizeWhitespace($instructions),
            $mailText,
        );
    }

    private function createProduct(): Product
    {
        return Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
    }

    private function bankTransferInstructions(): string
    {
        return <<<'TEXT'
Bank: BDO
Account Name: Up Shop Trading
Account Number: 1234-5678-9012

Please complete the transfer before sending proof of payment.
TEXT;
    }

    private function normalizeWhitespace(string $value): string
    {
        $normalized = preg_replace(
            '/\s+/',
            ' ',
            trim($value),
        );

        return $normalized ?? '';
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

            'shipping_address_id' => null,
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

    /**
     * @return array<string, mixed>
     */
    private function guestCartSession(
        Product $product,
    ): array {
        return [
            'cart' => [
                'items' => [
                    $product->id => 1,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createStoreSettings(
        array $overrides = [],
    ): StoreSetting {
        return StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'store_email' => 'hello@example.com',
            'contact_number' => null,
            'business_address' => null,
            'bank_transfer_instructions' => null,
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 300_000,
            'tax_rate_basis_points' => null,
            'social_links' => [],
            ...$overrides,
        ]);
    }

    private function createOrder(
        PaymentMethod $paymentMethod,
    ): Order {
        return Order::query()->create([
            'order_number' => 'TEST-123456',

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

            'payment_method' => $paymentMethod,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,

            'customer_notes' => null,
            'admin_notes' => null,
        ]);
    }
}
