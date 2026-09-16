<?php

namespace App\Services;

use App\Exceptions\RefundException;
use App\Models\Batch;
use App\Notifications\BatchRefunded;
use App\Notifications\BatchSucceeded;
use Illuminate\Support\Facades\Log;

class BatchService
{
    public function __construct(private RefundService $refunds)
    {
    }
    public function markSuccess(Batch $batch): void
    {
        $batch->update(['status' => 'success']);

        $reservations = $batch->activeReservations()->get();

        foreach ($reservations as $reservation) {
            $reservation->user->notify(new BatchSucceeded($batch, $reservation->quantity));
        }
    }

    /**
     * H3: xử lý từng reservation độc lập — RefundException ở một reservation
     * (VD chạy chồng khiến trùng refund) thì skip + log, tiếp tục các
     * reservation còn lại, không dừng cả batch. Không transaction bao ngoài.
     */
    public function markFailed(Batch $batch, string $reason = 'Đợt gom không đạt ngưỡng'): void
    {
        $batch->update(['status' => 'failed']);

        $reservations = $batch->activeReservations()->get();

        foreach ($reservations as $reservation) {
            try {
                // Service sở hữu transaction từng reservation (không bọc chung ngoài).
                // complete() ngay để giữ hành vi ledger cũ: refund completed + Payment.
                if ((float) $reservation->deposit_paid > 0) {
                    $refund = $this->refunds->cancelReservationAndRefundDeposit(
                        $reservation->id,
                        RefundService::REASON_BATCH_FAILED
                    );
                    $this->refunds->complete($refund->id);
                } else {
                    $reservation->update([
                        'deposit_paid' => 0,
                        'status' => 'refunded',
                    ]);
                }
            } catch (RefundException $e) {
                Log::warning("markFailed batch #{$batch->id}: skip reservation #{$reservation->id} [{$e->errorCode}] {$e->getMessage()}");
                continue;
            }

            $reservation->user->notify(new BatchRefunded($batch, $reservation->fresh(), $reason));
        }
    }
}
