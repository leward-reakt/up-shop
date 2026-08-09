<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Actions\Orders\CancelOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        $currency = StoreSetting::currentCurrency();

        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('customer_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money(
                        $currency,
                        divideBy: 100,
                    )
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->formatStateUsing(
                        fn (
                            PaymentMethod $state,
                        ): string => $state->label(),
                    ),

                TextColumn::make(
                    'payment_status',
                )
                    ->label('Payment status')
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            PaymentStatus $state,
                        ): string => $state->label(),
                    ),

                TextColumn::make('order_status')
                    ->label('Order status')
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            OrderStatus $state,
                        ): string => $state->label(),
                    ),

                TextColumn::make('created_at')
                    ->label('Ordered')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->label('Order date')
                    ->schema([
                        DatePicker::make('from')
                            ->label('From')
                            ->maxDate(
                                function (
                                    Get $get,
                                ): ?string {
                                    $to = $get('to');

                                    return is_string($to)
                                        ? $to
                                        : null;
                                },
                            ),

                        DatePicker::make('to')
                            ->label('To')
                            ->minDate(
                                function (
                                    Get $get,
                                ): ?string {
                                    $from = $get('from');

                                    return is_string($from)
                                        ? $from
                                        : null;
                                },
                            ),
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data,
                        ): Builder {
                            $from = $data['from'] ?? null;
                            $to = $data['to'] ?? null;

                            return $query
                                ->when(
                                    is_string($from)
                                        && $from !== '',
                                    fn (
                                        Builder $query,
                                    ): Builder => $query
                                        ->whereDate(
                                            'created_at',
                                            '>=',
                                            $from,
                                        ),
                                )
                                ->when(
                                    is_string($to)
                                        && $to !== '',
                                    fn (
                                        Builder $query,
                                    ): Builder => $query
                                        ->whereDate(
                                            'created_at',
                                            '<=',
                                            $to,
                                        ),
                                );
                        },
                    ),

                SelectFilter::make(
                    'order_status',
                )
                    ->options(
                        collect(OrderStatus::cases())
                            ->mapWithKeys(
                                fn (
                                    OrderStatus $status,
                                ): array => [
                                    $status->value => $status
                                        ->label(),
                                ],
                            )
                            ->all(),
                    ),

                SelectFilter::make(
                    'payment_status',
                )
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

                SelectFilter::make(
                    'payment_method',
                )
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
                EditAction::make(),

                Action::make('cancel')
                    ->label('Cancel')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(
                        fn (
                            Order $record,
                        ): bool => ! $record
                            ->order_status
                            ->isTerminal(),
                    )
                    ->action(
                        function (
                            Order $record,
                        ): void {
                            $user = auth()->user();

                            abort_unless(
                                $user instanceof User,
                                403,
                            );

                            app(
                                CancelOrder::class,
                            )->handle(
                                order: $record,
                                user: $user,
                            );

                            Notification::make()
                                ->title(
                                    'Order cancelled',
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
