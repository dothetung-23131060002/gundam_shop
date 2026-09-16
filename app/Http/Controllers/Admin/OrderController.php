<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RefundException;
use App\Http\Controllers\Admin\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RefundTransaction;
use App\Models\Reservation;
use App\Notifications\OrderStatusChanged;
use App\Notifications\PaymentStatusUpdated;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = Order::with('user')->latest();

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->paginate(10)->withQueryString();

        $paymentStatuses = [
            'unpaid' => 'Chờ thanh toán (COD/cũ)',
            'pending_payment' => 'Chờ thanh toán',
            'awaiting_confirmation' => 'Chờ shop xác nhận',
            'paid' => 'Thanh toán thành công',
            'payment_rejected' => 'Chưa được xác nhận',
        ];

        return view('admin.orders.index', compact('orders', 'paymentStatuses'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'details.product', 'batch']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order, RefundService $refundService)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:pending,confirmed,shipping,completed,cancelled',
        ]);

        $oldStatus = $order->order_status;
        $newStatus = $validated['order_status'];

        if ($newStatus === $oldStatus) {
            return back()->with('success', 'Trạng thái không thay đổi.');
        }

        // H5: mọi chuyển trạng thái qua một nguồn duy nhất canTransitOrderTo.
        // Terminal (completed/cancelled) không quay ngược, không resurrect.
        if (! $order->canTransitOrderTo($newStatus)) {
            return back()->with('error', "Không thể chuyển đơn hàng từ '{$oldStatus}' sang '{$newStatus}'.");
        }

        if ($newStatus === 'cancelled') {
            // Hủy qua RefundService (service sở hữu transaction): cancel order +
            // hồi kho IFF đơn cart (đơn batch chưa từng trừ kho) + tạo refund
            // pending nếu đã thu tiền. Tiền đi sau ở complete(), không giả định
            // hoàn ngay. Đơn completed không được cancel ở đây.
            try {
                $refundService->cancelAndRefundOrder(
                    $order->id,
                    [],
                    RefundService::REASON_ADMIN_CANCEL,
                    auth()->id()
                );
            } catch (RefundException $e) {
                return back()->with('error', 'Không thể hủy đơn hàng: '.$e->getMessage());
            }
        } else {
            // Lock + recheck để chống hai admin đổi trạng thái đồng thời.
            $moved = DB::transaction(function () use ($order, $newStatus) {
                $locked = Order::lockForUpdate()->find($order->id);

                if (! $locked || ! $locked->canTransitOrderTo($newStatus)) {
                    return false;
                }

                $updateData = ['order_status' => $newStatus];

                if ($newStatus === 'completed' && is_null($locked->delivered_at)) {
                    $updateData['delivered_at'] = now();
                }

                $locked->update($updateData);

                return true;
            });

            if (! $moved) {
                return back()->with('error', 'Trạng thái đơn hàng đã thay đổi (có thể đã được xử lý).');
            }
        }

        $order->user->notify(new OrderStatusChanged($order, $oldStatus, $newStatus));

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }

    /**
     * Admin xác nhận đã nhận tiền (đối soát thủ công ngoài hệ thống).
     * awaiting_confirmation → paid (+ reservation → converted nếu có).
     * Chạy trong transaction + lock + re-check để chống double-approve.
     */
    public function confirmPayment(Order $order)
    {
        $result = DB::transaction(function () use ($order) {
            $locked = Order::lockForUpdate()->find($order->id);

            if ($locked->payment_status !== Order::PAY_AWAITING) {
                return false;
            }

            // H4: không approve đơn đã cancelled (tiền về đơn hủy phải đi
            // đường refund thủ công, không tự paid ở đây).
            if ($locked->order_status === 'cancelled') {
                return false;
            }

            $locked->update(['payment_status' => Order::PAY_PAID]);

            if ($locked->reservation_id) {
                Reservation::where('id', $locked->reservation_id)
                    ->where('status', 'reserved')
                    ->update(['status' => 'converted']);
            }

            return true;
        });

        if (! $result) {
            return back()->with('error', 'Đơn hàng không ở trạng thái chờ xác nhận (có thể đã được xử lý).');
        }

        PaymentStatusUpdated::sendToUserAndAdmins(
            $order->user, $order, Order::PAY_AWAITING, Order::PAY_PAID
        );

        return back()->with('success', "Đã xác nhận nhận tiền đơn hàng #{$order->id}.");
    }

    /**
     * Admin xác nhận đã thu tiền COD.
     *
     * Flow chính thức: shipping + unpaid → collectCod → completed + paid.
     * Legacy compatibility: completed + unpaid (dữ liệu cũ) → collectCod → completed + paid.
     *
     * Lock + re-check to prevent double-submit / race conditions.
     * No Payment record created (Payment::type enum does not support COD).
     */
    public function collectCod(Order $order)
    {
        $result = DB::transaction(function () use ($order) {
            $locked = Order::lockForUpdate()->find($order->id);

            if ($locked->payment_method !== 'cod' || $locked->payment_status !== 'unpaid') {
                return false;
            }

            if ($locked->order_status === 'completed') {
                \Log::warning("COD legacy collect: order #{$locked->id} completed+unpaid collected.");
            } elseif ($locked->order_status !== 'shipping') {
                return false;
            }

            $locked->update([
                'payment_status' => 'paid',
                'order_status' => 'completed',
                'delivered_at' => $locked->delivered_at ?? now(),
            ]);

            return true;
        });

        if (! $result) {
            return back()->with('error', 'Đơn hàng không đủ điều kiện xác nhận thu tiền COD (có thể đã được xử lý).');
        }

        try {
            PaymentStatusUpdated::sendToUserAndAdmins(
                $order->user, $order, 'unpaid', 'paid'
            );
        } catch (\Throwable $e) {
            // Notification failure must not fail the transaction.
        }

        return back()->with('success', "Đã xác nhận thu tiền COD đơn hàng #{$order->id}.");
    }

    /**
     * Admin từ chối yêu cầu thanh toán. awaiting_confirmation → payment_rejected.
     * Lý do lưu trong notification (không thêm cột DB).
     */
    public function rejectPayment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reason = trim((string) ($validated['reason'] ?? ''));

        $result = DB::transaction(function () use ($order) {
            $locked = Order::lockForUpdate()->find($order->id);

            if ($locked->payment_status !== Order::PAY_AWAITING) {
                return false;
            }

            // H4: không reject đơn đã cancelled.
            if ($locked->order_status === 'cancelled') {
                return false;
            }

            $locked->update(['payment_status' => Order::PAY_REJECTED]);

            return true;
        });

        if (! $result) {
            return back()->with('error', 'Đơn hàng không ở trạng thái chờ xác nhận (có thể đã được xử lý).');
        }

        PaymentStatusUpdated::sendToUserAndAdmins(
            $order->user, $order, Order::PAY_AWAITING, Order::PAY_REJECTED,
            $reason !== '' ? $reason : null
        );

        return back()->with('success', "Đã từ chối thanh toán đơn hàng #{$order->id}.");
    }

    public function export()
    {
        $statusLabels = [
            'pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận',
            'shipping' => 'Đang giao', 'completed' => 'Đã giao', 'cancelled' => 'Đã hủy',
        ];
        $methodLabels = [
            'cod' => 'COD', 'qr' => 'Chuyển khoản QR',
            'balance' => 'Thanh toán qua đợt gom', 'cash' => 'Tiền mặt (admin thu hộ)',
        ];

        $orders = Order::with(['user', 'details'])->latest()->cursor();

        // Tính 1 lần bằng SQL (không N+1), cùng semantics với dashboard.
        $refunds = (float) RefundTransaction::monetary()->sum('amount');
        $forfeitures = (float) RefundTransaction::forfeitures()->sum('amount');

        // Cùng semantics với dashboard: gross tính mọi đơn paid (kể cả cancelled).
        $rows = function () use ($orders, $statusLabels, $methodLabels, $refunds, $forfeitures) {
            $count = 0;
            $revenue = 0;

            foreach ($orders as $order) {
                $count++;

                if ($order->payment_status === 'paid') {
                    $revenue += $order->total_amount;
                }

                yield [
                    $order->id,
                    $order->created_at->format('d/m/Y H:i'),
                    $order->customer_name,
                    $order->customer_phone,
                    $order->details->sum('quantity'),
                    $order->total_amount,
                    $methodLabels[$order->payment_method] ?? $order->payment_method,
                    Order::paymentLabel($order->payment_status),
                    $statusLabels[$order->order_status] ?? $order->order_status,
                ];
            }

            yield [];
            yield ['Tổng số đơn', $count];
            yield ['Gross đã thu (paid, kể cả cancelled)', $revenue];
            yield ['Hoàn tiền đã chi (completed)', $refunds];
            yield ['Doanh thu thuần (Net)', $revenue - $refunds];
            yield ['Tịch thu cọc (không trừ Net)', $forfeitures];
        };

        return $this->streamCsv(
            'don-hang-'.now()->format('Ymd-His').'.csv',
            ['Mã đơn', 'Ngày tạo', 'Khách hàng', 'SĐT', 'Số SP', 'Tổng tiền', 'Thanh toán', 'TT thanh toán', 'Trạng thái'],
            $rows()
        );
    }
}
