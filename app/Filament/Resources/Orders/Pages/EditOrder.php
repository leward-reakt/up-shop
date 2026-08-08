<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(
        Model $record,
        array $data,
    ): Model {
        if (! $record instanceof Order) {
            return parent::handleRecordUpdate(
                $record,
                $data,
            );
        }

        $status = OrderStatus::from(
            (string) $data['order_status'],
        );

        $order = app(UpdateOrderStatus::class)->handle(
            order: $record,
            status: $status,
        );

        $order->update([
            'admin_notes' => $this->nullableString(
                $data['admin_notes'] ?? null,
            ),
        ]);

        return $order->refresh();
    }

    private function nullableString(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
