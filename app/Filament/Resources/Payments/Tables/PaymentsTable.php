<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Actions\Payments\UpdatePaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\StoreSetting;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        $currency = StoreSetting::currentCurrency();

        return $table
            ->columns([
                TextColumn::make(
                    'order.order_number',
                )
                    ->label('Order')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('method')
                    ->formatStateUsing(
                        fn (
                            PaymentMethod $state,
                        ): string => $state->label(),
                    ),

                TextColumn::make('amount')
                    ->money(
                        $currency,
                        divideBy: 100,
                    )
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            PaymentStatus $state,
                        ): string => $state->label(),
                    ),

                TextColumn::make('reference')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('paid_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(
                        collect(
                            PaymentStatus::cases(),
                        )
                            ->mapWithKeys(
                                fn (
                                    PaymentStatus $status,
                                ): array => [
                                    $status->value => $status
                                        ->label(),
                                ],
                            )
                            ->all(),
                    ),

                SelectFilter::make('method')
                    ->options(
                        collect(
                            PaymentMethod::cases(),
                        )
                            ->mapWithKeys(
                                fn (
                                    PaymentMethod $method,
                                ): array => [
                                    $method->value => $method
                                        ->label(),
                                ],
                            )
                            ->all(),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),

                EditAction::make()
                    ->visible(
                        fn (
                            Payment $record,
                        ): bool => ! $record
                            ->isPayMongoManaged(),
                    ),

                Action::make('markPaid')
                    ->label('Mark paid')
                    ->requiresConfirmation()
                    ->visible(
                        fn (
                            Payment $record,
                        ): bool => (
                            $record->status
                                === PaymentStatus::Pending
                            && ! $record->isPayMongoManaged()
                        ),
                    )
                    ->action(
                        function (
                            Payment $record,
                        ): void {
                            app(
                                UpdatePaymentStatus::class,
                            )->handle(
                                payment: $record,
                                status: PaymentStatus::Paid,
                                reference: $record
                                    ->reference,
                                notes: $record->notes,
                            );

                            Notification::make()
                                ->title(
                                    'Payment marked as paid',
                                )
                                ->success()
                                ->send();
                        },
                    ),
            ])
            ->defaultSort(
                'created_at',
                'desc',
            );
    }
}
