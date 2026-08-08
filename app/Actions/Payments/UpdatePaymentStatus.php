<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class UpdatePaymentStatus
{
    public function handle(
        Payment $payment,
        PaymentStatus $status,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        return DB::transaction(function () use (
            $payment,
            $status,
            $reference,
            $notes,
        ): Payment {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

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

            $lockedPayment->order()->update([
                'payment_status' => $status,
            ]);

            return $lockedPayment
                ->refresh()
                ->load('order');
        });
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
