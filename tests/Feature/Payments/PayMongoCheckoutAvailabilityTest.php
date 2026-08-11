<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PayMongoCheckoutAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_exposes_gcash_and_maya_when_paymongo_is_available(): void
    {
        $this->enablePayMongoConfiguration();

        $this->createStoreSettings(
            payMongoEnabled: true,
        );

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
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/index')
                    ->has('payment_options', 4)
                    ->where(
                        'payment_options.0.value',
                        PaymentMethod::CashOnDelivery->value,
                    )
                    ->where(
                        'payment_options.1.value',
                        PaymentMethod::BankTransfer->value,
                    )
                    ->where(
                        'payment_options.2.value',
                        PaymentMethod::GCash->value,
                    )
                    ->where(
                        'payment_options.3.value',
                        PaymentMethod::Maya->value,
                    ),
            );
    }

    public function test_checkout_hides_paymongo_when_store_toggle_is_disabled(): void
    {
        $this->enablePayMongoConfiguration();

        $this->createStoreSettings(
            payMongoEnabled: false,
        );

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
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('checkout/index')
                    ->has('payment_options', 2)
                    ->where(
                        'payment_options.0.value',
                        PaymentMethod::CashOnDelivery->value,
                    )
                    ->where(
                        'payment_options.1.value',
                        PaymentMethod::BankTransfer->value,
                    ),
            );
    }

    public function test_forged_paymongo_checkout_is_rejected_when_unavailable(): void
    {
        config()->set(
            'services.paymongo.available',
            false,
        );

        $this->createStoreSettings(
            payMongoEnabled: true,
        );

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        foreach (
            [
                PaymentMethod::GCash,
                PaymentMethod::Maya,
            ] as $method
        ) {
            $this
                ->withSession([
                    'cart.items' => [
                        $product->id => 1,
                    ],
                ])
                ->post(
                    route('checkout.store'),
                    $this->checkoutPayload($method),
                )
                ->assertSessionHasErrors(
                    'payment_method',
                );
        }

        $this->assertDatabaseCount(
            'orders',
            0,
        );

        $this->assertDatabaseCount(
            'payments',
            0,
        );

        $this->assertSame(
            5,
            $product->fresh()->stock_quantity,
        );
    }

    private function enablePayMongoConfiguration(): void
    {
        config()->set(
            'services.paymongo.admin_enabled',
            true,
        );

        config()->set(
            'services.paymongo.secret_key',
            'sk_test_example',
        );

        config()->set(
            'services.paymongo.webhook_secret',
            'whsk_test_example',
        );

        config()->set(
            'services.paymongo.available',
            true,
        );
    }

    private function createStoreSettings(
        bool $payMongoEnabled,
    ): void {
        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => null,
            'tax_rate_basis_points' => null,
            'bank_transfer_instructions' => 'Transfer to test bank account.',
            'paymongo_enabled' => $payMongoEnabled,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(
        PaymentMethod $paymentMethod,
    ): array {
        return [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '09171234567',

            'shipping_address_line_1' => '123 Test Street',
            'shipping_address_line_2' => null,
            'shipping_city' => 'Manila',
            'shipping_province' => 'Metro Manila',
            'shipping_postal_code' => '1000',

            'shipping_method' => ShippingMethod::FlatRate->value,
            'payment_method' => $paymentMethod->value,

            'customer_notes' => null,
        ];
    }
}
