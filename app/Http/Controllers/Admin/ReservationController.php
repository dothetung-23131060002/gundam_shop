<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\LogsAdminActions;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    use LogsAdminActions;

    public function index(Request $request)
    {
        $query = Reservation::with(['user', 'batch.product'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $reservations = $query->paginate(10)->withQueryString();

        return view('admin.reservations.index', compact('reservations'));
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['user', 'batch.product', 'payments', 'refundTransactions', 'order']);

        return view('admin.reservations.show', compact('reservation'));
    }

    public function collectForm(Reservation $reservation)
    {
        if (! $reservation->needsPayment()) {
            return back()->with('error', 'Giữ slot này không cần thu hộ.');
        }

        $reservation->load(['user', 'batch.product']);

        return view('admin.reservations.collect', compact('reservation'));
    }

    public function collectBalance(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email',
            'shipping_address' => 'required|string|max:500',
            'collect_method' => 'required|in:cash,transfer',
            'reason' => 'nullable|string|max:500',
        ]);

        $reservation->loadMissing('batch');

        $order = DB::transaction(function () use ($reservation, $validated) {
            $locked = Reservation::lockForUpdate()->find($reservation->id);

            if (! $locked->needsPayment()) {
                throw ValidationException::withMessages([
                    'reservation' => 'Giữ slot này chưa cần hoặc đã thanh toán xong.',
                ]);
            }

            $locked->load('batch.product');

            $balance = $locked->balanceAmount();
            $methodLabel = $validated['collect_method'] === 'cash' ? 'tiền mặt' : 'chuyển khoản';

            $order = Order::create([
                'user_id' => $locked->user_id,
                'batch_id' => $locked->batch_id,
                'reservation_id' => $locked->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'shipping_address' => $validated['shipping_address'],
                'total_amount' => $locked->totalProductPrice(),
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'order_status' => 'pending',
            ]);

            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $locked->batch->product->id,
                'product_name' => $locked->batch->product->name,
                'price' => $locked->batch->product->price,
                'quantity' => $locked->quantity,
                'subtotal' => $locked->totalProductPrice(),
            ]);

            Payment::create([
                'user_id' => $locked->user_id,
                'reservation_id' => $locked->id,
                'order_id' => $order->id,
                'amount' => $balance,
                'type' => 'balance',
                'note' => "Admin thu hộ {$methodLabel} phần còn lại batch #{$locked->batch_id}",
            ]);

            $locked->update(['status' => 'converted']);

            return $order;
        });

        $this->logAdminAction('collect_balance', $reservation->batch, $reservation, $this->inputReason());

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "Đã thu hộ và tạo đơn hàng #{$order->id}.");
    }
}
