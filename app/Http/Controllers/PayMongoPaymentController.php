<?php

namespace App\Http\Controllers;

use App\Actions\Payments\ResumePayMongoCheckoutSession;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class PayMongoPaymentController extends Controller
{
    public function show(
        Request $request,
        Order $order,
    ): Response {
        $this->authorizeOrder(
            $request,
            $order,
        );

        return $this->renderPaymentPage(
            $request,
            $order,
            'status',
        );
    }

    public function success(
        Request $request,
        Order $order,
    ): Response {
        $this->authorizeOrder(
            $request,
            $order,
        );

        return $this->renderPaymentPage(
            $request,
            $order,
            'success',
        );
    }

    public function cancelled(
        Request $request,
        Order $order,
    ): Response {
        $this->authorizeOrder(
            $request,
            $order,
        );

        // Returning through PayMongo's cancel URL does not cancel the local
        // Payment. It remains Pending until an authoritative workflow changes
        // its status.
        return $this->renderPaymentPage(
            $request,
            $order,
            'cancelled',
        );
    }

    public function resume(
        Request $request,
        Order $order,
        ResumePayMongoCheckoutSession $resumeCheckoutSession,
    ): SymfonyResponse {
        $this->authorizeOrder(
            $request,
            $order,
        );

        try {
            $checkoutSession = $resumeCheckoutSession->handle(
                $order,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'payment' => 'We could not resume the online payment. Please try again later.',
            ]);
        }

        return Inertia::location(
            $checkoutSession['checkout_url'],
        );
    }

    private function renderPaymentPage(
        Request $request,
        Order $order,
        string $context,
    ): Response {
        $order->loadMissing('payment');

        $payment = $order->payment;

        abort_unless(
            $payment instanceof Payment
            && $order->payment_method->usesPayMongo(),
            404,
        );

        return Inertia::render(
            'checkout/payment-return',
            [
                'context' => $context,

                'can_resume' => $this->canResume(
                    $order,
                    $payment,
                ),

                'is_authenticated' => $request->user() !== null,

                'order' => [
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,

                    'payment_method' => $order->payment_method->value,
                    'payment_method_label' => $order->payment_method->label(),

                    // Local application state only.
                    'payment_status' => $payment->status->value,
                    'payment_status_label' => $payment->status->label(),

                    'grand_total' => $order->grand_total,
                ],
            ],
        );
    }

    private function canResume(
        Order $order,
        Payment $payment,
    ): bool {
        return $order->payment_method->usesPayMongo()
            && $order->payment_status === PaymentStatus::Pending
            && $payment->status === PaymentStatus::Pending
            && $order->order_status !== OrderStatus::Cancelled
            && StoreSetting::payMongoAvailableForNewCheckout();
    }

    private function authorizeOrder(
        Request $request,
        Order $order,
    ): void {
        if ($order->user_id !== null) {
            abort_unless(
                $request->user()?->id === $order->user_id,
                403,
            );

            return;
        }

        $sessionOrderId = $request
            ->session()
            ->get('checkout.order_id');

        abort_unless(
            is_numeric($sessionOrderId)
            && (int) $sessionOrderId === $order->id,
            403,
        );
    }
}
