<?php

namespace App\Notifications;

use App\Models\Batch;
use App\Notifications\Concerns\SendsMailWhenConfigured;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BatchOpened extends Notification
{
    use Queueable, SendsMailWhenConfigured;

    public function __construct(
        public Batch $batch,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->mailChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'batch_id' => $this->batch->id,
            'product_id' => $this->batch->product_id,
            'product_name' => $this->batch->product->name,
            'message' => "Sản phẩm bạn yêu thích ({$this->batch->product->name}) đã mở đợt gom mới.",
            // Relative URL: never depends on APP_URL/host.
            'link' => '/batches/'.$this->batch->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Sản phẩm bạn yêu thích đã mở đợt gom mới")
            ->greeting("Chào {$notifiable->name},")
            ->line("Sản phẩm {$this->batch->product->name} bạn yêu thích đã mở đợt gom mới.")
            ->action('Xem đợt gom', url('/batches/'.$this->batch->id));
    }
}
