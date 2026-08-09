<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

class OrderPlacedNotification extends Notification
{
    public function __construct(
        public readonly Order $order,
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
        $message = (new MailMessage)
            ->subject(
                "Order {$this->order->order_number} received",
            )
            ->greeting(
                "Hi {$this->order->customer_name},",
            )
            ->line(
                "We received your order {$this->order->order_number}.",
            )
            ->line(
                'Order total: '.$this->money(
                    $this->order->grand_total,
                ),
            )
            ->line(
                'Payment method: '
                .$this->order->payment_method->label(),
            )
            ->line(
                'Payment status: '
                .$this->order->payment_status->label(),
            );

        if ($this->order->user_id !== null) {
            $message->action(
                'View order',
                route(
                    'account.orders.show',
                    $this->order,
                ),
            );
        }

        return $message->line(
            'Thank you for your order.',
        );
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
