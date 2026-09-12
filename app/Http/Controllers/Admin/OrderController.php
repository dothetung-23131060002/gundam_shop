<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ExportsCsv;

    public function index()
    {
        $orders = Order::with('user')->latest()->paginate(10);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'details.product', 'batch']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:pending,confirmed,shipping,completed,cancelled',
        ]);

        $oldStatus = $order->order_status;
        $newStatus = $validated['order_status'];

        if ($newStatus === $oldStatus) {
            return back()->with('success', 'Trạng thái không thay đổi.');
        }

        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            DB::transaction(function () use ($order) {
                foreach ($order->details as $detail) {
                    $detail->product()->increment('quantity', $detail->quantity);
                }
                $order->update(['order_status' => 'cancelled']);
            });
        } else {
            $order->update(['order_status' => $newStatus]);
        }

        $order->user->notify(new OrderStatusChanged($order, $oldStatus, $newStatus));

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
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

        $rows = function () use ($orders, $statusLabels, $methodLabels) {
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
                    $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán',
                    $statusLabels[$order->order_status] ?? $order->order_status,
                ];
            }

            yield [];
            yield ['Tổng số đơn', $count];
            yield ['Doanh thu đã thu (paid)', $revenue];
        };

        return $this->streamCsv(
            'don-hang-'.now()->format('Ymd-His').'.csv',
            ['Mã đơn', 'Ngày tạo', 'Khách hàng', 'SĐT', 'Số SP', 'Tổng tiền', 'Thanh toán', 'TT thanh toán', 'Trạng thái'],
            $rows()
        );
    }
}
