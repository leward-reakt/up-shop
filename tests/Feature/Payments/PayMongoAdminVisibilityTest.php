<?php

namespace Tests\Feature\Payments;

use App\Actions\Payments\UpdatePaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayMongoAdminVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config()->set(
            'services.paymongo.admin_enabled',
            true,
        );
        config()->set(
            'services.paymongo.secret_key',
            'sk_test_admin_secret',
        );
        config()->set(
            'services.paymongo.webhook_secret',
            'whsk_test_admin_secret',
        );
        config()->set(
            'services.paymongo.available',
            true,
        );

        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => null,
            'tax_rate_basis_points' => null,
            'paymongo_enabled' => true,
        ]);

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        Filament::setCurrentPanel(
            Filament::getPanel('admin'),
        );
    }

    public function test_order_view_exposes_read_only_paymongo_operational_references(): void
    {
        $payment = $this->placePayment(
            PaymentMethod::GCash,
        );

        $payment->update([
            'status' => PaymentStatus::Refunded,
            'provider_payment_intent_id' => 'pi_admin_test',
            'provider_payment_id' => 'pay_admin_test',
            'reference' => 'pay_admin_test',
            'paid_at' => now()->subHour(),
            'refund_reference' => 'refund_admin_test',
            'refunded_at' => now(),
        ]);

        $order = $payment->order()->firstOrFail();

        $order->update([
            'payment_status' => PaymentStatus::Refunded,
        ]);

        $this
            ->get(
                OrderResource::getUrl(
                    'view',
                    [
                        'record' => $order,
                    ],
                ),
            )
            ->assertOk()
            ->assertSeeText('Payment amount')
            ->assertSeeText('PayMongo Checkout Reference')
            ->assertSeeText('cs_admin_test')
            ->assertSeeText('PayMongo Payment Reference')
            ->assertSeeText('pay_admin_test')
            ->assertSeeText('Refund Reference')
            ->assertSeeText('refund_admin_test')
            ->assertDontSee(
                'sk_test_admin_secret',
                false,
            )
            ->assertDontSee(
                'whsk_test_admin_secret',
                false,
            );
    }

    public function test_paymongo_payment_cannot_use_manual_admin_payment_update_path(): void
    {
        $payment = $this->placePayment(
            PaymentMethod::GCash,
        );

        $this->assertFalse(
            PaymentResource::canEdit($payment),
        );

        try {
            app(UpdatePaymentStatus::class)->handle(
                payment: $payment,
                status: PaymentStatus::Paid,
            );

            $this->fail(
                'Expected manual PayMongo payment mutation to be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'status',
                $exception->errors(),
            );
        }

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
        );
    }

    public function test_manual_payment_management_remains_unchanged(): void
    {
        $payment = $this->placePayment(
            PaymentMethod::CashOnDelivery,
        );

        $this->assertTrue(
            PaymentResource::canEdit($payment),
        );

        $updatedPayment = app(
            UpdatePaymentStatus::class,
        )->handle(
            payment: $payment,
            status: PaymentStatus::Paid,
        );

        $this->assertSame(
            PaymentStatus::Paid,
            $updatedPayment->status,
        );
        $this->assertSame(
            PaymentStatus::Paid,
            $updatedPayment->order->payment_status,
        );
    }

    private function placePayment(
        PaymentMethod $paymentMethod,
    ): Payment {
        if ($paymentMethod->usesPayMongo()) {
            Http::fake([
                'https://api.paymongo.com/v2/checkout_sessions' => Http::response(
                    [
                        'data' => [
                            'id' => 'cs_admin_test',
                            'type' => 'checkout_session',
                            'attributes' => [
                                'checkout_url' => 'https://checkout.paymongo.com/cs_admin_test',
                            ],
                        ],
                    ],
                    200,
                ),
            ]);
        } else {
            Http::fake();
        }

        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $request = $this->withSession([
            'cart.items' => [
                $product->id => 1,
            ],
        ]);

        if ($paymentMethod->usesPayMongo()) {
            $request = $request->withHeader(
                'X-Inertia',
                'true',
            );
        }

        $response = $request->post(
            route('checkout.store'),
            $this->checkoutPayload($paymentMethod),
        );

        if ($paymentMethod->usesPayMongo()) {
            $response->assertStatus(409);
        } else {
            $response->assertRedirect(
                route('checkout.success'),
            );
        }

        $payment = Order::query()
            ->with('payment')
            ->sole()
            ->payment;

        $this->assertInstanceOf(
            Payment::class,
            $payment,
        );

        return $payment;
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
