<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WarrantyRequestStatusUpdated extends Notification
{
    use Queueable;

    public const EVENT_REQUESTED = 'requested';

    public const EVENT_APPROVED = 'approved';

    public const EVENT_REJECTED = 'rejected';

    public const EVENT_PROCESSING = 'processing';

    public const EVENT_COMPLETED = 'completed';

    public const RESOLUTION_LABELS = [
        'part_replaced' => 'Thay thế part',
        'runner_replaced' => 'Thay thế runner',
        'repaired' => 'Sửa chữa',
        'replaced_product' => 'Thay thế sản phẩm',
        'rejected' => 'Từ chối',
    ];

    public function __construct(
        public int $warrantyId,
        public string $event,
        public ?string $resolution = null,
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
        $message = match ($this->event) {
            self::EVENT_APPROVED => "Yêu cầu bảo hành #{$this->warrantyId} đã được duyệt.",
            self::EVENT_REJECTED => "Yêu cầu bảo hành #{$this->warrantyId} đã bị từ chối.",
            self::EVENT_PROCESSING => "Yêu cầu bảo hành #{$this->warrantyId} đang được xử lý.",
            self::EVENT_COMPLETED => "Yêu cầu bảo hành #{$this->warrantyId} đã hoàn thành"
                .($this->resolution ? ' với kết quả: '.(self::RESOLUTION_LABELS[$this->resolution] ?? $this->resolution).'.' : '.'),
            default => "Yêu cầu bảo hành #{$this->warrantyId} đã được gửi. Shop sẽ xem xét sớm.",
        };

        return [
            'type' => 'warranty',
            'event' => $this->event,
            'warranty_id' => $this->warrantyId,
            'message' => $message,
            // Relative URLs: never depend on APP_URL/host.
            'link' => '/warranties/'.$this->warrantyId,
            'admin_link' => '/admin/warranties/'.$this->warrantyId,
        ];
    }
}
