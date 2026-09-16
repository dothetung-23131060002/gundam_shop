<?php

namespace App\Services;

use App\Exceptions\RefundException;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\User;
use App\Notifications\ReturnRequestStatusUpdated;
use Illuminate\Support\Facades\Log;
use App\Models\RefundTransactionDetail;
use App\Models\ReturnRequest;
use App\Models\WarrantyRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(
        protected RefundService $refundService,
    ) {}

    /**
     * Customer tạo return request.
     * Validates ownership, quantity cap, deadline (3 days from delivered_at), runner condition.
     */
    public function request(int $orderId, int $orderDetailId, int $quantity, string $reason, int $userId, ?string $runnerCondition = null): ReturnRequest
    {
        return DB::transaction(function () use ($orderId, $orderDetailId, $quantity, $reason, $userId, $runnerCondition) {
            $order = Order::lockForUpdate()->find($orderId);

            if (! $order) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Order #{$orderId} không tồn tại."
                );
            }

            if ((int) $order->user_id !== $userId) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "Order #{$orderId} không thuộc về bạn."
                );
            }

            if ($order->order_status !== 'completed' || $order->payment_status !== 'paid') {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "Order #{$orderId} chưa hoàn thành hoặc chưa thanh toán."
                );
            }

            if (is_null($order->delivered_at)) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "Order #{$orderId} chưa có thông tin giao hàng. Vui lòng liên hệ admin."
                );
            }

            if (now()->gt($order->delivered_at->copy()->addDays(3))) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "Đã quá hạn 3 ngày kể từ ngày giao hàng ({$order->delivered_at->format('d/m/Y')})."
                );
            }

            if ($runnerCondition && ! in_array($runnerCondition, ReturnRequest::RUNNER_CONDITIONS, true)) {
                throw new RefundException(
                    RefundException::INVALID_REASON,
                    "runner_condition '{$runnerCondition}' không hợp lệ. Phải là: sealed, opened, hoặc unknown."
                );
            }

            if ($runnerCondition === ReturnRequest::RUNNER_OPENED) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    'Runner đã mở không được phép trả hàng. Vui lòng tạo yêu cầu bảo hành nếu còn trong 7 ngày.'
                );
            }

            $detail = OrderDetail::lockForUpdate()->find($orderDetailId);

            if (! $detail || (int) $detail->order_id !== (int) $order->id) {
                throw new RefundException(
                    RefundException::DETAIL_MISMATCH,
                    "OrderDetail #{$orderDetailId} không thuộc Order #{$orderId}."
                );
            }

            if ($quantity <= 0) {
                throw new RefundException(
                    RefundException::DETAIL_MISMATCH,
                    'Số lượng yêu cầu phải lớn hơn 0.'
                );
            }

            // Validate quantity cap: ordered - refunded - pending return
            $orderedQty = (int) $detail->quantity;
            $refundedQty = $this->refundService->refundedQuantityForDetail($orderDetailId);
            $pendingReturnQty = ReturnRequest::where('order_detail_id', $orderDetailId)
                ->whereIn('status', [ReturnRequest::STATUS_REQUESTED, ReturnRequest::STATUS_APPROVED, ReturnRequest::STATUS_RECEIVED])
                ->sum('quantity');
            // Also check active warranty quantity that consumes same order_detail
            $pendingWarrantyQty = WarrantyRequest::where('order_detail_id', $orderDetailId)
                ->active()
                ->sum('quantity');
            $remaining = $orderedQty - $refundedQty - (int) $pendingReturnQty - (int) $pendingWarrantyQty;

            if ($quantity > $remaining) {
                throw new RefundException(
                    RefundException::QUANTITY_EXCEEDED,
                    "OrderDetail #{$orderDetailId}: đã đặt {$orderedQty}, đã hoàn {$refundedQty}, đang chờ trả {$pendingReturnQty}, đang chờ bảo hành {$pendingWarrantyQty}, chỉ còn được yêu cầu {$remaining}, yêu cầu {$quantity}."
                );
            }

            $returnRequest = ReturnRequest::create([
                'order_id' => $order->id,
                'order_detail_id' => $detail->id,
                'quantity' => $quantity,
                'reason' => $reason,
                'runner_condition' => $runnerCondition,
                'status' => ReturnRequest::STATUS_REQUESTED,
                'requested_by' => $userId,
                'handled_by' => null,
                'received_at' => null,
                'inspection' => null,
                'restocked_qty' => 0,
                'refund_transaction_id' => null,
            ]);

            $fresh = $returnRequest->fresh(['orderDetail', 'requestedBy']);
            $fresh->requestedBy?->notify(new ReturnRequestStatusUpdated($fresh->id, ReturnRequestStatusUpdated::EVENT_REQUESTED));
            self::notifyAdminsOfNewRequest($fresh->id, (int) $userId);

            return $fresh;
        });
    }

    /**
     * Admin approve return request.
     * Approved KHÔNG đồng nghĩa refund đã tạo.
     */
    public function approve(int $returnRequestId, int $adminId): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequestId, $adminId) {
            $return = ReturnRequest::lockForUpdate()->find($returnRequestId);

            if (! $return) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "ReturnRequest #{$returnRequestId} không tồn tại."
                );
            }

            if ($return->status !== ReturnRequest::STATUS_REQUESTED) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "ReturnRequest #{$returnRequestId} đang ở trạng thái '{$return->status}', chỉ 'requested' mới được approve."
                );
            }

            $return->update([
                'status' => ReturnRequest::STATUS_APPROVED,
                'handled_by' => $adminId,
            ]);

            $fresh = $return->fresh(['orderDetail', 'requestedBy', 'handledBy']);
            $fresh->requestedBy?->notify(new ReturnRequestStatusUpdated($fresh->id, ReturnRequestStatusUpdated::EVENT_APPROVED));

            return $fresh;
        });
    }

    /**
     * Admin reject return request.
     */
    public function reject(int $returnRequestId, int $adminId): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequestId, $adminId) {
            $return = ReturnRequest::lockForUpdate()->find($returnRequestId);

            if (! $return) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "ReturnRequest #{$returnRequestId} không tồn tại."
                );
            }

            if ($return->status !== ReturnRequest::STATUS_REQUESTED && $return->status !== ReturnRequest::STATUS_APPROVED) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "ReturnRequest #{$returnRequestId} đang ở trạng thái '{$return->status}', không thể reject."
                );
            }

            $return->update([
                'status' => ReturnRequest::STATUS_REJECTED,
                'handled_by' => $adminId,
            ]);

            $fresh = $return->fresh(['orderDetail', 'requestedBy', 'handledBy']);
            $fresh->requestedBy?->notify(new ReturnRequestStatusUpdated($fresh->id, ReturnRequestStatusUpdated::EVENT_REJECTED));

            return $fresh;
        });
    }

    /**
     * Admin mark return as received (hàng đã về kho).
     */
    public function receive(int $returnRequestId, int $adminId): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequestId, $adminId) {
            $return = ReturnRequest::lockForUpdate()->find($returnRequestId);

            if (! $return) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "ReturnRequest #{$returnRequestId} không tồn tại."
                );
            }

            if ($return->status !== ReturnRequest::STATUS_APPROVED) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "ReturnRequest #{$returnRequestId} đang ở trạng thái '{$return->status}', chỉ 'approved' mới được receive."
                );
            }

            $return->update([
                'status' => ReturnRequest::STATUS_RECEIVED,
                'received_at' => now(),
                'handled_by' => $adminId,
            ]);

            $fresh = $return->fresh(['orderDetail', 'requestedBy', 'handledBy']);
            $fresh->requestedBy?->notify(new ReturnRequestStatusUpdated($fresh->id, ReturnRequestStatusUpdated::EVENT_RECEIVED));

            return $fresh;
        });
    }

    /**
     * Admin inspect received return.
     * inspection: resellable | defective | dispose
     */
    public function inspect(int $returnRequestId, string $inspection, int $adminId): ReturnRequest
    {
        $validInspections = ReturnRequest::INSPECTIONS;

        if (! in_array($inspection, $validInspections, true)) {
            throw new RefundException(
                RefundException::INVALID_REASON,
                "Giá trị inspection '{$inspection}' không hợp lệ."
            );
        }

        return DB::transaction(function () use ($returnRequestId, $inspection, $adminId) {
            $return = ReturnRequest::lockForUpdate()->find($returnRequestId);

            if (! $return) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "ReturnRequest #{$returnRequestId} không tồn tại."
                );
            }

            if ($return->status !== ReturnRequest::STATUS_RECEIVED) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "ReturnRequest #{$returnRequestId} đang ở trạng thái '{$return->status}', chỉ 'received' mới được inspect."
                );
            }

            if ($return->inspection !== null) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "ReturnRequest #{$returnRequestId} đã được inspect ('{$return->inspection}'), không thể inspect lại."
                );
            }

            $return->update([
                'inspection' => $inspection,
                'handled_by' => $adminId,
            ]);

            return $return->fresh(['orderDetail', 'requestedBy', 'handledBy']);
        });
    }

    /**
     * Complete return WITHOUT receiving goods (case không cần thu hồi).
     * Tạo refund + complete ngay.
     */
    public function completeWithoutReturn(int $returnRequestId, int $adminId, ?string $refundReason = null): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequestId, $adminId, $refundReason) {
            $return = ReturnRequest::lockForUpdate()->find($returnRequestId);

            if (! $return) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "ReturnRequest #{$returnRequestId} không tồn tại."
                );
            }

            if ($return->status !== ReturnRequest::STATUS_APPROVED) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "ReturnRequest #{$returnRequestId} đang ở trạng thái '{$return->status}', chỉ 'approved' mới được complete (no-return)."
                );
            }

            if ($refundReason !== null) {
                $this->assertOrderReasonValue($refundReason);
                $return->update(['refund_reason' => $refundReason]);
            }

            $this->assertValidRefundReason($return);

            // Re-check quantity cap within transaction
            $this->assertQuantityCap($return);

            // Create refund via RefundService
            $refund = $this->refundService->createOrderRefund(
                $return->order_id,
                [['order_detail_id' => $return->order_detail_id, 'quantity' => $return->quantity]],
                $return->refund_reason,
                $adminId
            );

            $this->refundService->complete($refund->id, $adminId);

            $return->update([
                'status' => ReturnRequest::STATUS_COMPLETED,
                'refund_transaction_id' => $refund->id,
                'handled_by' => $adminId,
            ]);

            $fresh = $return->fresh(['orderDetail', 'requestedBy', 'handledBy', 'refundTransaction']);
            $fresh->requestedBy?->notify(new ReturnRequestStatusUpdated(
                $fresh->id,
                ReturnRequestStatusUpdated::EVENT_COMPLETED,
                $fresh->refundTransaction ? (float) $fresh->refundTransaction->amount : null,
            ));

            return $fresh;
        });
    }

    /**
     * Complete return AFTER inspection + restock (case có thu hồi).
     * Nếu inspection = resellable + restocked_qty > 0 → increment stock.
     */
    public function complete(int $returnRequestId, int $restockedQty, int $adminId, ?string $refundReason = null): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequestId, $restockedQty, $adminId, $refundReason) {
            $return = ReturnRequest::lockForUpdate()->find($returnRequestId);

            if (! $return) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "ReturnRequest #{$returnRequestId} không tồn tại."
                );
            }

            if ($return->status !== ReturnRequest::STATUS_RECEIVED) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "ReturnRequest #{$returnRequestId} đang ở trạng thái '{$return->status}', chỉ 'received' mới được complete."
                );
            }

            if (! $return->inspection) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "ReturnRequest #{$returnRequestId} chưa được inspect."
                );
            }

            // Validate restocked_qty guard
            if ($restockedQty < 0) {
                throw new RefundException(
                    RefundException::DETAIL_MISMATCH,
                    'restocked_qty không được âm.'
                );
            }

            if ($restockedQty > $return->quantity) {
                throw new RefundException(
                    RefundException::QUANTITY_EXCEEDED,
                    "restocked_qty ({$restockedQty}) không được vượt quantity return ({$return->quantity})."
                );
            }

            if ($refundReason !== null) {
                $this->assertOrderReasonValue($refundReason);
                $return->update(['refund_reason' => $refundReason]);
            }

            $this->assertValidRefundReason($return);

            // Re-check quantity cap within transaction
            $this->assertQuantityCap($return);

            // Create refund via RefundService
            $refund = $this->refundService->createOrderRefund(
                $return->order_id,
                [['order_detail_id' => $return->order_detail_id, 'quantity' => $return->quantity]],
                $return->refund_reason,
                $adminId
            );

            $this->refundService->complete($refund->id, $adminId);

            $return->update([
                'status' => ReturnRequest::STATUS_COMPLETED,
                'restocked_qty' => $restockedQty,
                'refund_transaction_id' => $refund->id,
                'handled_by' => $adminId,
            ]);

            $completedFresh = $return->fresh(['orderDetail', 'requestedBy', 'handledBy', 'refundTransaction']);
            $completedFresh->requestedBy?->notify(new ReturnRequestStatusUpdated(
                $completedFresh->id,
                ReturnRequestStatusUpdated::EVENT_COMPLETED,
                $completedFresh->refundTransaction ? (float) $completedFresh->refundTransaction->amount : null,
            ));

            // Restock: only if resellable AND restocked_qty > 0 AND cart order
            // (batch orders never deducted stock at purchase, mirroring
            // RefundService::cancelAndRefundOrder "Hồi kho IFF đơn cart").
            // Product is the leaf of the GLOBAL lock order
            // (Batch → Reservation → Order → OrderDetail → RefundTransaction → Product),
            // locked here after the refund chain — never in reverse.
            if ($return->inspection === ReturnRequest::INSPECTION_RESELLABLE && $restockedQty > 0) {
                $stockOrder = Order::lockForUpdate()->find($return->order_id);

                if ($stockOrder && $stockOrder->batch_id === null) {
                    $stockDetail = OrderDetail::lockForUpdate()->find($return->order_detail_id);

                    if ($stockDetail && $stockDetail->product_id) {
                        $stockProduct = Product::lockForUpdate()->find($stockDetail->product_id);
                        $stockProduct?->increment('quantity', $restockedQty);
                    }
                }
            }

            return $completedFresh;
        });
    }

    /**
     * Customer cancel自己的return request trước khi hoàn thành.
     */
    public function cancel(int $returnRequestId, int $userId): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequestId, $userId) {
            $return = ReturnRequest::lockForUpdate()->find($returnRequestId);

            if (! $return) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "ReturnRequest #{$returnRequestId} không tồn tại."
                );
            }

            if ((int) $return->requested_by !== $userId) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "ReturnRequest #{$returnRequestId} không thuộc về bạn."
                );
            }

            if (! in_array($return->status, [ReturnRequest::STATUS_REQUESTED, ReturnRequest::STATUS_APPROVED], true)) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "ReturnRequest #{$returnRequestId} đang ở trạng thái '{$return->status}', không thể hủy."
                );
            }

            $return->update([
                'status' => ReturnRequest::STATUS_CANCELLED,
            ]);

            return $return->fresh(['orderDetail', 'requestedBy']);
        });
    }

    /**
     * Notify all admins of a new customer request (database-only).
     * Never fails the request flow.
     */
    protected static function notifyAdminsOfNewRequest(int $returnId, int $excludeUserId): void
    {
        try {
            User::where('role', 'admin')->where('id', '!=', $excludeUserId)->get()->each(
                fn (User $admin) => $admin->notify(new ReturnRequestStatusUpdated($returnId, ReturnRequestStatusUpdated::EVENT_REQUESTED))
            );
        } catch (\Throwable $e) {
            Log::warning('Return request admin notify failed', ['return_id' => $returnId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Validate refund_reason is set and is a valid RefundService order reason.
     * Admin PHẤI chọn refund_reason hợp lệ trước khi hoàn tiền.
     *
     * @throws RefundException
     */
    protected function assertValidRefundReason(ReturnRequest $return): void
    {
        if (empty($return->refund_reason)) {
            throw new RefundException(
                RefundException::INVALID_REASON,
                'refund_reason là bắt buộc. Admin phải chọn lý do hoàn tiền hợp lệ trước khi hoàn tiền.'
            );
        }

        $this->assertOrderReasonValue($return->refund_reason);
    }

    /**
     * Validate một refund reason string thuộc RefundService::ORDER_REASONS.
     *
     * @throws RefundException
     */
    protected function assertOrderReasonValue(string $reason): void
    {
        if (! in_array($reason, RefundService::ORDER_REASONS, true)) {
            throw new RefundException(
                RefundException::INVALID_REASON,
                "refund_reason '{$reason}' không hợp lệ. Phải là một trong: " . implode(', ', RefundService::ORDER_REASONS) . '.'
            );
        }
    }

    /**
     * Re-check quantity cap: ordered - refunded - pending return - pending warranty (excluding self) >= quantity.
     */
    protected function assertQuantityCap(ReturnRequest $return): void
    {
        $detail = OrderDetail::find($return->order_detail_id);

        if (! $detail) {
            throw new RefundException(
                RefundException::SOURCE_NOT_FOUND,
                "OrderDetail #{$return->order_detail_id} không còn tồn tại."
            );
        }

        $orderedQty = (int) $detail->quantity;
        $refundedQty = $this->refundService->refundedQuantityForDetail($return->order_detail_id);

        // Pending return excluding self
        $pendingReturnQty = ReturnRequest::where('order_detail_id', $return->order_detail_id)
            ->where('id', '!=', $return->id)
            ->whereIn('status', [ReturnRequest::STATUS_REQUESTED, ReturnRequest::STATUS_APPROVED, ReturnRequest::STATUS_RECEIVED])
            ->sum('quantity');

        // Active warranty consuming same order_detail
        $pendingWarrantyQty = WarrantyRequest::where('order_detail_id', $return->order_detail_id)
            ->active()
            ->sum('quantity');

        $remaining = $orderedQty - $refundedQty - (int) $pendingReturnQty - (int) $pendingWarrantyQty;

        if ((int) $return->quantity > $remaining) {
            throw new RefundException(
                RefundException::QUANTITY_EXCEEDED,
                "OrderDetail #{$return->order_detail_id}: đã đặt {$orderedQty}, đã hoàn {$refundedQty}, đang chờ trả {$pendingReturnQty}, đang chờ bảo hành {$pendingWarrantyQty}, không đủ cho return {$return->quantity} (còn {$remaining})."
            );
        }
    }
}
