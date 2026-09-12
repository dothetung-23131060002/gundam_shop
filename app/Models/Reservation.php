<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'batch_id',
        'quantity',
        'deposit_paid',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'deposit_paid' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function refundTransactions()
    {
        return $this->hasMany(RefundTransaction::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCancellable(): bool
    {
        return $this->status === 'reserved' && $this->batch->isOpen() && ! $this->batch->isExpired();
    }

    public function totalDepositAmount(): float
    {
        return $this->batch->deposit_amount * $this->quantity;
    }

    public function totalProductPrice(): float
    {
        return $this->batch->product->price * $this->quantity;
    }

    public function balanceAmount(): float
    {
        return max(0, $this->totalProductPrice() - $this->deposit_paid);
    }

    public function needsPayment(): bool
    {
        return $this->batch->status === 'success'
            && $this->status === 'reserved'
            && $this->balanceAmount() > 0;
    }
}
