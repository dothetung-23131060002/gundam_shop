<?php

namespace App\Services;

use App\Exceptions\RefundException;
use App\Models\Batch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\RefundTransaction;
use App\Models\RefundTransactionDetail;
use App\Models\Reservation;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cơ chế refund tập trung. Mọi nghiệp vụ hoàn tiền đều phải đi qua service này.
 *
 * NGUYÊN TẮC BẮT BUỘC (đã duyệt):
 *
 * 1. EXPIRED_FORFEITED: ghi bằng RefundTransaction hiện tại, KHÔNG migration mới.
 *    Quy ước: type=deposit_refund, reason=EXPIRED_FORFEITED, status=completed,
 *    payment_id=NULL, KHÔNG tạo Payment refund, KHÔNG tính vào Refund Total /
 *    Net Revenue / Net Sold. Có báo cáo Forfeiture Income riêng (refundTotals()).
 *
 * 2. IDEMPOTENCY TRUNG THỰC: lockForUpdate + server-side recheck chống
 *    concurrent duplicate (double-click, admin trùng, auto+manual đồng thời).
 *    CHƯA bảo đảm 100% chống network retry sau khi transaction đã commit
 *    (schema chưa có idempotency_key — cần migration tương lai). KHÔNG được
 *    tuyên bố hệ thống đã idempotent hoàn toàn.
 *
 * 3. GLOBAL LOCK ORDER: Batch → Reservation → Order → OrderDetail →
 *    RefundTransaction. Mọi method đều đọc id routing trước (không lock),
 *    rồi lock theo đúng thứ tự trên và re-validate sau lock. Flow nào không
 *    cần entity nào thì bỏ qua entity đó, nhưng KHÔNG đảo thứ tự (tránh
 *    deadlock giữa auto refund và manual refund).
 *    Đã đấu nối: BatchService::markFailed, ReservationController::destroy,
 *    Admin\OrderController::updateStatus(cancelled) đều đi qua service này.
 *    Admin\OrderController::collectCod chỉ lock Order — tương thích.
 *
 * 4. ACTUALLY PAID: Payment record KHÔNG phải bằng chứng duy nhất (Balance
 *    Payment tạo lúc user claim, trước admin approval; COD thu tiền thật
 *    KHÔNG tạo Payment record). Quy tắc theo payment_method:
 *    - qr / balance / cash: chỉ phần đã `payment_status = paid`.
 *    - cod: `unpaid` → từ chối monetary refund; `paid` (qua collectCod) → được refund.
 *    - QR/MoMo: DB đổi status ≠ đã refund thật — tiền chỉ đi ở complete().
 *
 * 5. REFUND TOTALS: chỉ tính status=completed và reason != EXPIRED_FORFEITED.
 *    pending/failed KHÔNG bao giờ vào Revenue hoặc Net Sold.
 *
 * STATE MACHINE: pending → completed | pending → failed. Refund luôn được tạo
 * ở pending với refunded_at=NULL; refunded_at chỉ set khi completed.
 * DB commit KHÔNG đồng nghĩa tiền đã đi — tiền chỉ đi ở complete().
 *
 * ANTI-DOUBLE-REFUND (deposit pot vs order pot):
 * - R1: reservation có order paid hoặc đã có order-refund completed thì pot
 *   cọc đóng — mọi tiền đi qua pot order (cap = total_amount đã gồm phần cọc).
 * - R2: còn order sống (order_status != cancelled) thì pot cọc đóng.
 * Hai quy tắc được check ở lúc tạo VÀ re-check ở complete().
 */
class RefundService
{
    // Lý do hoàn tiền (11 nghiệp vụ).
    public const REASON_BATCH_FAILED = 'BATCH_FAILED';

    public const REASON_USER_CANCEL_DEPOSIT = 'USER_CANCEL_DEPOSIT';

    public const REASON_SUPPLIER_SHORTAGE = 'SUPPLIER_SHORTAGE';

    public const REASON_RELEASE_OVERDUE = 'RELEASE_OVERDUE';

    public const REASON_LOST_IN_TRANSIT = 'LOST_IN_TRANSIT';

    public const REASON_PAYMENT_ERROR = 'PAYMENT_ERROR';

    public const REASON_MANUFACTURER_DEFECT = 'MANUFACTURER_DEFECT';

    public const REASON_WRONG_ITEM = 'WRONG_ITEM';

    public const REASON_DAMAGED_TRANSIT = 'DAMAGED_TRANSIT';

    public const REASON_DELIVERY_FAILED = 'DELIVERY_FAILED';

    // Admin chủ động hủy đơn (không phải giao hàng thất bại).
    // Cột reason là string nên không cần migration cho reason này.
    public const REASON_ADMIN_CANCEL = 'ADMIN_CANCEL';

    public const REASON_EXPIRED_FORFEITED = RefundTransaction::REASON_FORFEITED;

    public const DEPOSIT_REASONS = [
        self::REASON_BATCH_FAILED,
        self::REASON_USER_CANCEL_DEPOSIT,
        self::REASON_RELEASE_OVERDUE,
    ];

    public const ORDER_REASONS = [
        self::REASON_BATCH_FAILED,
        self::REASON_SUPPLIER_SHORTAGE,
        self::REASON_LOST_IN_TRANSIT,
        self::REASON_PAYMENT_ERROR,
        self::REASON_MANUFACTURER_DEFECT,
        self::REASON_WRONG_ITEM,
        self::REASON_DAMAGED_TRANSIT,
        self::REASON_DELIVERY_FAILED,
        self::REASON_ADMIN_CANCEL,
    ];

    /**
     * Trạng thái reservation sau khi hoàn cọc xong, theo lý do.
     * Forfeiture KHÔNG đổi trạng thái ở đây (xem recordForfeiture).
     */
    public const DEPOSIT_DONE_STATUS = [
        self::REASON_BATCH_FAILED => 'refunded',
        self::REASON_USER_CANCEL_DEPOSIT => 'cancelled',
        self::REASON_RELEASE_OVERDUE => 'cancelled',
    ];

    /**
     * Lock theo GLOBAL ORDER (Batch → Reservation → Order) và trả về
     * các model đã lock để validate tiếp. Caller đọc id routing trước
     * (không lock), helper này lock đúng thứ tự rồi đọc lại.
     *
     * @return array{0: ?Batch, 1: ?Reservation, 2: ?Order}
     */
    protected function lockChain(?int $batchId, ?int $reservationId, ?int $orderId): array
    {
        $batch = $batchId ? Batch::lockForUpdate()->find($batchId) : null;
        $reservation = $reservationId ? Reservation::lockForUpdate()->find($reservationId) : null;
        $order = $orderId ? Order::lockForUpdate()->find($orderId) : null;

        return [$batch, $reservation, $order];
    }

    /**
     * R2: còn order sống (order_status != cancelled) thì pot cọc đóng —
     * còn đơn sống thì tiền đi qua đơn.
     *
     * @throws RefundException
     */
    protected function assertNoLiveOrder(int $reservationId): void
    {
        $live = Order::where('reservation_id', $reservationId)
            ->where('order_status', '!=', 'cancelled')
            ->lockForUpdate()
            ->exists();

        if ($live) {
            throw new RefundException(
                RefundException::DUPLICATE_REFUND,
                "Reservation #{$reservationId} còn đơn hàng đang sống — tiền hoàn (nếu có) phải đi qua order refund."
            );
        }
    }

    /**
     * R1: đã có order paid hoặc đã có order-refund completed thì pot cọc
     * đóng vĩnh viễn — cọc đã nhập vào pot order (cap = total_amount).
     *
     * @throws RefundException
     */
    protected function assertDepositPotOpen(int $reservationId): void
    {
        $paid = Order::where('reservation_id', $reservationId)
            ->where('payment_status', Order::PAY_PAID)
            ->lockForUpdate()
            ->exists();

        if ($paid) {
            throw new RefundException(
                RefundException::DUPLICATE_REFUND,
                "Reservation #{$reservationId} đã có order paid — tiền cọc đã nhập vào pot order, không hoàn cọc riêng."
            );
        }

        $refunded = RefundTransaction::where('reservation_id', $reservationId)
            ->whereNotNull('order_id')
            ->monetary()
            ->lockForUpdate()
            ->exists();

        if ($refunded) {
            throw new RefundException(
                RefundException::DUPLICATE_REFUND,
                "Reservation #{$reservationId} đã có order refund completed — không hoàn cọc thêm."
            );
        }
    }

    /**
     * Hoàn cọc reservation. Số tiền = deposit_paid còn lại (service tự đọc,
     * caller KHÔNG được truyền amount). Trả về refund ở trạng thái pending.
     *
     * @throws RefundException
     */
    public function createDepositRefund(int $reservationId, string $reason, ?int $adminId = null): RefundTransaction
    {
        $this->assertDepositReason($reason);

        return DB::transaction(function () use ($reservationId, $reason, $adminId) {
            $route = Reservation::find($reservationId);

            if (! $route) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Reservation #{$reservationId} không tồn tại."
                );
            }

            // Lock order: Batch → Reservation → (không có Order ở flow này).
            [$batch, $reservation] = $this->lockChain($route->batch_id, $route->id, null);

            if (! $reservation) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Reservation #{$reservationId} không còn tồn tại."
                );
            }

            $this->assertNoLiveOrder($reservation->id);
            $this->assertDepositPotOpen($reservation->id);
            $this->assertNoPendingOrCompletedDepositRefund($reservation->id);

            if ($reservation->status !== 'reserved') {
                throw new RefundException(
                    RefundException::NOT_REFUNDABLE_STATE,
                    "Reservation #{$reservation->id} đang ở trạng thái '{$reservation->status}', không thể hoàn cọc."
                );
            }

            $amount = round((float) $reservation->deposit_paid, 2);

            if ($amount <= 0) {
                throw new RefundException(
                    RefundException::NOTHING_TO_REFUND,
                    "Reservation #{$reservation->id} không còn tiền cọc để hoàn."
                );
            }

            return RefundTransaction::create([
                'reservation_id' => $reservation->id,
                'order_id' => null,
                'payment_id' => null,
                'amount' => $amount,
                'reason' => $reason,
                'refunded_at' => null,
                'status' => RefundTransaction::STATUS_PENDING,
                'type' => RefundTransaction::TYPE_DEPOSIT,
                'admin_id' => $adminId,
            ]);
        });
    }

    /**
     * Hoàn tiền đơn hàng (full hoặc partial) theo từng OrderDetail trong 1 lần.
     * Số tiền từng dòng = price snapshot × quantity (service tự tính).
     *
     * @param  array  $items  [['order_detail_id' => int, 'quantity' => int], ...]
     *
     * @throws RefundException
     */
    public function createOrderRefund(int $orderId, array $items, string $reason, ?int $adminId = null): RefundTransaction
    {
        $this->assertOrderReason($reason);

        if (empty($items)) {
            throw new RefundException(
                RefundException::DETAIL_MISMATCH,
                'Refund đơn hàng phải có ít nhất một dòng chi tiết.'
            );
        }

        return DB::transaction(function () use ($orderId, $items, $reason, $adminId) {
            $route = Order::find($orderId);

            if (! $route) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Order #{$orderId} không tồn tại."
                );
            }

            // Lock order: Batch → Reservation → Order.
            [$batch, $reservation, $order] = $this->lockChain($route->batch_id, $route->reservation_id, $route->id);

            if (! $order) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Order #{$orderId} không còn tồn tại."
                );
            }

            $paidAmount = $this->paidAmountForOrder($order);

            // COD chưa thu tiền: cấm monetary refund (điều chỉnh 4).
            if ($order->payment_method === 'cod' && $order->payment_status !== Order::PAY_PAID) {
                throw new RefundException(
                    RefundException::UNPAID_COD,
                    "Order COD #{$order->id} chưa thu tiền (payment_status='{$order->payment_status}'), không được hoàn tiền."
                );
            }

            if ($paidAmount <= 0) {
                throw new RefundException(
                    RefundException::UNPAID_ORDER,
                    "Order #{$order->id} chưa có khoản thực thu nào (payment_status='{$order->payment_status}'), không được hoàn tiền."
                );
            }

            [$lines, $headerAmount] = $this->buildOrderRefundLines($order, $items, $paidAmount);

            $refund = RefundTransaction::create([
                'reservation_id' => $order->reservation_id,
                'order_id' => $order->id,
                'payment_id' => null,
                'amount' => $headerAmount,
                'reason' => $reason,
                'refunded_at' => null,
                'status' => RefundTransaction::STATUS_PENDING,
                'type' => RefundTransaction::TYPE_ORDER,
                'admin_id' => $adminId,
            ]);

            foreach ($lines as $line) {
                RefundTransactionDetail::create([
                    'refund_transaction_id' => $refund->id,
                    'order_detail_id' => $line['order_detail_id'],
                    'quantity_refunded' => $line['quantity'],
                    'amount_refunded' => $line['amount'],
                ]);
            }

            return $refund->fresh('details');
        });
    }

    /**
     * Dựng các dòng refund + tổng header từ items. Caller phải lock
     * OrderDetail (theo id tăng dần) và serialize refund của order trước.
     * Dùng chung cho createOrderRefund và cancelAndRefundOrder.
     *
     * @return array{0: array, 1: float}
     *
     * @throws RefundException
     */
    protected function buildOrderRefundLines(Order $order, array $items, float $paidAmount): array
    {
        $detailIds = collect($items)->pluck('order_detail_id')->all();
        $details = OrderDetail::whereIn('id', $detailIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // Serialize với các refund đang có của cùng order.
        RefundTransaction::where('order_id', $order->id)->lockForUpdate()->get();

        $lines = [];
        $headerAmount = 0.0;

        foreach ($items as $item) {
            $detailId = (int) ($item['order_detail_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            $detail = $details->get($detailId);

            if (! $detail || (int) $detail->order_id !== (int) $order->id) {
                throw new RefundException(
                    RefundException::DETAIL_MISMATCH,
                    "OrderDetail #{$detailId} không thuộc Order #{$order->id}."
                );
            }

            if ($quantity <= 0) {
                throw new RefundException(
                    RefundException::DETAIL_MISMATCH,
                    "Số lượng hoàn của OrderDetail #{$detailId} phải lớn hơn 0."
                );
            }

            $alreadyRefunded = $this->completedQuantityForDetail($detailId);
            $remaining = (int) $detail->quantity - $alreadyRefunded;

            if ($quantity > $remaining) {
                throw new RefundException(
                    RefundException::QUANTITY_EXCEEDED,
                    "OrderDetail #{$detailId}: đã hoàn {$alreadyRefunded}/{$detail->quantity}, chỉ còn được hoàn {$remaining}, yêu cầu {$quantity}."
                );
            }

            $lineAmount = round((float) $detail->price * $quantity, 2);
            $headerAmount = round($headerAmount + $lineAmount, 2);

            $lines[] = [
                'order_detail_id' => $detail->id,
                'quantity' => $quantity,
                'amount' => $lineAmount,
            ];
        }

        $alreadyRefundedAmount = $this->completedAmountForOrder($order->id);

        if (round($alreadyRefundedAmount + $headerAmount, 2) > $paidAmount) {
            throw new RefundException(
                RefundException::AMOUNT_EXCEEDED,
                "Order #{$order->id}: đã trả thực {$paidAmount}, đã hoàn {$alreadyRefundedAmount}, yêu cầu thêm {$headerAmount} vượt số tiền thực thu."
            );
        }

        return [$lines, $headerAmount];
    }

    /**
     * Hủy giữ slot + tạo refund cọc pending TRONG CÙNG 1 transaction.
     * Trạng thái reservation flip ngay (giải phóng slot) — tiền đi sau ở
     * complete(). Dùng cho: user hủy, batch fail, RELEASE_OVERDUE,
     * batch success nhưng chưa có order sống.
     *
     * @throws RefundException
     */
    public function cancelReservationAndRefundDeposit(int $reservationId, string $reason, ?int $adminId = null): RefundTransaction
    {
        $this->assertDepositReason($reason);

        return DB::transaction(function () use ($reservationId, $reason, $adminId) {
            $route = Reservation::find($reservationId);

            if (! $route) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Reservation #{$reservationId} không tồn tại."
                );
            }

            [$batch, $reservation] = $this->lockChain($route->batch_id, $route->id, null);

            if (! $reservation) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Reservation #{$reservationId} không còn tồn tại."
                );
            }

            if ($reservation->status !== 'reserved') {
                throw new RefundException(
                    RefundException::NOT_REFUNDABLE_STATE,
                    "Reservation #{$reservation->id} đang ở trạng thái '{$reservation->status}', không thể hủy-hoàn cọc."
                );
            }

            $this->assertNoLiveOrder($reservation->id);
            $this->assertDepositPotOpen($reservation->id);
            $this->assertNoPendingOrCompletedDepositRefund($reservation->id);

            $amount = round((float) $reservation->deposit_paid, 2);

            if ($amount <= 0) {
                throw new RefundException(
                    RefundException::NOTHING_TO_REFUND,
                    "Reservation #{$reservation->id} không còn tiền cọc để hoàn."
                );
            }

            $refund = RefundTransaction::create([
                'reservation_id' => $reservation->id,
                'order_id' => null,
                'payment_id' => null,
                'amount' => $amount,
                'reason' => $reason,
                'refunded_at' => null,
                'status' => RefundTransaction::STATUS_PENDING,
                'type' => RefundTransaction::TYPE_DEPOSIT,
                'admin_id' => $adminId,
            ]);

            $reservation->update([
                'status' => self::DEPOSIT_DONE_STATUS[$reason] ?? 'cancelled',
            ]);

            return $refund->fresh();
        });
    }

    /**
     * Hủy order + tạo refund pending TRONG CÙNG 1 transaction (atomic ở DB).
     * KHÔNG giả định tiền đã hoàn — tiền đi sau ở complete(), thất bại ở fail().
     *
     * - Order unpaid (chưa thu đồng nào): chỉ cancel, không tạo refund, trả refund=null.
     * - Order paid: items rỗng → tự hoàn full phần còn lại; có items → theo items.
     * - Không cancel order completed (sau giao hàng đi đường incident-refund,
     *   không đổi trạng thái) và order đã cancelled.
     * - Hồi kho IFF order cart (batch_id IS NULL) — batch order chưa từng trừ kho.
     *
     * @param  array  $items  [['order_detail_id' => int, 'quantity' => int], ...]
     * @return array{order: Order, refund: ?RefundTransaction}
     *
     * @throws RefundException
     */
    public function cancelAndRefundOrder(int $orderId, array $items = [], string $reason = self::REASON_ADMIN_CANCEL, ?int $adminId = null): array
    {
        $this->assertOrderReason($reason);

        return DB::transaction(function () use ($orderId, $items, $reason, $adminId) {
            $route = Order::find($orderId);

            if (! $route) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Order #{$orderId} không tồn tại."
                );
            }

            [$batch, $linkedReservation, $order] = $this->lockChain($route->batch_id, $route->reservation_id, $route->id);

            if (! $order) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Order #{$orderId} không còn tồn tại."
                );
            }

            if ($order->order_status === 'cancelled') {
                throw new RefundException(
                    RefundException::NOT_REFUNDABLE_STATE,
                    "Order #{$order->id} đã cancelled — không cancel/hoàn lần 2."
                );
            }

            if ($order->order_status === 'completed') {
                throw new RefundException(
                    RefundException::NOT_REFUNDABLE_STATE,
                    "Order #{$order->id} đã completed — sau giao hàng chỉ đi đường incident refund, không đổi trạng thái."
                );
            }

            $paidAmount = $this->paidAmountForOrder($order);
            $refund = null;

            if ($paidAmount > 0) {
                if (empty($items)) {
                    $items = $this->fullRemainingItems($order->id);
                }

                if (empty($items)) {
                    throw new RefundException(
                        RefundException::NOTHING_TO_REFUND,
                        "Order #{$order->id} đã paid nhưng không còn dòng nào để hoàn."
                    );
                }

                [$lines, $headerAmount] = $this->buildOrderRefundLines($order, $items, $paidAmount);

                $refund = RefundTransaction::create([
                    'reservation_id' => $order->reservation_id,
                    'order_id' => $order->id,
                    'payment_id' => null,
                    'amount' => $headerAmount,
                    'reason' => $reason,
                    'refunded_at' => null,
                    'status' => RefundTransaction::STATUS_PENDING,
                    'type' => RefundTransaction::TYPE_ORDER,
                    'admin_id' => $adminId,
                ]);

                foreach ($lines as $line) {
                    RefundTransactionDetail::create([
                        'refund_transaction_id' => $refund->id,
                        'order_detail_id' => $line['order_detail_id'],
                        'quantity_refunded' => $line['quantity'],
                        'amount_refunded' => $line['amount'],
                    ]);
                }

                $refund = $refund->fresh('details');
            }

            // Hồi kho IFF đơn cart (đơn batch chưa từng trừ kho).
            if ($order->batch_id === null) {
                $details = OrderDetail::where('order_id', $order->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($details as $detail) {
                    if ($detail->product_id) {
                        $detail->product()->increment('quantity', $detail->quantity);
                    }
                }
            }

            $order->update(['order_status' => 'cancelled']);

            return ['order' => $order->fresh(), 'refund' => $refund];
        });
    }

    /**
     * Dựng items hoàn full phần còn lại của mọi dòng (dùng khi cancel
     * mà caller không chỉ định items).
     */
    protected function fullRemainingItems(int $orderId): array
    {
        $details = OrderDetail::where('order_id', $orderId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $items = [];

        foreach ($details as $detail) {
            $remaining = (int) $detail->quantity - $this->completedQuantityForDetail($detail->id);

            if ($remaining > 0) {
                $items[] = ['order_detail_id' => $detail->id, 'quantity' => $remaining];
            }
        }

        return $items;
    }

    /**
     * Hoàn tất refund: pending → completed. Tiền thật được chi TẠI ĐÂY
     * (tạo Payment type refund + link payment_id + set refunded_at).
     * Re-check lại toàn bộ guard vì pending không được tính vào cap lúc tạo.
     *
     * @throws RefundException
     */
    public function complete(int $refundId, ?int $adminId = null): RefundTransaction
    {
        return DB::transaction(function () use ($refundId, $adminId) {
            $route = RefundTransaction::find($refundId);

            if (! $route) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Refund #{$refundId} không tồn tại."
                );
            }

            // Routing không lock để lấy id, rồi lock đúng GLOBAL ORDER.
            $reservationId = $route->reservation_id;
            $orderId = $route->order_id;

            if ($orderId && ! $reservationId) {
                $orderRoute = Order::find($orderId);
                $reservationId = $orderRoute ? $orderRoute->reservation_id : null;
            }

            $batchId = null;

            if ($reservationId) {
                $resRoute = Reservation::find($reservationId);
                $batchId = $resRoute ? $resRoute->batch_id : null;
            }

            if (! $batchId && $orderId) {
                $orderRoute = $orderRoute ?? Order::find($orderId);
                $batchId = $orderRoute ? $orderRoute->batch_id : null;
            }

            [$batch, $reservation, $order] = $this->lockChain($batchId, $reservationId, $orderId);

            // RefundTransaction luôn lock cuối cùng.
            $locked = RefundTransaction::lockForUpdate()->find($route->id);

            if (! $locked->isPending()) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "Refund #{$locked->id} đang ở trạng thái '{$locked->status}', chỉ pending mới được complete."
                );
            }

            if ($locked->isForfeiture()) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "Bản ghi forfeiture #{$locked->id} đã completed sẵn, không đi qua complete()."
                );
            }

            $userId = null;

            if ($locked->type === RefundTransaction::TYPE_DEPOSIT) {
                if (! $reservation) {
                    throw new RefundException(
                        RefundException::SOURCE_NOT_FOUND,
                        "Reservation của refund #{$locked->id} không còn tồn tại."
                    );
                }

                // Re-check R1/R2: từ lúc tạo đến lúc complete không được xuất
                // hiện order sống/paid (tiền đã chuyển sang pot order).
                $this->assertNoLiveOrder($reservation->id);
                $this->assertDepositPotOpen($reservation->id);

                if (round((float) $reservation->deposit_paid, 2) < round((float) $locked->amount, 2)) {
                    throw new RefundException(
                        RefundException::AMOUNT_EXCEEDED,
                        "Reservation #{$reservation->id} chỉ còn {$reservation->deposit_paid} tiền cọc, không đủ hoàn {$locked->amount}."
                    );
                }

                $userId = $reservation->user_id;
            } else {
                if (! $order) {
                    throw new RefundException(
                        RefundException::SOURCE_NOT_FOUND,
                        "Order của refund #{$locked->id} không còn tồn tại."
                    );
                }

                // Re-check money cap với chính refund này được tính vào.
                // (Trạng thái paid đã khóa từ lúc tạo — complete() chỉ recheck cap.)
                $paidAmount = $this->paidAmountForOrder($order);
                $otherCompleted = $this->completedAmountForOrder($order->id);

                if (round($otherCompleted + (float) $locked->amount, 2) > $paidAmount) {
                    throw new RefundException(
                        RefundException::AMOUNT_EXCEEDED,
                        "Order #{$order->id}: đã trả thực {$paidAmount}, hoàn tất thêm {$locked->amount} sẽ vượt."
                    );
                }

                // Re-check quantity từng dòng.
                $detailIds = $locked->details()->pluck('order_detail_id')->filter()->all();

                if (! empty($detailIds)) {
                    $details = OrderDetail::whereIn('id', $detailIds)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    foreach ($locked->details as $line) {
                        if (! $line->order_detail_id) {
                            continue;
                        }

                        $detail = $details->get($line->order_detail_id);

                        if (! $detail) {
                            throw new RefundException(
                                RefundException::SOURCE_NOT_FOUND,
                                "OrderDetail #{$line->order_detail_id} của refund #{$locked->id} không còn tồn tại."
                            );
                        }

                        $otherQty = $this->completedQuantityForDetail($detail->id);

                        if ($otherQty + (int) $line->quantity_refunded > (int) $detail->quantity) {
                            throw new RefundException(
                                RefundException::QUANTITY_EXCEEDED,
                                "OrderDetail #{$detail->id}: hoàn tất refund này sẽ vượt số lượng đã đặt."
                            );
                        }
                    }
                }

                $userId = $order->user_id;
            }

            // Tiền thật đi tại đây: tạo Payment refund trước, rồi link ngược.
            $payment = Payment::create([
                'user_id' => $userId,
                'reservation_id' => $locked->reservation_id,
                'order_id' => $locked->order_id,
                'amount' => $locked->amount,
                'type' => 'refund',
                'note' => "Hoàn tiền #{$locked->id} ({$locked->reason})",
            ]);

            $locked->update([
                'status' => RefundTransaction::STATUS_COMPLETED,
                'refunded_at' => now(),
                'payment_id' => $payment->id,
                'admin_id' => $adminId,
            ]);

            // Đóng tiền cọc cho deposit refund. Status chỉ set khi reservation
            // vẫn còn 'reserved' (flow cancel đã flip status từ trước thì giữ).
            if ($locked->type === RefundTransaction::TYPE_DEPOSIT && $reservation) {
                $updates = ['deposit_paid' => 0];

                if ($reservation->status === 'reserved') {
                    $updates['status'] = self::DEPOSIT_DONE_STATUS[$locked->reason] ?? $reservation->status;
                }

                $reservation->update($updates);
            }

            // QĐ2: FULL order refund completed → đóng pot cọc của reservation
            // liên kết (deposit_paid = 0, GIỮ NGUYÊN status converted).
            // Partial thì không đụng tới deposit_paid.
            if ($locked->type === RefundTransaction::TYPE_ORDER && $order && $order->reservation_id) {
                $linked = ($reservation && (int) $reservation->id === (int) $order->reservation_id)
                    ? $reservation
                    : Reservation::lockForUpdate()->find($order->reservation_id);

                if ($linked && round($otherCompleted + (float) $locked->amount, 2) >= $paidAmount) {
                    $linked->update(['deposit_paid' => 0]);
                }
            }

            return $locked->fresh(['details', 'payment']);
        });
    }

    /**
     * Từ chối refund: pending → failed. Giữ bản ghi đối soát, KHÔNG tạo Payment.
     * Lý do từ chối do caller ghi vào admin action log (bảng chưa có cột note).
     *
     * @throws RefundException
     */
    public function fail(int $refundId, ?int $adminId = null): RefundTransaction
    {
        return DB::transaction(function () use ($refundId, $adminId) {
            $locked = RefundTransaction::lockForUpdate()->find($refundId);

            if (! $locked) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Refund #{$refundId} không tồn tại."
                );
            }

            if (! $locked->isPending()) {
                throw new RefundException(
                    RefundException::INVALID_TRANSITION,
                    "Refund #{$locked->id} đang ở trạng thái '{$locked->status}', chỉ pending mới được fail."
                );
            }

            $locked->update([
                'status' => RefundTransaction::STATUS_FAILED,
                'admin_id' => $adminId,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Ghi nhận tịch thu cọc quá hạn (EXPIRED_FORFEITED).
     * Quy ước cứng: type=deposit_refund, status=completed ngay, payment_id=NULL,
     * KHÔNG tạo Payment, KHÔNG tính vào Refund Total / Net Revenue / Net Sold.
     *
     * INTERIM CONVENTION (không migration, chưa có status `forfeited` riêng):
     * cùng transaction này đóng reservation bằng status='cancelled' +
     * deposit_paid=0 để giải phóng slot và holder tiền giữ. Bản ghi forfeiture
     * (reason=EXPIRED_FORFEITED) là bằng chứng phân biệt với cancel-hoàn thường.
     *
     * @throws RefundException
     */
    public function recordForfeiture(int $reservationId, ?int $adminId = null): RefundTransaction
    {
        return DB::transaction(function () use ($reservationId, $adminId) {
            $route = Reservation::find($reservationId);

            if (! $route) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Reservation #{$reservationId} không tồn tại."
                );
            }

            // Lock order: Batch → Reservation → RefundTransaction (không Order).
            [$batch, $reservation] = $this->lockChain($route->batch_id, $route->id, null);

            if (! $reservation) {
                throw new RefundException(
                    RefundException::SOURCE_NOT_FOUND,
                    "Reservation #{$reservationId} không còn tồn tại."
                );
            }

            // Re-check mọi điều kiện bên trong transaction.
            if ($reservation->status !== 'reserved') {
                throw new RefundException(
                    RefundException::NOT_REFUNDABLE_STATE,
                    "Reservation #{$reservation->id} đang ở trạng thái '{$reservation->status}' (cần reserved), không thể forfeiture."
                );
            }

            if (! $batch || $batch->status !== 'success') {
                throw new RefundException(
                    RefundException::NOT_REFUNDABLE_STATE,
                    "Forfeiture chỉ áp dụng khi batch đã success (batch #{$reservation->batch_id})."
                );
            }

            // Bắt buộc: không còn đơn sống — nếu không, duyệt đơn sau sẽ
            // khiến khách vừa mất cọc vừa trả full giá (double).
            $this->assertNoLiveOrder($reservation->id);

            // Đã có order-refund completed thì tiền đã đi qua pot order.
            $orderRefunded = RefundTransaction::where('reservation_id', $reservation->id)
                ->whereNotNull('order_id')
                ->monetary()
                ->lockForUpdate()
                ->exists();

            if ($orderRefunded) {
                throw new RefundException(
                    RefundException::DUPLICATE_REFUND,
                    "Reservation #{$reservation->id} đã có order refund completed — không forfeiture thêm."
                );
            }

            $amount = round((float) $reservation->deposit_paid, 2);

            if ($amount <= 0) {
                throw new RefundException(
                    RefundException::NOTHING_TO_REFUND,
                    "Reservation #{$reservation->id} không còn tiền cọc để ghi forfeiture."
                );
            }

            $exists = RefundTransaction::where('reservation_id', $reservation->id)
                ->where('reason', RefundTransaction::REASON_FORFEITED)
                ->where('status', RefundTransaction::STATUS_COMPLETED)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new RefundException(
                    RefundException::DUPLICATE_REFUND,
                    "Reservation #{$reservation->id} đã có bản ghi forfeiture."
                );
            }

            $record = RefundTransaction::create([
                'reservation_id' => $reservation->id,
                'order_id' => null,
                'payment_id' => null,
                'amount' => $amount,
                'reason' => RefundTransaction::REASON_FORFEITED,
                'refunded_at' => now(),
                'status' => RefundTransaction::STATUS_COMPLETED,
                'type' => RefundTransaction::TYPE_DEPOSIT,
                'admin_id' => $adminId,
            ]);

            // INTERIM: đóng cọc + đóng trạng thái để giải phóng slot.
            $reservation->update([
                'deposit_paid' => 0,
                'status' => 'cancelled',
            ]);

            return $record->fresh();
        });
    }

    // -----------------------------------------------------------------
    // PROXY deadline cho forfeiture (TẠM THỜI).
    // Schema hiện chưa có balance_due_at / estimated_release_date thật nên
    // "quá hạn trả balance" được ước lượng bằng reservation.updated_at +
    // số ngày ân hạn. TOÀN BỘ công thức nằm ở 3 method này — không hard-code
    // ở nơi khác. Sau này có deadline thật thì chỉ thay phần thân ở đây.
    // -----------------------------------------------------------------

    public const FORFEITURE_GRACE_SETTING = 'balance_forfeit_grace_days';

    public const FORFEITURE_DEFAULT_GRACE_DAYS = 7;

    /**
     * Số ngày ân hạn (Setting runtime, không phải schema).
     */
    public function forfeitureGraceDays(?int $override = null): int
    {
        if ($override !== null && $override >= 0) {
            return $override;
        }

        return max(0, (int) Setting::get(
            self::FORFEITURE_GRACE_SETTING,
            (string) self::FORFEITURE_DEFAULT_GRACE_DAYS
        ));
    }

    /**
     * Mốc thời gian: reservation không động đậy từ trước mốc này = quá hạn.
     */
    public function forfeitureOverdueCutoff(?int $graceDays = null): Carbon
    {
        return now()->subDays($this->forfeitureGraceDays($graceDays));
    }

    /**
     * Reservation có quá hạn trả balance theo proxy tạm thời không.
     * Chỉ là điều kiện THỜI GIAN — service recordForfeiture vẫn re-check
     * toàn bộ điều kiện tiền/trạng thái bên trong transaction.
     */
    public function isOverdueForForfeiture(Reservation $reservation, ?int $graceDays = null): bool
    {
        return $reservation->updated_at !== null
            && $reservation->updated_at->lte($this->forfeitureOverdueCutoff($graceDays));
    }

    /**
     * Số tiền thực sự đã thanh toán của order — KHÔNG dùng Payment record
     * làm bằng chứng duy nhất (xem docblock class, điều chỉnh 4).
     */
    public function paidAmountForOrder(Order $order): float
    {
        if ($order->payment_status !== Order::PAY_PAID) {
            return 0.0;
        }

        return round((float) $order->total_amount, 2);
    }

    /**
     * Tổng đã hoàn (completed, loại forfeiture) của một order.
     */
    public function refundedAmountForOrder(int $orderId): float
    {
        return round((float) RefundTransaction::where('order_id', $orderId)
            ->monetary()
            ->sum('amount'), 2);
    }

    /**
     * Tổng số lượng đã hoàn (completed) của một OrderDetail.
     * Forfeiture không bao giờ có details nên không cần lọc thêm.
     */
    public function refundedQuantityForDetail(int $orderDetailId): int
    {
        return (int) RefundTransactionDetail::where('order_detail_id', $orderDetailId)
            ->whereHas('refund', function ($q) {
                $q->where('status', RefundTransaction::STATUS_COMPLETED);
            })
            ->sum('quantity_refunded');
    }

    /**
     * Dữ liệu phục vụ báo cáo: Refund Total (tiền đã chi thật) và
     * Forfeiture Income riêng. pending/failed không bao giờ được tính.
     *
     * @return array{refund_total: float, forfeiture_total: float}
     */
    public function refundTotals(): array
    {
        return [
            'refund_total' => round((float) RefundTransaction::monetary()->sum('amount'), 2),
            'forfeiture_total' => round((float) RefundTransaction::forfeitures()->sum('amount'), 2),
        ];
    }

    /**
     * Tổng số lượng đã hoàn (completed) của một product — đầu vào cho
     * Net Sold sau này (Gross sold - số này). Không chạm products.sold_count.
     */
    public function refundedQuantityForProduct(int $productId): int
    {
        return (int) RefundTransactionDetail::whereHas('orderDetail', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })->whereHas('refund', function ($q) {
            $q->where('status', RefundTransaction::STATUS_COMPLETED);
        })->sum('quantity_refunded');
    }

    // -----------------------------------------------------------------
    // Guards nội bộ
    // -----------------------------------------------------------------

    protected function assertDepositReason(string $reason): void
    {
        if (! in_array($reason, self::DEPOSIT_REASONS, true)) {
            throw new RefundException(
                RefundException::INVALID_REASON,
                "Lý do '{$reason}' không dùng cho hoàn cọc."
            );
        }
    }

    protected function assertOrderReason(string $reason): void
    {
        if (! in_array($reason, self::ORDER_REASONS, true)) {
            throw new RefundException(
                RefundException::INVALID_REASON,
                "Lý do '{$reason}' không dùng cho hoàn đơn."
            );
        }
    }

    protected function assertNoPendingOrCompletedDepositRefund(int $reservationId): void
    {
        $exists = RefundTransaction::where('reservation_id', $reservationId)
            ->whereNull('order_id')
            ->where('reason', '!=', RefundTransaction::REASON_FORFEITED)
            ->whereIn('status', [RefundTransaction::STATUS_PENDING, RefundTransaction::STATUS_COMPLETED])
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw new RefundException(
                RefundException::DUPLICATE_REFUND,
                "Reservation #{$reservationId} đã có refund cọc đang xử lý hoặc đã hoàn."
            );
        }

        $forfeited = RefundTransaction::where('reservation_id', $reservationId)
            ->where('reason', RefundTransaction::REASON_FORFEITED)
            ->where('status', RefundTransaction::STATUS_COMPLETED)
            ->lockForUpdate()
            ->exists();

        if ($forfeited) {
            throw new RefundException(
                RefundException::DUPLICATE_REFUND,
                "Reservation #{$reservationId} đã bị forfeiture, tiền cọc đã ghi nhận thu nhập."
            );
        }
    }

    protected function completedAmountForOrder(int $orderId): float
    {
        return $this->refundedAmountForOrder($orderId);
    }

    protected function completedQuantityForDetail(int $orderDetailId): int
    {
        return $this->refundedQuantityForDetail($orderDetailId);
    }
}
