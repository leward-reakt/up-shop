<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order.order_number')
                    ->label('Order')
                    ->disabled()
                    ->saved(false),

                TextInput::make('method')
                    ->formatStateUsing(
                        fn ($state): string => $state?->label()
                            ?? (string) $state,
                    )
                    ->disabled()
                    ->saved(false),

                TextInput::make('amount')
                    ->prefix('₱')
                    ->formatStateUsing(
                        fn (int|string|null $state): ?string => $state === null
                            ? null
                            : number_format(((int) $state) / 100, 2),
                    )
                    ->disabled()
                    ->saved(false),

                Select::make('status')
                    ->options(
                        collect(PaymentStatus::cases())
                            ->mapWithKeys(
                                fn (PaymentStatus $status): array => [
                                    $status->value => $status->label(),
                                ],
                            )
                            ->all(),
                    )
                    ->required(),

                TextInput::make('reference')
                    ->maxLength(255),

                Textarea::make('notes')
                    ->rows(4)
                    ->maxLength(5000),

                TextInput::make('paid_at')
                    ->label('Paid at')
                    ->disabled()
                    ->saved(false),
            ]);
    }
}
