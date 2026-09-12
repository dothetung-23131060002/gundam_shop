<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\AdminActionLog;
use App\Models\Batch;
use App\Models\Reservation;

trait LogsAdminActions
{
    protected function logAdminAction(
        string $action,
        ?Batch $batch = null,
        ?Reservation $reservation = null,
        ?string $reason = null,
    ): AdminActionLog {
        return AdminActionLog::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'batch_id' => $batch?->id,
            'reservation_id' => $reservation?->id,
            'reason' => $reason ? mb_substr($reason, 0, 500) : null,
        ]);
    }

    protected function inputReason(?string $fallback = null): ?string
    {
        $reason = trim((string) request()->input('reason', ''));

        return $reason !== '' ? $reason : $fallback;
    }
}
