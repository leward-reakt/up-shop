<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Actions\Payments\UpdatePaymentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(
        Model $record,
        array $data,
    ): Model {
        if (! $record instanceof Payment) {
            return parent::handleRecordUpdate(
                $record,
                $data,
            );
        }

        return app(UpdatePaymentStatus::class)->handle(
            payment: $record,
            status: PaymentStatus::from(
                (string) $data['status'],
            ),
            reference: isset($data['reference'])
                ? (string) $data['reference']
                : null,
            notes: isset($data['notes'])
                ? (string) $data['notes']
                : null,
        );
    }
}
