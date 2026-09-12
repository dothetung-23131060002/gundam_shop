<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminActionLog extends Model
{
    use HasFactory;

    public const ACTIONS = [
        'close_early' => 'Đóng batch sớm',
        'force_success' => 'Buộc thành công (demo)',
        'force_fail' => 'Chốt thất bại',
        'collect_balance' => 'Thu hộ tạo đơn',
    ];

    protected $fillable = [
        'admin_id',
        'action',
        'batch_id',
        'reservation_id',
        'reason',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
