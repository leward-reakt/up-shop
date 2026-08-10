<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentDomainExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_domain_contains_paymongo_payment_methods(): void
    {
        $this->assertSame(
            'GCash',
            PaymentMethod::GCash->label(),
        );

        $this->assertSame(
            'Maya',
            PaymentMethod::Maya->label(),
        );

        $this->assertTrue(
            PaymentMethod::GCash->usesPayMongo(),
        );

        $this->assertTrue(
            PaymentMethod::Maya->usesPayMongo(),
        );

        $this->assertFalse(
            PaymentMethod::CashOnDelivery->usesPayMongo(),
        );

        $this->assertFalse(
            PaymentMethod::BankTransfer->usesPayMongo(),
        );
    }

    public function test_payments_table_has_provider_tracking_fields(): void
    {
        $this->assertTrue(
            Schema::hasColumns(
                'payments',
                [
                    'currency',
                    'provider',
                    'provider_checkout_id',
                    'provider_payment_intent_id',
                    'provider_payment_id',
                    'failed_at',
                    'metadata',
                ],
            ),
        );
    }

    public function test_public_checkout_rejects_paymongo_methods_until_checkout_integration_exists(): void
    {
        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        foreach (
            [
                PaymentMethod::GCash,
                PaymentMethod::Maya,
            ] as $paymentMethod
        ) {
            $response = $this
                ->withSession([
                    'cart.items' => [
                        $product->id => 1,
                    ],
                ])
                ->post(
                    '/checkout',
                    [
                        'customer_name' => 'Test Customer',
                        'customer_email' => 'customer@example.com',
                        'customer_phone' => '09171234567',
                        'shipping_address_line_1' => '100 Test Street',
                        'shipping_address_line_2' => null,
                        'shipping_city' => 'Makati',
                        'shipping_province' => 'Metro Manila',
                        'shipping_postal_code' => '1200',
                        'shipping_method' => ShippingMethod::FlatRate->value,
                        'payment_method' => $paymentMethod->value,
                        'customer_notes' => null,
                    ],
                );

            $response->assertSessionHasErrors(
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

            $this->assertSame(
                5,
                $product->fresh()->stock_quantity,
            );
        }
    }
}
