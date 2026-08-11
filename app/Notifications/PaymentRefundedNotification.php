<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\StoreSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

class PaymentRefundedNotification extends Notification
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
                "Refund confirmed for {$order->order_number}",
            )
            ->greeting(
                "Hi {$order->customer_name},",
            )
            ->line(
                "Your full refund for order {$order->order_number} has been confirmed.",
            )
            ->line(
                'Refund amount: '.$this->money(
                    $this->payment->amount,
                ),
            );

        if ($this->payment->refund_reference !== null) {
            $message->line(
                "Refund reference: {$this->payment->refund_reference}",
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
        $currency = StoreSetting::currentCurrency();

        $formattedAmount = Number::currency(
            $amount / 100,
            in: $currency,
        );

        return $formattedAmount !== false
            ? $formattedAmount
            : sprintf(
                '%s %s',
                $currency,
                number_format(
                    $amount / 100,
                    2,
                ),
            );
    }
}
