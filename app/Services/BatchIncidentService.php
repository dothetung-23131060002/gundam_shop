<?php

namespace App\Services;

use App\Exceptions\RefundException;
use App\Models\Batch;
use App\Models\BatchIncident;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\RefundTransaction;
use App\Notifications\IncidentRefundIssued;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration layer cho batch incident:
 * Controller → BatchIncidentService → RefundService (+ BatchService).
 * Controller KHÔNG tự xử lý tiền/inventory.
 *
 * OWNERSHIP (chống double-process reserved):
 * - Reserved reservations thuộc về flow markFailed HIỆN CÓ. Khi batch còn
 *   open, resolveBatchShortage() ủy quyền toàn bộ reserved cho
 *   BatchService::markFailed() và TUYỆT ĐỐI không refund/cancel reserved
 *   lần nữa. Service này chỉ xử lý phần converted/live order mà markFailed
 *   bỏ sót.
 * - Batch đã failed/success: bỏ qua markFailed (reserved đã được xử lý hoặc
 *   không còn), chỉ xử lý converted/live orders.
 *
 * KHÔNG có rule dedup "batch+type+N giờ": incident là event, cùng type khác
 * case là hợp lệ. Idempotency nằm ở money layer (caps, UNIQUE detail,
 * guards của RefundService) + resolve skip gracefully khi đã xử lý xong.
 */
class BatchIncidentService
{
    /**
     * Incident type → refund reason (toàn bộ đã có trong ORDER_REASONS,
     * không thêm reason mới).
     */
    public const TYPE_REASON = [
        'supplier_shortage' => RefundService::REASON_SUPPLIER_SHORTAGE,
        'lost_in_transit' => RefundService::REASON_LOST_IN_TRANSIT,
        'damaged' => RefundService::REASON_DAMAGED_TRANSIT,
        'quality_defect' => RefundService::REASON_MANUFACTURER_DEFECT,
        'wrong_item' => RefundService::REASON_WRONG_ITEM,
        'delivery_failed' => RefundService::REASON_DELIVERY_FAILED,
        'other' => RefundService::REASON_ADMIN_CANCEL,
    ];

    public function __construct(
        private RefundService $refunds,
        private BatchService $batches,
    ) {}

    /**
     * Ghi nhận incident (event thuần túy, chưa đụng tiền).
     *
     * @throws RefundException
     */
    public function record(int $batchId, string $type, ?string $description, ?int $adminId = null): BatchIncident
    {
        if (! in_array($type, BatchIncident::TYPES, true)) {
            throw new RefundException(
                RefundException::INVALID_REASON,
                "Incident type '{$type}' không hợp lệ."
            );
        }

        return DB::transaction(function () use ($batchId, $type, $description, $adminId) {
            $batch = Batch::lockForUpdate()->find($batchId);

            if (! $batch) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Batch #{$batchId} không tồn tại."
                );
            }

            return BatchIncident::create([
                'batch_id' => $batch->id,
                'type' => $type,
                'description' => $description,
                'admin_id' => $adminId,
            ]);
        });
    }

    /**
     * Resolve incident cho MỘT order.
     * - Order completed: incident refund (giữ nguyên status), items rỗng →
     *   hoàn full phần còn lại.
     * - Order chưa completed + có items: incident refund đúng các dòng,
     *   GIỮ NGUYÊN status (đơn vẫn tiếp tục giao/phục vụ phần còn lại).
     * - Order chưa completed + không items: cancel flow (unpaid → cancel 0đ;
     *   paid → cancel + pending rồi complete ngay).
     * Tiền đi ngay (complete) để incident resolution khép kín; caller nào cần
     * 2-phase tách bước thì gọi RefundService trực tiếp.
     *
     * @param  array  $items  [['order_detail_id' => int, 'quantity' => int], ...]
     * @return ?RefundTransaction  null khi cancel 0đ (không thu tiền).
     *
     * @throws RefundException
     */
    public function resolveOrderRefund(int $incidentId, int $orderId, array $items = [], ?int $adminId = null): ?RefundTransaction
    {
        $incident = BatchIncident::find($incidentId);

        if (! $incident) {
            throw new RefundException(
                RefundException::SOURCE_NOT_FOUND,
                "Incident #{$incidentId} không tồn tại."
            );
        }

        $reason = self::TYPE_REASON[$incident->type] ?? RefundService::REASON_ADMIN_CANCEL;

        $order = Order::find($orderId);

        if (! $order) {
            throw new RefundException(
                RefundException::SOURCE_NOT_FOUND,
                "Order #{$orderId} không tồn tại."
            );
        }

        if ((int) $order->batch_id !== (int) $incident->batch_id) {
            throw new RefundException(
                RefundException::DETAIL_MISMATCH,
                "Order #{$order->id} không thuộc batch #{$incident->batch_id} của incident."
            );
        }

        if ($order->order_status === 'completed' || ! empty($items)) {
            if (empty($items)) {
                $items = $this->fullRemainingItems($order->id);
            }

            if (empty($items)) {
                throw new RefundException(
                    RefundException::NOTHING_TO_REFUND,
                    "Order #{$order->id} không còn gì để hoàn."
                );
            }

            $refund = $this->refunds->createOrderRefund($order->id, $items, $reason, $adminId);
            $done = $this->refunds->complete($refund->id, $adminId);
            $this->notifyRefund($order->fresh(), $done);

            return $done;
        }

        $result = $this->refunds->cancelAndRefundOrder($order->id, $items, $reason, $adminId);
        $fresh = $result['order'];

        if ($result['refund']) {
            $done = $this->refunds->complete($result['refund']->id, $adminId);
            $this->notifyRefund($fresh, $done);

            return $done;
        }

        $fresh->user->notify(new OrderStatusChanged($fresh, $order->order_status, 'cancelled'));

        return null;
    }

    /**
     * Resolve shortage cả đợt (chỉ cho type supplier_shortage).
     * - Batch open: reserved → ủy quyền DUY NHẤT cho markFailed; sau đó xử lý
     *   converted/live orders còn sót.
     * - Batch đã failed/success: chỉ xử lý converted/live orders.
     * - converted + unpaid (claim sống) → cancel 0đ.
     * - converted/completed + paid → incident refund full phần còn lại.
     * Mỗi order lỗi thì skip (ghi nhận) — không dừng cả đợt. Chạy lại không
     * sinh tiền mới (guards + caps của RefundService chặn).
     *
     * @return array{refunded: array, cancelled: array, skipped: array}
     *
     * @throws RefundException
     */
    public function resolveBatchShortage(int $incidentId, ?int $adminId = null): array
    {
        $incident = BatchIncident::find($incidentId);

        if (! $incident) {
            throw new RefundException(
                RefundException::SOURCE_NOT_FOUND,
                "Incident #{$incidentId} không tồn tại."
            );
        }

        if ($incident->type !== 'supplier_shortage') {
            throw new RefundException(
                RefundException::INVALID_REASON,
                'Resolve cả đợt chỉ áp dụng cho supplier_shortage; các type khác resolve từng order.'
            );
        }

        $batch = Batch::find($incident->batch_id);

        if (! $batch) {
            throw new RefundException(
                RefundException::SOURCE_NOT_FOUND,
                "Batch #{$incident->batch_id} không tồn tại."
            );
        }

        // SINGLE OWNER của reserved: chỉ markFailed khi batch còn open.
        if ($batch->status === 'open') {
            $this->batches->markFailed($batch->fresh(), 'NCC thiếu hàng (incident #'.$incident->id.')');
        }

        $summary = ['refunded' => [], 'cancelled' => [], 'skipped' => []];

        $orders = Order::where('batch_id', $batch->id)
            ->where('order_status', '!=', 'cancelled')
            ->orderBy('id')
            ->get();

        foreach ($orders as $order) {
            try {
                // completed → incident refund giữ status; chưa completed →
                // cancel (unpaid: 0đ; paid: + pending rồi complete ngay).
                // Hết tiền để hoàn (đã full refund trước đó) → guard ném,
                // catch bên dưới skip — chạy lại không sinh tiền mới.
                $done = $this->resolveOrderRefund($incident->id, $order->id, [], $adminId);
                $summary[$done ? 'refunded' : 'cancelled'][] = $order->id;
            } catch (RefundException $e) {
                $summary['skipped'][] = ['order_id' => $order->id, 'code' => $e->errorCode];
            }
        }

        return $summary;
    }

    /**
     * Dựng items hoàn full phần còn lại (dùng khi incident không chỉ định items).
     */
    protected function fullRemainingItems(int $orderId): array
    {
        $details = OrderDetail::where('order_id', $orderId)->orderBy('id')->get();
        $items = [];

        foreach ($details as $detail) {
            $remaining = (int) $detail->quantity - $this->refunds->refundedQuantityForDetail($detail->id);

            if ($remaining > 0) {
                $items[] = ['order_detail_id' => $detail->id, 'quantity' => $remaining];
            }
        }

        return $items;
    }

    protected function notifyRefund(Order $order, RefundTransaction $refund): void
    {
        try {
            $order->user->notify(new IncidentRefundIssued($order, $refund));
        } catch (\Throwable $e) {
            // Notification failure must not fail the resolution.
        }
    }
}
