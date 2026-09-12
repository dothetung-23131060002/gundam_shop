<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Services\BatchService;
use Illuminate\Console\Command;

class CheckExpiredBatches extends Command
{
    protected $signature = 'batches:check-expired';

    protected $description = 'Check for expired batches and auto-refund failed reservations';

    public function handle(BatchService $batchService): int
    {
        $expiredBatches = Batch::where('status', 'open')
            ->where('deadline', '<', now())
            ->get();

        $processed = 0;

        foreach ($expiredBatches as $batch) {
            $reservedCount = $batch->activeReservations()->sum('quantity');

            if ($reservedCount >= $batch->threshold) {
                $batchService->markSuccess($batch);
                $this->info("Batch #{$batch->id} succeeded ({$reservedCount}/{$batch->threshold} slots).");
            } else {
                $reason = "Không đạt ngưỡng: {$reservedCount}/{$batch->threshold} slot đã đặt";
                $batchService->markFailed($batch, $reason);
                $this->info("Batch #{$batch->id} failed. Refunded {$batch->activeReservations()->count()} reservation(s).");
            }

            $processed++;
        }

        $this->info("Processed {$processed} expired batch(es).");

        return Command::SUCCESS;
    }
}
