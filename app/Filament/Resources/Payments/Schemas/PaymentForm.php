<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Actions\Payments\UpdatePaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\StoreSetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        $currency = StoreSetting::currentCurrency();

        return $schema
            ->components([
                TextInput::make('order_id')
                    ->label('Order')
                    ->formatStateUsing(
                        fn (
                            int|string|null $state,
                            ?Payment $record,
                        ): string => $record?->order
                            ?->order_number
                            ?? (
                                $state !== null
                                    ? "Order #{$state}"
                                    : 'Unavailable'
                            ),
                    )
                    ->disabled()
                    ->saved(false),

                TextInput::make('method')
                    ->formatStateUsing(
                        fn (
                            mixed $state,
                        ): string => match (true) {
                            $state instanceof PaymentMethod => $state
                                ->label(),

                            is_string($state) => PaymentMethod::tryFrom(
                                $state,
                            )?->label() ?? $state,

                            default => '',
                        },
                    )
                    ->disabled()
                    ->saved(false),

                TextInput::make('amount')
                    ->prefix($currency)
                    ->formatStateUsing(
                        fn (
                            int|string|null $state,
                        ): ?string => $state === null
                            ? null
                            : number_format(
                                ((int) $state) / 100,
                                2,
                            ),
                    )
                    ->disabled()
                    ->saved(false),

                Select::make('status')
                    ->options(
                        function (
                            ?Payment $record,
                        ): array {
                            if ($record === null) {
                                return [];
                            }

                            $statuses = [
                                $record->status,
                                ...UpdatePaymentStatus::allowedNextStatuses(
                                    $record,
                                ),
                            ];

                            return collect($statuses)
                                ->unique(
                                    fn (
                                        PaymentStatus $status,
                                    ): string => $status
                                        ->value,
                                )
                                ->mapWithKeys(
                                    fn (
                                        PaymentStatus $status,
                                    ): array => [
                                        $status->value => $status
                                            ->label(),
                                    ],
                                )
                                ->all();
                        },
                    )
                    ->required(),

                TextInput::make('reference')
                    ->placeholder(
                        fn (
                            string $operation,
                            ?Payment $record,
                        ): string => match (true) {
                            $operation === 'view' => 'Not provided',

                            $record?->method
                                === PaymentMethod::BankTransfer => 'Enter bank transfer reference',

                            default => 'Optional',
                        },
                    )
                    ->maxLength(255),

                Textarea::make('notes')
                    ->placeholder(
                        fn (
                            string $operation,
                        ): string => $operation
                            === 'view'
                            ? 'No notes'
                            : 'Optional payment notes',
                    )
                    ->rows(4)
                    ->maxLength(5000),

                TextInput::make('paid_at')
                    ->label('Paid at')
                    ->placeholder('Not paid yet')
                    ->disabled()
                    ->saved(false),
            ]);
    }
}
