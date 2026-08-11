<?php

namespace Tests\Feature\Payments;

use App\Actions\Payments\RefundPayMongoPayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Notifications\PaymentRefundedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayMongoRefundTest extends TestCase
{
    use RefreshDatabase;

    private const CHECKOUT_URL =
        'https://api.paymongo.com/v2/checkout_sessions';

    private const REFUNDS_URL =
        'https://api.paymongo.com/refunds';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Notification::fake();

        config()->set(
            'services.paymongo.admin_enabled',
            true,
        );
        config()->set(
            'services.paymongo.secret_key',
            'sk_test_refund',
        );
        config()->set(
            'services.paymongo.webhook_secret',
            'whsk_test_refund',
        );
        config()->set(
            'services.paymongo.available',
            true,
        );

        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 0,
            'free_shipping_threshold' => null,
            'tax_rate_basis_points' => null,
            'paymongo_enabled' => true,
        ]);
    }

    public function test_paid_paymongo_payment_is_eligible_for_full_refund(): void
    {
        $this->fakeCheckout();

        $payment = $this->placePaidPayMongoPayment();

        $this->assertTrue(
            app(
                RefundPayMongoPayment::class,
            )->isEligible($payment),
        );
    }

    public function test_store_checkout_toggle_does_not_block_existing_payment_refund_eligibility(): void
    {
        $this->fakeCheckout();

        $payment = $this->placePaidPayMongoPayment();

        StoreSetting::query()->update([
            'paymongo_enabled' => false,
        ]);

        $this->assertTrue(
            app(
                RefundPayMongoPayment::class,
            )->isEligible(
                $payment->fresh(),
            ),
        );
    }

    public function test_pending_paymongo_payment_is_not_eligible_for_refund(): void
    {
        $this->fakeCheckout();

        $payment = $this->placePaidPayMongoPayment();

        $payment->update([
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);

        $payment->order()->update([
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->assertFalse(
            app(
                RefundPayMongoPayment::class,
            )->isEligible(
                $payment->fresh(),
            ),
        );
    }

    public function test_successful_full_refund_updates_payment_and_order_once(): void
    {
        $this->fakeCheckoutAndRefund(
            status: 'succeeded',
        );

        $payment = $this->placePaidPayMongoPayment();

        $updatedPayment = app(
            RefundPayMongoPayment::class,
        )->handle($payment);

        $updatedPayment->refresh();

        $order = $updatedPayment
            ->order()
            ->firstOrFail();

        $this->assertSame(
            PaymentStatus::Refunded,
            $updatedPayment->status,
        );
        $this->assertSame(
            PaymentStatus::Refunded,
            $order->payment_status,
        );
        $this->assertSame(
            'ref_refund_test',
            $updatedPayment->refund_reference,
        );
        $this->assertNotNull(
            $updatedPayment->refunded_at,
        );
        $this->assertSame(
            'succeeded',
            data_get(
                $updatedPayment->metadata,
                'paymongo_refund.status',
            ),
        );

        Http::assertSent(
            function (Request $request) use (
                $payment,
            ): bool {
                return $request->method() === 'POST'
                    && $request->url() === self::REFUNDS_URL
                    && $request['data']['attributes']['payment_id']
                        === $payment->provider_payment_id
                    && $request['data']['attributes']['amount']
                        === $payment->amount;
            },
        );

        Notification::assertSentOnDemandTimes(
            PaymentRefundedNotification::class,
            1,
        );
    }

    public function test_processing_refund_keeps_local_payment_paid(): void
    {
        $this->fakeCheckoutAndRefund(
            status: 'processing',
        );

        $payment = $this->placePaidPayMongoPayment();

        $updatedPayment = app(
            RefundPayMongoPayment::class,
        )->handle($payment);

        $this->assertSame(
            PaymentStatus::Paid,
            $updatedPayment->status,
        );
        $this->assertSame(
            'ref_refund_test',
            $updatedPayment->refund_reference,
        );
        $this->assertNull(
            $updatedPayment->refunded_at,
        );
        $this->assertSame(
            PaymentStatus::Paid,
            $updatedPayment
                ->order()
                ->firstOrFail()
                ->payment_status,
        );

        $this->assertFalse(
            app(
                RefundPayMongoPayment::class,
            )->isEligible(
                $updatedPayment->fresh(),
            ),
        );

        Notification::assertSentOnDemandTimes(
            PaymentRefundedNotification::class,
            0,
        );
    }

    public function test_failed_refund_remains_paid_and_can_be_retried(): void
    {
        $this->fakeCheckoutAndRefund(
            status: 'failed',
        );

        $payment = $this->placePaidPayMongoPayment();

        try {
            app(
                RefundPayMongoPayment::class,
            )->handle($payment);

            $this->fail(
                'Expected failed PayMongo refund to be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'refund',
                $exception->errors(),
            );
        }

        $payment->refresh();

        $this->assertSame(
            PaymentStatus::Paid,
            $payment->status,
        );
        $this->assertNull(
            $payment->refund_reference,
        );
        $this->assertNull(
            $payment->refunded_at,
        );
        $this->assertSame(
            'failed',
            data_get(
                $payment->metadata,
                'paymongo_refund.status',
            ),
        );
        $this->assertSame(
            PaymentStatus::Paid,
            $payment
                ->order()
                ->firstOrFail()
                ->payment_status,
        );

        $this->assertTrue(
            app(
                RefundPayMongoPayment::class,
            )->isEligible($payment),
        );

        Notification::assertSentOnDemandTimes(
            PaymentRefundedNotification::class,
            0,
        );
    }

    private function fakeCheckout(): void
    {
        Http::fake([
            self::CHECKOUT_URL => Http::response(
                $this->checkoutSessionResponse(),
                200,
            ),
        ]);
    }

    private function fakeCheckoutAndRefund(
        string $status,
    ): void {
        Http::fake([
            self::CHECKOUT_URL => Http::response(
                $this->checkoutSessionResponse(),
                200,
            ),

            self::REFUNDS_URL => Http::response(
                $this->refundResponse(
                    status: $status,
                ),
                200,
            ),
        ]);
    }

    private function placePaidPayMongoPayment(): Payment
    {
        $product = Product::factory()->create([
            'price' => 100_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this
            ->withHeader(
                'X-Inertia',
                'true',
            )
            ->withSession([
                'cart.items' => [
                    $product->id => 1,
                ],
            ])
            ->post(
                route('checkout.store'),
                $this->checkoutPayload(),
            )
            ->assertStatus(409);

        $order = Order::query()
            ->with('payment')
            ->sole();

        $payment = $order->payment;

        $this->assertInstanceOf(
            Payment::class,
            $payment,
        );

        $payment->update([
            'status' => PaymentStatus::Paid,
            'provider_payment_intent_id' => 'pi_refund_test',
            'provider_payment_id' => 'pay_refund_test',
            'reference' => 'pay_refund_test',
            'paid_at' => now(),
        ]);

        $order->update([
            'payment_status' => PaymentStatus::Paid,
        ]);

        return $payment
            ->refresh()
            ->load('order');
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(): array
    {
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
            'payment_method' => PaymentMethod::GCash->value,
            'customer_notes' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutSessionResponse(): array
    {
        return [
            'data' => [
                'id' => 'cs_refund_test',
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/cs_refund_test',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function refundResponse(
        string $status,
    ): array {
        return [
            'data' => [
                'id' => 'ref_refund_test',
                'type' => 'refund',
                'attributes' => [
                    'payment_id' => 'pay_refund_test',
                    'amount' => 100_000,
                    'currency' => 'PHP',
                    'status' => $status,
                    'reason' => 'others',
                ],
            ],
        ];
    }
}
