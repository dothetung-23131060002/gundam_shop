<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReturnRequestStatusUpdated extends Notification
{
    use Queueable;

    public const EVENT_REQUESTED = 'requested';

    public const EVENT_APPROVED = 'approved';

    public const EVENT_REJECTED = 'rejected';

    public const EVENT_RECEIVED = 'received';

    public const EVENT_COMPLETED = 'completed';

    public function __construct(
        public int $returnId,
        public string $event,
        public ?float $refundAmount = null,
    ) {}

    /**
     * Phase C core: database only (no email, no analytics).
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $amount = $this->refundAmount !== null
            ? number_format((float) $this->refundAmount, 0, ',', '.').'đ'
            : null;

        $message = match ($this->event) {
            self::EVENT_APPROVED => "Yêu cầu trả hàng #{$this->returnId} đã được duyệt. Vui lòng gửi hàng về shop.",
            self::EVENT_REJECTED => "Yêu cầu trả hàng #{$this->returnId} đã bị từ chối.",
            self::EVENT_RECEIVED => "Shop đã nhận được hàng trả của yêu cầu #{$this->returnId} và đang kiểm tra.",
            self::EVENT_COMPLETED => $amount
                ? "Trả hàng #{$this->returnId} đã hoàn thành. Đã hoàn {$amount}."
                : "Trả hàng #{$this->returnId} đã hoàn thành.",
            default => "Yêu cầu trả hàng #{$this->returnId} đã được gửi. Shop sẽ xem xét sớm.",
        };

        return [
            'type' => 'return',
            'event' => $this->event,
            'return_id' => $this->returnId,
            'message' => $message,
            // Relative URLs: never depend on APP_URL/host.
            'link' => '/returns/'.$this->returnId,
            'admin_link' => '/admin/returns/'.$this->returnId,
        ];
    }
}
