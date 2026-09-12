<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Payment;
use App\Models\RefundTransaction;
use App\Notifications\BatchRefunded;
use App\Notifications\BatchSucceeded;
use Illuminate\Support\Facades\DB;

class BatchService
{
    public function markSuccess(Batch $batch): void
    {
        $batch->update(['status' => 'success']);

        $reservations = $batch->activeReservations()->get();

        foreach ($reservations as $reservation) {
            $reservation->user->notify(new BatchSucceeded($batch, $reservation->quantity));
        }
    }

    public function markFailed(Batch $batch, string $reason = 'Đợt gom không đạt ngưỡng'): void
    {
        DB::transaction(function () use ($batch, $reason) {
            $batch->update(['status' => 'failed']);

            $reservations = $batch->activeReservations()->get();

            foreach ($reservations as $reservation) {
                $depositPaid = $reservation->deposit_paid;

                if ($depositPaid > 0) {
                    RefundTransaction::create([
                        'reservation_id' => $reservation->id,
                        'amount' => $depositPaid,
                        'reason' => "Batch #{$batch->id} failed: {$reason}",
                        'refunded_at' => now(),
                    ]);

                    Payment::create([
                        'user_id' => $reservation->user_id,
                        'reservation_id' => $reservation->id,
                        'amount' => $depositPaid,
                        'type' => 'refund',
                        'note' => "Hoàn cọc tự động do batch #{$batch->id} thất bại",
                    ]);
                }

                $reservation->update([
                    'deposit_paid' => 0,
                    'status' => 'refunded',
                ]);

                $reservation->user->notify(new BatchRefunded($batch, $reservation, $reason));
            }
        });
    }
}
