<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

                $becamePaid = (
                    $lockedPayment->status
                    !== PaymentStatus::Paid
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
                    'reference' => $this->nullableString(
                        $reference,
                    ),
                    'notes' => $this->nullableString(
                        $notes,
                    ),
                    'paid_at' => $paidAt,
                ]);

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
