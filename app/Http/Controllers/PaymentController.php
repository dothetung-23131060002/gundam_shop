<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Notifications\PaymentStatusUpdated;
use App\Services\VietQrService;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function qr(Order $order)
    {
        abort_unless(
            $order->user_id === auth()->id() || auth()->user()->role === 'admin',
            403
        );

        $paymentOptions = VietQrService::buildOptions(
            $order->total_amount,
            VietQrService::orderAddInfo($order->id)
        );

        return view('payment.qr', compact('order', 'paymentOptions'));
    }

    public function confirm(Order $order)
    {
        abort_unless(
            $order->user_id === auth()->id() || auth()->user()->role === 'admin',
            403
        );

        if (! VietQrService::hasValidMethods()) {
            return back()->with('error', 'Chưa cấu hình phương thức thanh toán. Vui lòng liên hệ shop.');
        }

        if (! VietQrService::isValidAmount($order->total_amount)) {
            return back()->with('error', 'Số tiền thanh toán không hợp lệ.');
        }

        // H1: mọi kiểm tra trạng thái + mutation nằm trong transaction với
        // lock + re-read + recheck — chống admin approve và customer confirm
        // chạy đồng thời làm lật trạng thái paid về awaiting.
        $outcome = DB::transaction(function () use ($order) {
            $locked = Order::lockForUpdate()->find($order->id);

            if (! $locked) {
                return 'missing';
            }

            if ($locked->payment_status === Order::PAY_PAID) {
                return 'paid';
            }

            // COD không đi flow chuyển khoản xác thực.
            if ($locked->payment_method === 'cod') {
                return 'cod';
            }

            if ($locked->payment_status === Order::PAY_AWAITING) {
                return 'awaiting';
            }

            // Khách chỉ được pending_payment/payment_rejected → awaiting_confirmation.
            // Tuyệt đối không cho khách tự chuyển sang paid.
            if (! $locked->canTransitPaymentTo(Order::PAY_AWAITING)) {
                return 'invalid';
            }

            $oldStatus = $locked->payment_status;
            $locked->update(['payment_status' => Order::PAY_AWAITING]);

            return $oldStatus;
        });

        if ($outcome === 'paid') {
            return back()->with('error', 'Đơn hàng đã được thanh toán.');
        }

        if ($outcome === 'cod') {
            return back()->with('error', 'Đơn COD thanh toán khi nhận hàng, không cần xác nhận chuyển khoản.');
        }

        if ($outcome === 'awaiting') {
            return back()->with('info', 'Đã gửi yêu cầu xác nhận thanh toán. Shop đang kiểm tra giao dịch.');
        }

        if ($outcome === 'invalid' || $outcome === 'missing') {
            return back()->with('error', 'Trạng thái đơn hàng không cho phép xác nhận lúc này.');
        }

        // Notify sau commit, không để notification làm fail luồng thanh toán.
        PaymentStatusUpdated::sendToUserAndAdmins($order->user, $order, $outcome, Order::PAY_AWAITING);

        return redirect()->route('payment.qr', $order)
            ->with('success', 'Đã gửi yêu cầu xác nhận thanh toán. Shop đang kiểm tra giao dịch.');
    }
}
