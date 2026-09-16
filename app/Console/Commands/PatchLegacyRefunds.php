<?php

namespace App\Console\Commands;

use App\Models\RefundTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * H2 — one-time data patch cho refund rows legacy (tạo trước phase mở rộng
 * bảng, chỉ có reservation_id/amount/reason/refunded_at, thiếu status/type).
 *
 * Mặc định là DRY-RUN (chỉ report, KHÔNG mutate). Mutation chỉ khi truyền
 * --apply EXPLICIT. Tuyệt đối không tự chạy trên production/dev khi chưa duyệt.
 *
 * Legacy row = status != 'completed' NHƯNG refunded_at IS NOT NULL
 * (tiền đã chi thật theo flow cũ, nay bị loại khỏi monetary totals).
 */
class PatchLegacyRefunds extends Command
{
    protected $signature = 'refunds:patch-legacy
                            {--apply : Thực sự patch (không có flag = dry-run, chỉ báo cáo)}';

    protected $description = 'Dry-run/report (mặc định) hoặc patch refund legacy thiếu status completed';

    public function handle(): int
    {
        $query = RefundTransaction::where('status', '!=', RefundTransaction::STATUS_COMPLETED)
            ->whereNotNull('refunded_at');

        $count = (clone $query)->count();

        if (! $this->option('apply')) {
            $this->info("DRY-RUN: {$count} legacy refund row(s) cần patch (status != completed nhưng refunded_at đã set). Không thay đổi DB.");

            if ($count > 0) {
                $rows = (clone $query)->orderBy('id')->limit(50)
                    ->get(['id', 'reservation_id', 'order_id', 'amount', 'reason', 'status', 'type', 'refunded_at']);

                foreach ($rows as $row) {
                    $this->line(" - #{$row->id} amount={$row->amount} reason={$row->reason} status={$row->status} type={$row->type} refunded_at={$row->refunded_at}");
                }

                if ($count > 50) {
                    $this->line(' ... và '.($count - 50).' rows nữa (giới hạn hiển thị 50).');
                }
            }

            return Command::SUCCESS;
        }

        $before = (clone $query)->count();

        DB::transaction(function () use ($query) {
            // Chỉ chạm đúng legacy rows; rows hợp lệ hiện tại không đổi.
            // Type legacy luôn là deposit (flow cũ chỉ hoàn cọc) — giữ nguyên type.
            (clone $query)->update(['status' => RefundTransaction::STATUS_COMPLETED]);
        });

        $after = RefundTransaction::where('status', '!=', RefundTransaction::STATUS_COMPLETED)
            ->whereNotNull('refunded_at')
            ->count();

        $this->info("APPLY xong: {$before} → {$after} rows còn lại cần patch.");

        return Command::SUCCESS;
    }
}
