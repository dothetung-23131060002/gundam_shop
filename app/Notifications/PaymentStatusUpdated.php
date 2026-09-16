<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Notifications\Concerns\SendsMailWhenConfigured;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PaymentStatusUpdated extends Notification
{
    use Queueable, SendsMailWhenConfigured;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus,
        public ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->mailChannels();
    }

    public function toArray(object $notifiable): array
    {
        $old = Order::paymentLabel($this->oldStatus);
        $new = Order::paymentLabel($this->newStatus);

        $data = [
            'order_id' => $this->order->id,
            'message' => "Thanh toán đơn hàng #{$this->order->id}: {$old} → {$new}",
            'link' => route('orders.show', $this->order),
        ];

        if (filled($this->reason)) {
            $data['reason'] = $this->reason;
        }

        return $data;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $old = Order::paymentLabel($this->oldStatus);
        $new = Order::paymentLabel($this->newStatus);

        $mail = (new MailMessage)
            ->subject("Thanh toán đơn hàng #{$this->order->id}: {$new}")
            ->greeting("Chào {$notifiable->name},")
            ->line("Thanh toán đơn hàng #{$this->order->id} đã chuyển trạng thái: {$old} → {$new}.");

        if (filled($this->reason)) {
            $mail->line('Lý do: '.$this->reason);
        }

        return $mail->action('Xem đơn hàng', route('orders.show', $this->order));
    }

    /**
     * Gửi notify cho khách + toàn bộ admin SAU commit.
     * Không để notification làm fail luồng thanh toán.
     */
    public static function sendToUserAndAdmins(User $user, Order $order, string $oldStatus, string $newStatus, ?string $reason = null): void
    {
        try {
            $user->notify(new self($order, $oldStatus, $newStatus, $reason));

            User::where('role', 'admin')->where('id', '!=', $user->id)->get()->each(
                fn (User $admin) => $admin->notify(new self($order, $oldStatus, $newStatus, $reason))
            );
        } catch (\Throwable $e) {
            Log::warning('Payment status notify failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
