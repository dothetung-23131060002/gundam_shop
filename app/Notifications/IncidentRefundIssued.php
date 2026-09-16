<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\RefundTransaction;
use App\Notifications\Concerns\SendsMailWhenConfigured;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentRefundIssued extends Notification
{
    use Queueable, SendsMailWhenConfigured;

    public function __construct(
        public Order $order,
        public RefundTransaction $refund,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->mailChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'refund_id' => $this->refund->id,
            'message' => "Đơn hàng #{$this->order->id} phát sinh sự cố ({$this->refund->reason}). Đã hoàn ".number_format((float) $this->refund->amount, 0, ',', '.')."đ.",
            'link' => route('orders.show', $this->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Đơn hàng #{$this->order->id} — đã hoàn tiền sự cố")
            ->greeting("Chào {$notifiable->name},")
            ->line("Đơn hàng #{$this->order->id} của bạn gặp sự cố ({$this->refund->reason}).")
            ->line('Shop đã hoàn '.number_format((float) $this->refund->amount, 0, ',', '.').'đ. Chi tiết trong đơn hàng của bạn.')
            ->action('Xem đơn hàng', route('orders.show', $this->order));
    }
}
