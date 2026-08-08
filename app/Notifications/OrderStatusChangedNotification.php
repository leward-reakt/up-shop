<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification
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
        $subject = match ($this->order->order_status) {
            OrderStatus::Processing => "Order {$this->order->order_number} is being processed",

            OrderStatus::Shipped => "Order {$this->order->order_number} has shipped",

            OrderStatus::Completed => "Order {$this->order->order_number} is complete",

            OrderStatus::Cancelled => "Order {$this->order->order_number} was cancelled",

            default => "Order {$this->order->order_number} updated",
        };

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting(
                "Hi {$this->order->customer_name},",
            )
            ->line(
                "Your order {$this->order->order_number} is now {$this->order->order_status->label()}.",
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

        return $message;
    }
}
