<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PayMongoConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_paymongo_secrets_are_not_exposed_to_storefront_responses(): void
    {
        $secretKey = 'paymongo-test-secret-not-for-client';
        $webhookSecret = 'paymongo-test-webhook-secret-not-for-client';

        config()->set(
            'services.paymongo.secret_key',
            $secretKey,
        );

        config()->set(
            'services.paymongo.webhook_secret',
            $webhookSecret,
        );

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(
                $secretKey,
                false,
            )
            ->assertDontSee(
                $webhookSecret,
                false,
            );
    }

    public function test_paymongo_configuration_does_not_change_checkout_payment_options(): void
    {
        config()->set(
            'services.paymongo.admin_enabled',
            true,
        );

        config()->set(
            'services.paymongo.secret_key',
            'paymongo-test-secret',
        );

        config()->set(
            'services.paymongo.webhook_secret',
            'paymongo-test-webhook-secret',
        );

        config()->set(
            'services.paymongo.available',
            true,
        );

        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 0,
            'bank_transfer_instructions' => implode("\n", [
                'Bank: Test Bank',
                'Account Name: Up Shop',
                'Account Number: 1234567890',
            ]),
        ]);

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
                    ->where(
                        'payment_options',
                        [
                            [
                                'value' => PaymentMethod::CashOnDelivery->value,
                                'label' => PaymentMethod::CashOnDelivery->label(),
                            ],
                            [
                                'value' => PaymentMethod::BankTransfer->value,
                                'label' => PaymentMethod::BankTransfer->label(),
                            ],
                        ],
                    ),
            );
    }
}
