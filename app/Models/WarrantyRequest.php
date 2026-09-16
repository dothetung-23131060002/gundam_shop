<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyRequest extends Model
{
    protected $fillable = [
        'order_id',
        'order_detail_id',
        'user_id',
        'quantity',
        'reason',
        'description',
        'evidence',
        'status',
        'handled_by',
        'resolution',
        'received_at',
        'completed_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'quantity' => 'integer',
        'received_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_APPROVED,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    public const RESOLUTION_PART_REPLACED = 'part_replaced';
    public const RESOLUTION_RUNNER_REPLACED = 'runner_replaced';
    public const RESOLUTION_REPAIRED = 'repaired';
    public const RESOLUTION_REPLACED_PRODUCT = 'replaced_product';
    public const RESOLUTION_REJECTED = 'rejected';

    /**
     * Resolutions allowed for complete(). 'rejected' is intentionally excluded:
     * rejection only goes through reject() (status=rejected), never complete().
     */
    public const RESOLUTIONS = [
        self::RESOLUTION_PART_REPLACED,
        self::RESOLUTION_RUNNER_REPLACED,
        self::RESOLUTION_REPAIRED,
        self::RESOLUTION_REPLACED_PRODUCT,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_REQUESTED,
            self::STATUS_APPROVED,
            self::STATUS_PROCESSING,
        ]);
    }
}
