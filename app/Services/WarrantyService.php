<?php

namespace App\Services;

use App\Exceptions\RefundException;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\WarrantyRequest;
use App\Notifications\WarrantyRequestStatusUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WarrantyService
{
    /**
     * Customer tạo warranty request.
     * Validates ownership, deadline (7 days from delivered_at), evidence, quantity cap.
     */
    public function request(
        int $orderId,
        int $orderDetailId,
        int $quantity,
        string $reason,
        ?string $description,
        int $userId,
        ?array $evidencePaths = null,
    ): WarrantyRequest {
        return DB::transaction(function () use ($orderId, $orderDetailId, $quantity, $reason, $description, $userId, $evidencePaths) {
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

            if (now()->gt($order->delivered_at->copy()->addDays(7))) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "Đã quá hạn 7 ngày kể từ ngày giao hàng ({$order->delivered_at->format('d/m/Y')})."
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

            // Validate quantity cap: ordered - refunded - pending return - pending warranty
            $orderedQty = (int) $detail->quantity;
            $refundedQty = (new RefundService)->refundedQuantityForDetail($orderDetailId);
            $pendingReturnQty = ReturnRequest::where('order_detail_id', $orderDetailId)
                ->whereIn('status', [ReturnRequest::STATUS_REQUESTED, ReturnRequest::STATUS_APPROVED, ReturnRequest::STATUS_RECEIVED])
                ->sum('quantity');
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

            // Evidence: at least 1 image required
            if (empty($evidencePaths) || count($evidencePaths) < 1) {
                throw new RefundException(
                    RefundException::INVALID_REASON,
                    'Bắt buộc phải có ít nhất 1 ảnh bằng chứng.'
                );
            }

            if (count($evidencePaths) > 5) {
                throw new RefundException(
                    RefundException::INVALID_REASON,
                    'Tối đa 5 ảnh bằng chứng.'
                );
            }

            $warranty = WarrantyRequest::create([
                'order_id' => $order->id,
                'order_detail_id' => $detail->id,
                'user_id' => $userId,
                'quantity' => $quantity,
                'reason' => $reason,
                'description' => $description,
                'evidence' => $evidencePaths,
                'status' => WarrantyRequest::STATUS_REQUESTED,
                'handled_by' => null,
                'resolution' => null,
                'received_at' => null,
                'completed_at' => null,
            ]);

            $fresh = $warranty->fresh(['orderDetail', 'user']);
            $fresh->user?->notify(new WarrantyRequestStatusUpdated($fresh->id, WarrantyRequestStatusUpdated::EVENT_REQUESTED));

            try {
                User::where('role', 'admin')->where('id', '!=', $userId)->get()->each(
                    fn (User $admin) => $admin->notify(new WarrantyRequestStatusUpdated($fresh->id, WarrantyRequestStatusUpdated::EVENT_REQUESTED))
                );
            } catch (\Throwable $e) {
                Log::warning('Warranty request admin notify failed', ['warranty_id' => $fresh->id, 'error' => $e->getMessage()]);
            }

            return $fresh;
        });
    }

    /**
     * Admin approve warranty request.
     */
    public function approve(int $warrantyId, int $adminId): WarrantyRequest
    {
        return DB::transaction(function () use ($warrantyId, $adminId) {
            $warranty = WarrantyRequest::lockForUpdate()->find($warrantyId);

            if (! $warranty) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "WarrantyRequest #{$warrantyId} không tồn tại."
                );
            }

            if ($warranty->status !== WarrantyRequest::STATUS_REQUESTED) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "WarrantyRequest #{$warrantyId} đang ở trạng thái '{$warranty->status}', chỉ 'requested' mới được approve."
                );
            }

            $warranty->update([
                'status' => WarrantyRequest::STATUS_APPROVED,
                'handled_by' => $adminId,
            ]);

            $fresh = $warranty->fresh(['orderDetail', 'user', 'handledBy']);
            $fresh->user?->notify(new WarrantyRequestStatusUpdated($fresh->id, WarrantyRequestStatusUpdated::EVENT_APPROVED));

            return $fresh;
        });
    }

    /**
     * Admin reject warranty request.
     */
    public function reject(int $warrantyId, int $adminId): WarrantyRequest
    {
        return DB::transaction(function () use ($warrantyId, $adminId) {
            $warranty = WarrantyRequest::lockForUpdate()->find($warrantyId);

            if (! $warranty) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "WarrantyRequest #{$warrantyId} không tồn tại."
                );
            }

            if (! in_array($warranty->status, [WarrantyRequest::STATUS_REQUESTED, WarrantyRequest::STATUS_APPROVED], true)) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "WarrantyRequest #{$warrantyId} đang ở trạng thái '{$warranty->status}', không thể từ chối."
                );
            }

            $warranty->update([
                'status' => WarrantyRequest::STATUS_REJECTED,
                'resolution' => WarrantyRequest::RESOLUTION_REJECTED,
                'handled_by' => $adminId,
            ]);

            $fresh = $warranty->fresh(['orderDetail', 'user', 'handledBy']);
            $fresh->user?->notify(new WarrantyRequestStatusUpdated($fresh->id, WarrantyRequestStatusUpdated::EVENT_REJECTED));

            return $fresh;
        });
    }

    /**
     * Admin marks warranty as processing.
     */
    public function processing(int $warrantyId, int $adminId): WarrantyRequest
    {
        return DB::transaction(function () use ($warrantyId, $adminId) {
            $warranty = WarrantyRequest::lockForUpdate()->find($warrantyId);

            if (! $warranty) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "WarrantyRequest #{$warrantyId} không tồn tại."
                );
            }

            if ($warranty->status !== WarrantyRequest::STATUS_APPROVED) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "WarrantyRequest #{$warrantyId} đang ở trạng thái '{$warranty->status}', chỉ 'approved' mới được processing."
                );
            }

            $warranty->update([
                'status' => WarrantyRequest::STATUS_PROCESSING,
                'handled_by' => $adminId,
                'received_at' => $warranty->received_at ?? now(),
            ]);

            $fresh = $warranty->fresh(['orderDetail', 'user', 'handledBy']);
            $fresh->user?->notify(new WarrantyRequestStatusUpdated($fresh->id, WarrantyRequestStatusUpdated::EVENT_PROCESSING));

            return $fresh;
        });
    }

    /**
     * Admin complete warranty request with resolution.
     * No refund by default — resolution is part/runner replacement, repair, or product replacement.
     */
    public function complete(int $warrantyId, string $resolution, int $adminId): WarrantyRequest
    {
        return DB::transaction(function () use ($warrantyId, $resolution, $adminId) {
            $warranty = WarrantyRequest::lockForUpdate()->find($warrantyId);

            if (! $warranty) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "WarrantyRequest #{$warrantyId} không tồn tại."
                );
            }

            if ($warranty->status !== WarrantyRequest::STATUS_PROCESSING) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "WarrantyRequest #{$warrantyId} đang ở trạng thái '{$warranty->status}', chỉ 'processing' mới được complete."
                );
            }

            if ($resolution === WarrantyRequest::RESOLUTION_REJECTED) {
                throw new RefundException(
                    RefundException::INVALID_REASON,
                    "resolution 'rejected' không dùng cho complete. Hãy dùng action reject (status=rejected)."
                );
            }

            if (! in_array($resolution, WarrantyRequest::RESOLUTIONS, true)) {
                throw new RefundException(
                    RefundException::INVALID_REASON,
                    "resolution '{$resolution}' không hợp lệ. Phải là: " . implode(', ', WarrantyRequest::RESOLUTIONS) . '.'
                );
            }

            $warranty->update([
                'status' => WarrantyRequest::STATUS_COMPLETED,
                'resolution' => $resolution,
                'completed_at' => now(),
                'handled_by' => $adminId,
            ]);

            $fresh = $warranty->fresh(['orderDetail', 'user', 'handledBy']);
            $fresh->user?->notify(new WarrantyRequestStatusUpdated(
                $fresh->id,
                WarrantyRequestStatusUpdated::EVENT_COMPLETED,
                $fresh->resolution,
            ));

            return $fresh;
        });
    }

    /**
     * Customer cancel自己的warranty request trước khi hoàn thành.
     */
    public function cancel(int $warrantyId, int $userId): WarrantyRequest
    {
        return DB::transaction(function () use ($warrantyId, $userId) {
            $warranty = WarrantyRequest::lockForUpdate()->find($warrantyId);

            if (! $warranty) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "WarrantyRequest #{$warrantyId} không tồn tại."
                );
            }

            if ((int) $warranty->user_id !== $userId) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "WarrantyRequest #{$warrantyId} không thuộc về bạn."
                );
            }

            if ($warranty->isTerminal()) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "WarrantyRequest #{$warrantyId} đang ở trạng thái '{$warranty->status}', không thể hủy."
                );
            }

            $warranty->update([
                'status' => WarrantyRequest::STATUS_CANCELLED,
            ]);

            return $warranty->fresh(['orderDetail', 'user']);
        });
    }

    /**
     * Validate evidence files: jpg/jpeg/png/webp, max 5MB each.
     *
     * @return array<int, string> stored file paths
     * @throws RefundException
     */
    public function validateAndStoreEvidence(array $files, int $warrantyId): array
    {
        $paths = [];
        $allowedMimes = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 5 * 1024; // 5MB in KB

        foreach ($files as $file) {
            if (! $file->isValid()) {
                throw new RefundException(
                    RefundException::INVALID_REASON,
                    'File upload không hợp lệ.'
                );
            }

            if (! in_array(strtolower($file->getClientOriginalExtension()), $allowedMimes, true)) {
                throw new RefundException(
                    RefundException::INVALID_REASON,
                    "File '{$file->getClientOriginalName()}' định dạng không hỗ trợ. Chỉ chấp nhận: jpg, jpeg, png, webp."
                );
            }

            if ($file->getSize() > $maxSize * 1024) {
                throw new RefundException(
                    RefundException::INVALID_REASON,
                    "File '{$file->getClientOriginalName()}' vượt quá 5MB."
                );
            }

            $path = $file->store("warranty-evidence/{$warrantyId}", 'public');
            $paths[] = $path;
        }

        return $paths;
    }
}
