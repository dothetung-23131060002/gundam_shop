<?php

namespace App\Notifications;

use App\Models\Batch;
use App\Notifications\Concerns\SendsMailWhenConfigured;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BatchSucceeded extends Notification
{
    use Queueable, SendsMailWhenConfigured;

    public function __construct(
        public Batch $batch,
        public int $quantity,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->mailChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'batch_id' => $this->batch->id,
            'product_name' => $this->batch->product->name,
            'quantity' => $this->quantity,
            'message' => "Đợt gom #{$this->batch->id} đã thành công! Bạn giữ {$this->quantity} slot.",
            'link' => route('reservations.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Đợt gom #{$this->batch->id} đã thành công!")
            ->greeting("Chào {$notifiable->name},")
            ->line("Sản phẩm {$this->batch->product->name} đã gom đủ {$this->batch->threshold} slot.")
            ->line("Bạn đang giữ {$this->quantity} slot. Vui lòng thanh toán phần còn lại để nhận hàng.")
            ->action('Thanh toán phần còn lại', route('reservations.index'));
    }
}
