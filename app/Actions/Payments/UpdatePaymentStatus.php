<?php

namespace App\Actions\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdatePaymentStatus
{
    public function handle(
        Payment $payment,
        PaymentStatus $status,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        $becamePaid = false;

        $updatedPayment = DB::transaction(
            function () use (
                $payment,
                $status,
                $reference,
                $notes,
                &$becamePaid,
            ): Payment {
                $lockedPayment = Payment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                $statusChanged = $lockedPayment->status !== $status;

                if ($statusChanged) {
                    $allowedStatuses = self::allowedNextStatuses(
                        $lockedPayment,
                    );

                    if (! in_array($status, $allowedStatuses, true)) {
                        throw ValidationException::withMessages([
                            'status' => 'That payment status transition is not allowed.',
                        ]);
                    }
                }

                $normalizedReference = $this->nullableString(
                    $reference,
                );

                $normalizedNotes = $this->nullableString(
                    $notes,
                );

                if (
                    $lockedPayment->method === PaymentMethod::BankTransfer
                    && $status === PaymentStatus::Paid
                    && $normalizedReference === null
                ) {
                    throw ValidationException::withMessages([
                        'reference' => 'A payment reference is required when marking a bank transfer as paid.',
                    ]);
                }

                $becamePaid = (
                    $statusChanged
                    && $status === PaymentStatus::Paid
                );

                $paidAt = $lockedPayment->paid_at;

                if (
                    $status === PaymentStatus::Paid
                    && $paidAt === null
                ) {
                    $paidAt = now();
                }

                if (
                    ! in_array(
                        $status,
                        [
                            PaymentStatus::Paid,
                            PaymentStatus::Refunded,
                        ],
                        true,
                    )
                ) {
                    $paidAt = null;
                }

                $lockedPayment->update([
                    'status' => $status,
                    'reference' => $normalizedReference,
                    'notes' => $normalizedNotes,
                    'paid_at' => $paidAt,
                ]);

                // Keep the order snapshot synchronized with the payment
                // inside the same transaction.
                $lockedPayment
                    ->order()
                    ->update([
                        'payment_status' => $status,
                    ]);

                return $lockedPayment
                    ->refresh()
                    ->load('order');
            },
        );

        if ($becamePaid) {
            $this->notifyCustomer($updatedPayment);
        }

        return $updatedPayment;
    }

    /**
     * @return array<int, PaymentStatus>
     */
    public static function allowedNextStatuses(
        Payment $payment,
    ): array {
        return match ($payment->status) {
            PaymentStatus::Pending => [
                PaymentStatus::Paid,
                PaymentStatus::Failed,
                PaymentStatus::Cancelled,
            ],

            PaymentStatus::Failed => [
                PaymentStatus::Pending,
                PaymentStatus::Cancelled,
            ],

            PaymentStatus::Paid => [
                PaymentStatus::Refunded,
            ],

            PaymentStatus::Cancelled,
            PaymentStatus::Refunded => [],
        };
    }

    private function notifyCustomer(
        Payment $payment,
    ): void {
        $order = $payment->order;

        if (! $order instanceof Order) {
            return;
        }

        try {
            Notification::route(
                'mail',
                [
                    $order->customer_email => $order->customer_name,
                ],
            )->notify(
                new PaymentConfirmedNotification(
                    $payment,
                ),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function nullableString(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
