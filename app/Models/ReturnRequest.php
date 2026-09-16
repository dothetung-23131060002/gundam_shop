<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    protected $fillable = [
        'order_id',
        'order_detail_id',
        'quantity',
        'reason',
        'runner_condition',
        'refund_reason',
        'status',
        'requested_by',
        'handled_by',
        'received_at',
        'inspection',
        'restocked_qty',
        'refund_transaction_id',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'quantity' => 'integer',
        'restocked_qty' => 'integer',
    ];

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const INSPECTION_RESELLABLE = 'resellable';
    public const INSPECTION_DEFECTIVE = 'defective';
    public const INSPECTION_DISPOSE = 'dispose';

    public const RUNNER_SEALED = 'sealed';
    public const RUNNER_OPENED = 'opened';
    public const RUNNER_UNKNOWN = 'unknown';

    public const STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_APPROVED,
        self::STATUS_RECEIVED,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    public const INSPECTIONS = [
        self::INSPECTION_RESELLABLE,
        self::INSPECTION_DEFECTIVE,
        self::INSPECTION_DISPOSE,
    ];

    public const RUNNER_CONDITIONS = [
        self::RUNNER_SEALED,
        self::RUNNER_OPENED,
        self::RUNNER_UNKNOWN,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function refundTransaction(): BelongsTo
    {
        return $this->belongsTo(RefundTransaction::class);
    }

    public function isRequested(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('requested_by', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_REQUESTED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_REQUESTED, self::STATUS_APPROVED, self::STATUS_RECEIVED]);
    }
}
