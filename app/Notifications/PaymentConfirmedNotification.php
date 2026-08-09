<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\StoreSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

class PaymentConfirmedNotification extends Notification
{
    public function __construct(
        public readonly Payment $payment,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this
            ->payment
            ->order()
            ->firstOrFail();

        $message = (new MailMessage)
            ->subject(
                "Payment confirmed for {$order->order_number}",
            )
            ->greeting(
                "Hi {$order->customer_name},",
            )
            ->line(
                "Your payment for order {$order->order_number} has been confirmed.",
            )
            ->line(
                'Amount received: '.$this->money(
                    $this->payment->amount,
                ),
            );

        if ($this->payment->reference !== null) {
            $message->line(
                "Payment reference: {$this->payment->reference}",
            );
        }

        if ($order->user_id !== null) {
            $message->action(
                'View order',
                route(
                    'account.orders.show',
                    $order,
                ),
            );
        }

        return $message;
    }

    private function money(int $amount): string
    {
        return Number::currency(
            $amount / 100,
            in: StoreSetting::currentCurrency(),
        );
    }
}
