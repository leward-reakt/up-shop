<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Actions\Orders\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\StoreSetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        $currency = StoreSetting::currentCurrency();

        return $schema
            ->components([
                TextInput::make('order_number')
                    ->label('Order number')
                    ->disabled()
                    ->saved(false),

                TextInput::make('customer_name')
                    ->disabled()
                    ->saved(false),

                TextInput::make('customer_email')
                    ->disabled()
                    ->saved(false),

                TextInput::make('customer_phone')
                    ->disabled()
                    ->saved(false),

                TextInput::make(
                    'shipping_address_line_1',
                )
                    ->label(
                        fn (?Order $record): string => $record?->shipping_method
                            === ShippingMethod::StorePickup
                                ? 'Customer address'
                                : 'Delivery address',
                    )
                    ->disabled()
                    ->saved(false),

                TextInput::make(
                    'shipping_address_line_2',
                )
                    ->label('Address line 2')
                    ->disabled()
                    ->saved(false),

                TextInput::make('shipping_city')
                    ->label('City')
                    ->disabled()
                    ->saved(false),

                TextInput::make(
                    'shipping_province',
                )
                    ->label('Province')
                    ->disabled()
                    ->saved(false),

                TextInput::make(
                    'shipping_postal_code',
                )
                    ->label('Postal code')
                    ->disabled()
                    ->saved(false),

                TextInput::make('shipping_method')
                    ->formatStateUsing(
                        fn (
                            mixed $state,
                        ): string => match (true) {
                            $state instanceof ShippingMethod => $state
                                ->label(),

                            is_string($state) => ShippingMethod::tryFrom(
                                $state,
                            )?->label() ?? $state,

                            default => '',
                        },
                    )
                    ->disabled()
                    ->saved(false),

                Textarea::make('pickup_location')
                    ->label('Pickup location')
                    ->rows(3)
                    ->disabled()
                    ->saved(false)
                    ->visible(
                        fn (?Order $record): bool => $record?->shipping_method
                            === ShippingMethod::StorePickup,
                    ),

                TextInput::make('subtotal')
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
                    ->prefix($currency)
                    ->disabled()
                    ->saved(false),

                TextInput::make('discount_total')
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
                    ->prefix($currency)
                    ->disabled()
                    ->saved(false),

                TextInput::make('shipping_total')
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
                    ->prefix($currency)
                    ->disabled()
                    ->saved(false),

                TextInput::make('tax_total')
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
                    ->prefix($currency)
                    ->disabled()
                    ->saved(false),

                TextInput::make('grand_total')
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
                    ->prefix($currency)
                    ->disabled()
                    ->saved(false),

                TextInput::make('payment_method')
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

                TextInput::make(
                    'payment_status',
                )
                    ->formatStateUsing(
                        fn (
                            mixed $state,
                        ): string => match (true) {
                            $state instanceof PaymentStatus => $state
                                ->label(),

                            is_string($state) => PaymentStatus::tryFrom(
                                $state,
                            )?->label() ?? $state,

                            default => '',
                        },
                    )
                    ->disabled()
                    ->saved(false),

                TextInput::make('payment_amount')
                    ->label('Payment amount')
                    ->formatStateUsing(
                        fn (
                            mixed $state,
                            ?Order $record,
                        ): ?string => $record?->payment === null
                            ? null
                            : number_format(
                                ((int) $record->payment->amount) / 100,
                                2,
                            ),
                    )
                    ->prefix($currency)
                    ->disabled()
                    ->saved(false),

                TextInput::make(
                    'paymongo_checkout_reference',
                )
                    ->label('PayMongo Checkout Reference')
                    ->formatStateUsing(
                        fn (
                            mixed $state,
                            ?Order $record,
                        ): ?string => $record?->payment?->provider_checkout_id,
                    )
                    ->placeholder('Not available')
                    ->disabled()
                    ->saved(false)
                    ->visible(
                        fn (?Order $record): bool => $record?->payment_method
                            ?->usesPayMongo() ?? false,
                    ),

                TextInput::make(
                    'paymongo_payment_reference',
                )
                    ->label('PayMongo Payment Reference')
                    ->formatStateUsing(
                        fn (
                            mixed $state,
                            ?Order $record,
                        ): ?string => $record?->payment?->reference,
                    )
                    ->placeholder('Not paid yet')
                    ->disabled()
                    ->saved(false)
                    ->visible(
                        fn (?Order $record): bool => $record?->payment_method
                            ?->usesPayMongo() ?? false,
                    ),

                TextInput::make('payment_paid_at')
                    ->label('Paid at')
                    ->formatStateUsing(
                        fn (
                            mixed $state,
                            ?Order $record,
                        ): ?string => $record?->payment?->paid_at
                            ?->format('Y-m-d H:i:s'),
                    )
                    ->placeholder('Not paid yet')
                    ->disabled()
                    ->saved(false),

                TextInput::make('refund_reference')
                    ->label('Refund Reference')
                    ->formatStateUsing(
                        fn (
                            mixed $state,
                            ?Order $record,
                        ): ?string => $record?->payment?->refund_reference,
                    )
                    ->placeholder('Not refunded')
                    ->disabled()
                    ->saved(false)
                    ->visible(
                        fn (?Order $record): bool => $record?->payment_method
                            ?->usesPayMongo() ?? false,
                    ),

                TextInput::make('payment_refunded_at')
                    ->label('Refunded at')
                    ->formatStateUsing(
                        fn (
                            mixed $state,
                            ?Order $record,
                        ): ?string => $record?->payment?->refunded_at
                            ?->format('Y-m-d H:i:s'),
                    )
                    ->placeholder('Not refunded')
                    ->disabled()
                    ->saved(false)
                    ->visible(
                        fn (?Order $record): bool => $record?->payment_method
                            ?->usesPayMongo() ?? false,
                    ),

                Select::make('order_status')
                    ->label('Order status')
                    ->options(
                        function (
                            ?Order $record,
                        ): array {
                            if ($record === null) {
                                return [];
                            }

                            $statuses = [
                                $record->order_status,
                                ...UpdateOrderStatus::allowedNextStatuses(
                                    $record,
                                ),
                            ];

                            return collect($statuses)
                                ->unique(
                                    fn (
                                        OrderStatus $status,
                                    ): string => $status
                                        ->value,
                                )
                                ->mapWithKeys(
                                    fn (
                                        OrderStatus $status,
                                    ): array => [
                                        $status->value => $status
                                            ->label(),
                                    ],
                                )
                                ->all();
                        },
                    )
                    ->required(),

                Textarea::make(
                    'customer_notes',
                )
                    ->label('Customer notes')
                    ->rows(3)
                    ->disabled()
                    ->saved(false),

                Textarea::make('admin_notes')
                    ->label('Internal notes')
                    ->rows(4)
                    ->maxLength(5000),
            ]);
    }
}
