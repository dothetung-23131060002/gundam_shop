<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchIncident extends Model
{
    use HasFactory;

    public const TYPES = [
        'supplier_shortage',
        'lost_in_transit',
        'damaged',
        'quality_defect',
        'wrong_item',
        'delivery_failed',
        'other',
    ];

    protected $fillable = [
        'batch_id',
        'type',
        'description',
        'admin_id',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
