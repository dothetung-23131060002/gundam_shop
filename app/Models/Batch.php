<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'threshold',
        'deposit_amount',
        'deadline',
        'status',
    ];

    protected $casts = [
        'threshold' => 'integer',
        'deposit_amount' => 'decimal:2',
        'deadline' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function activeReservations()
    {
        return $this->reservations()->where('status', 'reserved');
    }

    public function reservedSlotCount(): int
    {
        // Ưu tiên aggregate đã eager-load (withSum ... as reserved_slots)
        // để tránh N+1 ở các trang listing; null (chưa có reservation) = 0.
        if (array_key_exists('reserved_slots', $this->attributes)) {
            return (int) $this->attributes['reserved_slots'];
        }

        return (int) $this->activeReservations()->sum('quantity');
    }

    public function isFull(): bool
    {
        return $this->reservedSlotCount() >= $this->threshold;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isExpired(): bool
    {
        return $this->deadline->isPast();
    }

    public function progressPercent(): float
    {
        if ($this->threshold <= 0) {
            return 0;
        }

        return round(($this->reservedSlotCount() / $this->threshold) * 100, 1);
    }
}
