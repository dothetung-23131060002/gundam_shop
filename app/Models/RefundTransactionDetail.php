<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundTransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_transaction_id',
        'order_detail_id',
        'quantity_refunded',
        'amount_refunded',
    ];

    protected $casts = [
        'quantity_refunded' => 'integer',
        'amount_refunded' => 'decimal:2',
    ];

    public function refund()
    {
        return $this->belongsTo(RefundTransaction::class, 'refund_transaction_id');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }
}
