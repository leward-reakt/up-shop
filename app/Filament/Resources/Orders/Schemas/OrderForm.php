<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Actions\Orders\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
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

                TextInput::make('shipping_address_line_1')
                    ->label('Address')
                    ->disabled()
                    ->saved(false),

                TextInput::make('shipping_address_line_2')
                    ->label('Address line 2')
                    ->disabled()
                    ->saved(false),

                TextInput::make('shipping_city')
                    ->label('City')
                    ->disabled()
                    ->saved(false),

                TextInput::make('shipping_province')
                    ->label('Province')
                    ->disabled()
                    ->saved(false),

                TextInput::make('shipping_postal_code')
                    ->label('Postal code')
                    ->disabled()
                    ->saved(false),

                TextInput::make('shipping_method')
                    ->formatStateUsing(
                        fn ($state): string => $state?->label()
                            ?? (string) $state,
                    )
                    ->disabled()
                    ->saved(false),

                TextInput::make('subtotal')
                    ->formatStateUsing(
                        fn (int|string|null $state): ?string => $state === null
                            ? null
                            : number_format(((int) $state) / 100, 2),
                    )
                    ->prefix('₱')
                    ->disabled()
                    ->saved(false),

                TextInput::make('discount_total')
                    ->formatStateUsing(
                        fn (int|string|null $state): ?string => $state === null
                            ? null
                            : number_format(((int) $state) / 100, 2),
                    )
                    ->prefix('₱')
                    ->disabled()
                    ->saved(false),

                TextInput::make('shipping_total')
                    ->formatStateUsing(
                        fn (int|string|null $state): ?string => $state === null
                            ? null
                            : number_format(((int) $state) / 100, 2),
                    )
                    ->prefix('₱')
                    ->disabled()
                    ->saved(false),

                TextInput::make('tax_total')
                    ->formatStateUsing(
                        fn (int|string|null $state): ?string => $state === null
                            ? null
                            : number_format(((int) $state) / 100, 2),
                    )
                    ->prefix('₱')
                    ->disabled()
                    ->saved(false),

                TextInput::make('grand_total')
                    ->formatStateUsing(
                        fn (int|string|null $state): ?string => $state === null
                            ? null
                            : number_format(((int) $state) / 100, 2),
                    )
                    ->prefix('₱')
                    ->disabled()
                    ->saved(false),

                TextInput::make('payment_method')
                    ->formatStateUsing(
                        fn ($state): string => $state?->label()
                            ?? (string) $state,
                    )
                    ->disabled()
                    ->saved(false),

                TextInput::make('payment_status')
                    ->formatStateUsing(
                        fn ($state): string => $state?->label()
                            ?? (string) $state,
                    )
                    ->disabled()
                    ->saved(false),

                Select::make('order_status')
                    ->label('Order status')
                    ->options(
                        function (?Order $record): array {
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
                                    fn (OrderStatus $status): string => $status->value,
                                )
                                ->mapWithKeys(
                                    fn (OrderStatus $status): array => [
                                        $status->value => $status->label(),
                                    ],
                                )
                                ->all();
                        },
                    )
                    ->required(),

                Textarea::make('customer_notes')
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
