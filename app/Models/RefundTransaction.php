<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundTransaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const TYPE_DEPOSIT = 'deposit_refund';

    public const TYPE_ORDER = 'order_refund';

    public const REASON_FORFEITED = 'EXPIRED_FORFEITED';

    protected $fillable = [
        'reservation_id',
        'order_id',
        'payment_id',
        'amount',
        'reason',
        'refunded_at',
        'status',
        'type',
        'admin_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function details()
    {
        return $this->hasMany(RefundTransactionDetail::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isForfeiture(): bool
    {
        return $this->reason === self::REASON_FORFEITED;
    }

    /**
     * Refund đã chi tiền thật: completed và không phải forfeiture.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeMonetary($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)
            ->where('reason', '!=', self::REASON_FORFEITED);
    }

    public function scopeForfeitures($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)
            ->where('reason', self::REASON_FORFEITED);
    }
}
