<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\SendsMailWhenConfigured;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification
{
    use Queueable, SendsMailWhenConfigured;

    public const STATUS_LABELS = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao hàng',
        'completed' => 'Đã giao hàng',
        'cancelled' => 'Đã hủy',
    ];

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->mailChannels();
    }

    public function toArray(object $notifiable): array
    {
        $old = self::STATUS_LABELS[$this->oldStatus] ?? $this->oldStatus;
        $new = self::STATUS_LABELS[$this->newStatus] ?? $this->newStatus;

        return [
            'order_id' => $this->order->id,
            'message' => "Đơn hàng #{$this->order->id}: {$old} → {$new}",
            'link' => route('orders.show', $this->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $old = self::STATUS_LABELS[$this->oldStatus] ?? $this->oldStatus;
        $new = self::STATUS_LABELS[$this->newStatus] ?? $this->newStatus;

        return (new MailMessage)
            ->subject("Đơn hàng #{$this->order->id}: {$new}")
            ->greeting("Chào {$notifiable->name},")
            ->line("Đơn hàng #{$this->order->id} của bạn đã chuyển trạng thái: {$old} → {$new}.")
            ->action('Xem đơn hàng', route('orders.show', $this->order));
    }
}
