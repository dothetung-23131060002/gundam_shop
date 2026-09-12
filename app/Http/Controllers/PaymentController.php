<?php

namespace App\Http\Controllers;

use App\Models\Order;

class PaymentController extends Controller
{
    public function qr(Order $order)
    {
        abort_unless(
            $order->user_id === auth()->id() || auth()->user()->role === 'admin',
            403
        );

        return view('payment.qr', compact('order'));
    }

    public function confirm(Order $order)
    {
        abort_unless(
            $order->user_id === auth()->id() || auth()->user()->role === 'admin',
            403
        );

        if ($order->payment_status === 'paid') {
            return back()->with('error', 'Đơn hàng đã được thanh toán.');
        }

        $order->update([
            'payment_status' => 'paid',
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Thanh toán đã được ghi nhận.');
    }
}
