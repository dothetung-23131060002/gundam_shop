<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\RefundTransaction;
use App\Models\Reservation;
use App\Services\BatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'quantity' => 'required|integer|min:1|max:5',
        ]);

        $reservation = DB::transaction(function () use ($validated) {
            $batch = Batch::lockForUpdate()->find($validated['batch_id']);

            if (! $batch->isOpen()) {
                throw ValidationException::withMessages([
                    'batch_id' => 'Đợt gom này không còn nhận đặt cọc.',
                ]);
            }

            if ($batch->isExpired()) {
                throw ValidationException::withMessages([
                    'batch_id' => 'Đợt gom đã hết hạn.',
                ]);
            }

            $existingReservation = Reservation::where('user_id', auth()->id())
                ->where('batch_id', $batch->id)
                ->where('status', 'reserved')
                ->first();

            if ($existingReservation) {
                throw ValidationException::withMessages([
                    'batch_id' => 'Bạn đã giữ slot cho đợt gom này rồi.',
                ]);
            }

            $quantity = $validated['quantity'];
            $totalDeposit = $batch->deposit_amount * $quantity;

            $currentSlots = $batch->reservedSlotCount();
            if ($currentSlots + $quantity > $batch->threshold) {
                $availableSlots = $batch->threshold - $currentSlots;
                throw ValidationException::withMessages([
                    'quantity' => "Chỉ còn {$availableSlots} slot trống.",
                ]);
            }

            $reservation = Reservation::create([
                'user_id' => auth()->id(),
                'batch_id' => $batch->id,
                'quantity' => $quantity,
                'deposit_paid' => $totalDeposit,
                'status' => 'reserved',
            ]);

            Payment::create([
                'user_id' => auth()->id(),
                'reservation_id' => $reservation->id,
                'amount' => $totalDeposit,
                'type' => 'deposit',
                'note' => "Đặt cọc {$quantity} slot cho batch #{$batch->id} - {$batch->product->name}",
            ]);

            if ($batch->isFull()) {
                $batchService = app(BatchService::class);
                $batchService->markSuccess($batch);
            }

            return $reservation;
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', "Đã giữ slot thành công! Tổng cọc {$reservation->deposit_paid}đ.");
    }

    public function index()
    {
        $reservations = Reservation::with(['batch.product'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('reservations.index', compact('reservations'));
    }

    public function show(Reservation $reservation)
    {
        abort_unless($reservation->user_id === auth()->id(), 403);

        $reservation->load(['batch.product', 'payments', 'refundTransactions', 'order']);

        return view('reservations.show', compact('reservation'));
    }

    public function destroy(Reservation $reservation)
    {
        abort_unless($reservation->user_id === auth()->id(), 403);

        if (! $reservation->isCancellable()) {
            return back()->with('error', 'Không thể hủy giữ slot lúc này.');
        }

        DB::transaction(function () use ($reservation) {
            $depositPaid = $reservation->deposit_paid;

            if ($depositPaid > 0) {
                RefundTransaction::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $depositPaid,
                    'reason' => 'User tự hủy giữ slot',
                    'refunded_at' => now(),
                ]);

                Payment::create([
                    'user_id' => $reservation->user_id,
                    'reservation_id' => $reservation->id,
                    'amount' => $depositPaid,
                    'type' => 'refund',
                    'note' => 'Hoàn cọc tự động do user hủy giữ slot',
                ]);
            }

            $reservation->update([
                'deposit_paid' => 0,
                'status' => 'cancelled',
            ]);
        });

        return redirect()->route('reservations.index')
            ->with('success', 'Đã hủy giữ slot. Số tiền cọc đã được hoàn về tài khoản.');
    }

    public function payBalance(Reservation $reservation)
    {
        abort_unless($reservation->user_id === auth()->id(), 403);

        if (! $reservation->needsPayment()) {
            return back()->with('error', 'Lưu slot này chưa cần thanh toán phần còn lại.');
        }

        $reservation->load('batch.product');

        return view('reservations.pay-balance', compact('reservation'));
    }

    public function processBalancePayment(Request $request, Reservation $reservation)
    {
        abort_unless($reservation->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email',
            'shipping_address' => 'required|string|max:500',
        ]);

        $order = DB::transaction(function () use ($reservation, $validated) {
            $locked = Reservation::lockForUpdate()->find($reservation->id);

            if (! $locked->needsPayment()) {
                throw ValidationException::withMessages([
                    'reservation' => 'Lưu slot này chưa cần hoặc đã thanh toán xong.',
                ]);
            }

            $locked->load('batch.product');

            $balance = $locked->balanceAmount();

            $order = Order::create([
                'user_id' => auth()->id(),
                'batch_id' => $locked->batch_id,
                'reservation_id' => $locked->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'shipping_address' => $validated['shipping_address'],
                'total_amount' => $locked->totalProductPrice(),
                'payment_method' => 'balance',
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
                'user_id' => auth()->id(),
                'reservation_id' => $locked->id,
                'order_id' => $order->id,
                'amount' => $balance,
                'type' => 'balance',
                'note' => "Thanh toán phần còn lại batch #{$locked->batch_id} - {$locked->batch->product->name}",
            ]);

            $locked->update(['status' => 'converted']);

            return $order;
        });

        return redirect()->route('orders.show', $order)
            ->with('success', 'Thanh toán thành công! Đơn hàng của bạn đã được tạo.');
    }
}
