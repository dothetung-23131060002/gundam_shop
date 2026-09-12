<?php

namespace App\Notifications;

use App\Notifications\Concerns\SendsMailWhenConfigured;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeRegistered extends Notification
{
    use Queueable, SendsMailWhenConfigured;

    public function via(object $notifiable): array
    {
        return $this->mailChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Chào mừng {$notifiable->name} đến với Gundam Shop! Hãy khám phá các đợt gom đang mở.",
            'link' => route('batches.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Chào mừng đến Gundam Shop — gom đơn theo đợt là gì?')
            ->greeting("Chào {$notifiable->name},")
            ->line('Tài khoản của bạn đã được tạo. Shop hoạt động theo mô hình gom đơn theo đợt:')
            ->line('1. Giữ slot bằng cọc — chọn đợt gom, đặt cọc một phần để giữ chỗ, không cần trả hết.')
            ->line('2. Chờ đủ ngưỡng — đợt thành công khi đủ số slot trước deadline; thiếu ngưỡng thì tự động hoàn cọc.')
            ->line('3. Thanh toán đủ — đợt thành công mới trả nốt phần còn lại, hệ thống tạo đơn hàng giao tận nơi.')
            ->action('Xem đợt gom đang mở', route('batches.index'));
    }
}
