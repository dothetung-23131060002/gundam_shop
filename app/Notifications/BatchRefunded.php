<?php

namespace App\Notifications;

use App\Models\Batch;
use App\Models\Reservation;
use App\Notifications\Concerns\SendsMailWhenConfigured;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BatchRefunded extends Notification
{
    use Queueable, SendsMailWhenConfigured;

    public function __construct(
        public Batch $batch,
        public Reservation $reservation,
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->mailChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'batch_id' => $this->batch->id,
            'reservation_id' => $this->reservation->id,
            'message' => "Đợt gom #{$this->batch->id} thất bại. {$this->reason}. Tiền cọc đã được hoàn.",
            'link' => route('reservations.show', $this->reservation),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Đợt gom #{$this->batch->id} chưa thành công — đã hoàn cọc")
            ->greeting("Chào {$notifiable->name},")
            ->line("Đợt gom sản phẩm {$this->batch->product->name} chưa đạt ngưỡng.")
            ->line("Lý do: {$this->reason}.")
            ->line('Tiền cọc của bạn đã được hoàn. Chi tiết trong lịch sử giữ slot.')
            ->action('Xem chi tiết hoàn cọc', route('reservations.show', $this->reservation));
    }
}
