<?php

namespace App\Notifications;

use App\Models\Batch;
use App\Models\Reservation;
use App\Notifications\Concerns\SendsMailWhenConfigured;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositForfeited extends Notification
{
    use Queueable, SendsMailWhenConfigured;

    public function __construct(
        public Batch $batch,
        public Reservation $reservation,
        public float $amount,
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
            'message' => "Quá hạn thanh toán phần còn lại đợt gom #{$this->batch->id}. Tiền cọc ".number_format($this->amount, 0, ',', '.')."đ đã bị tịch thu theo quy định.",
            'link' => route('reservations.show', $this->reservation),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Quá hạn thanh toán đợt gom #{$this->batch->id} — tiền cọc đã bị tịch thu")
            ->greeting("Chào {$notifiable->name},")
            ->line("Bạn đã quá hạn thanh toán phần còn lại của đợt gom sản phẩm {$this->batch->product->name}.")
            ->line('Tiền cọc '.number_format($this->amount, 0, ',', '.').'đ đã bị tịch thu theo quy định của shop.')
            ->action('Xem chi tiết', route('reservations.show', $this->reservation));
    }
}
