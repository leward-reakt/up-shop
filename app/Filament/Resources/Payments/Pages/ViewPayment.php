<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Actions\Payments\RefundPayMongoPayment;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Models\StoreSetting;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refundPayment')
                ->label('Refund payment')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Refund payment')
                ->modalDescription(
                    fn (): string => sprintf(
                        'Refund the full payment amount of %s through PayMongo. This action cannot issue a partial refund.',
                        $this->refundAmount(
                            $this->getRecord(),
                        ),
                    ),
                )
                ->modalSubmitActionLabel('Refund full amount')
                ->visible(
                    fn (): bool => app(
                        RefundPayMongoPayment::class,
                    )->isEligible(
                        $this->getRecord(),
                    ),
                )
                ->action(
                    function (): void {
                        try {
                            $payment = app(
                                RefundPayMongoPayment::class,
                            )->handle(
                                $this->getRecord(),
                            );
                        } catch (
                            ValidationException $exception
                        ) {
                            Notification::make()
                                ->title('Refund not completed')
                                ->body(
                                    collect(
                                        $exception->errors(),
                                    )
                                        ->flatten()
                                        ->first()
                                    ?? 'The refund could not be completed.',
                                )
                                ->danger()
                                ->send();

                            return;
                        }

                        $this->getRecord()->refresh();

                        if (
                            $payment->status
                            === PaymentStatus::Refunded
                        ) {
                            Notification::make()
                                ->title('Payment refunded')
                                ->body(
                                    'PayMongo confirmed the full refund.',
                                )
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Refund submitted')
                            ->body(
                                'PayMongo is processing the refund. The payment will remain Paid until provider confirmation is received.',
                            )
                            ->warning()
                            ->send();
                    },
                ),

            EditAction::make(),
        ];
    }

    private function refundAmount(
        Payment $payment,
    ): string {
        $currency = StoreSetting::currentCurrency();

        $formattedAmount = Number::currency(
            $payment->amount / 100,
            in: $currency,
        );

        return $formattedAmount !== false
            ? $formattedAmount
            : sprintf(
                '%s %s',
                $currency,
                number_format(
                    $payment->amount / 100,
                    2,
                ),
            );
    }
}
