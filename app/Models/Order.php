<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'batch_id',
        'reservation_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'total_amount',
        'payment_method',
        'payment_status',
        'order_status',
        'delivered_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function refunds()
    {
        return $this->hasMany(RefundTransaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Payment status flow (xác thực thủ công, không webhook)
    |--------------------------------------------------------------------------
    |
    | pending_payment → awaiting_confirmation → paid (+ payment_rejected nhánh từ chối)
    | Khách chỉ được pending_payment/payment_rejected → awaiting_confirmation.
    | Chỉ admin được awaiting_confirmation → paid / payment_rejected.
    | 'unpaid' là legacy (COD + đơn cũ): giữ nguyên, chỉ map label hiển thị.
    |
    */

    public const PAY_UNPAID = 'unpaid';

    public const PAY_PENDING = 'pending_payment';

    public const PAY_AWAITING = 'awaiting_confirmation';

    public const PAY_PAID = 'paid';

    public const PAY_REJECTED = 'payment_rejected';

    public const PAYMENT_LABELS = [
        'unpaid' => 'Chờ thanh toán',
        'pending_payment' => 'Chờ thanh toán',
        'awaiting_confirmation' => 'Đang chờ shop xác nhận thanh toán',
        'paid' => 'Thanh toán thành công',
        'payment_rejected' => 'Thanh toán chưa được xác nhận',
    ];
    public const PAYMENT_TRANSITIONS = [
        // 'unpaid' là legacy (đơn QR tạo trước deploy): cho phép như pending.
        'unpaid' => ['awaiting_confirmation'],
        'pending_payment' => ['awaiting_confirmation'],
        'awaiting_confirmation' => ['paid', 'payment_rejected'],
        'payment_rejected' => ['awaiting_confirmation'],
    ];

    /**
     * Ma trận order_status HỢP LỆ duy nhất (H5 — mọi mutation trạng thái
     * đơn đều phải qua canTransitOrderTo, không rải logic).
     * Terminal: completed và cancelled không quay ngược, không resurrect.
     */
    public const ORDER_TRANSITIONS = [
        'pending' => ['confirmed', 'shipping', 'completed', 'cancelled'],
        'confirmed' => ['shipping', 'completed', 'cancelled'],
        'shipping' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public static function paymentLabel(string $status): string
    {
        return self::PAYMENT_LABELS[$status] ?? $status;
    }

    public function canTransitPaymentTo(string $to): bool
    {
        return in_array($to, self::PAYMENT_TRANSITIONS[$this->payment_status] ?? [], true);
    }

    public function canTransitOrderTo(string $to): bool
    {
        return in_array($to, self::ORDER_TRANSITIONS[$this->order_status] ?? [], true);
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this->payment_status === self::PAY_AWAITING;
    }

    /**
     * Đơn đã thu tiền thật — nguồn duy nhất cho Gross Collected.
     * Cố ý KHÔNG lọc order_status: paid + cancelled vẫn tính gross
     * (phần hoàn nằm ở refund layer, xem RefundTransaction::scopeMonetary).
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAY_PAID);
    }
}
