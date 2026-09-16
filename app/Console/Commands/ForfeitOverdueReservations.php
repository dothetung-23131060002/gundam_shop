<?php

namespace App\Console\Commands;

use App\Exceptions\RefundException;
use App\Models\Reservation;
use App\Notifications\DepositForfeited;
use App\Services\RefundService;
use Illuminate\Console\Command;

class ForfeitOverdueReservations extends Command
{
    protected $signature = 'reservations:forfeit-overdue
                            {--dry-run : Chỉ liệt kê, tuyệt đối không thay đổi DB và không gửi notify}
                            {--grace-days= : Ghi đè số ngày ân hạn (mặc định lấy Setting balance_forfeit_grace_days)}';

    protected $description = 'Tịch thu cọc các reservation batch-success quá hạn chưa trả balance (EXPIRED_FORFEITED)';

    public function handle(RefundService $refunds): int
    {
        $grace = $this->option('grace-days') !== null
            ? max(0, (int) $this->option('grace-days'))
            : null;

        $cutoff = $refunds->forfeitureOverdueCutoff($grace);
        $dryRun = (bool) $this->option('dry-run');

        $candidates = Reservation::where('status', 'reserved')
            ->where('deposit_paid', '>', 0)
            ->where('updated_at', '<=', $cutoff)
            ->whereHas('batch', function ($q) {
                $q->where('status', 'success');
            })
            ->with(['batch', 'user'])
            ->orderBy('id')
            ->get();

        if ($dryRun) {
            $this->info("DRY-RUN: {$candidates->count()} reservation đủ điều kiện thời gian (cutoff {$cutoff->format('Y-m-d H:i')}, grace {$refunds->forfeitureGraceDays($grace)} ngày). Không thay đổi DB.");

            foreach ($candidates as $reservation) {
                $this->line(" - reservation #{$reservation->id} (batch #{$reservation->batch_id}, cọc {$reservation->deposit_paid})");
            }

            return Command::SUCCESS;
        }

        $forfeited = 0;
        $skipped = 0;

        foreach ($candidates as $reservation) {
            try {
                // Service sở hữu transaction + re-check toàn bộ điều kiện
                // (trạng thái, batch success, live order, cọc còn lại, trùng).
                $record = $refunds->recordForfeiture($reservation->id);
            } catch (RefundException $e) {
                // Một reservation lỗi (đã paid/có đơn sống/trùng...) thì skip
                // và tiếp tục các reservation còn lại — không dừng cả lô.
                $skipped++;
                $this->warn("Skip reservation #{$reservation->id}: [{$e->errorCode}] {$e->getMessage()}");
                continue;
            }

            $forfeited++;

            // Notify SAU khi transaction đã commit (service đã return).
            try {
                $reservation->user->notify(new DepositForfeited(
                    $reservation->batch,
                    $reservation->fresh(),
                    (float) $record->amount
                ));
            } catch (\Throwable $e) {
                // Notification failure must not fail the forfeiture.
                $this->warn("Forfeited reservation #{$reservation->id} nhưng gửi notify thất bại.");
            }

            $this->info("Forfeited reservation #{$reservation->id}: {$record->amount}.");
        }

        $this->info("Xong: {$forfeited} forfeited, {$skipped} skipped.");

        return Command::SUCCESS;
    }
}
