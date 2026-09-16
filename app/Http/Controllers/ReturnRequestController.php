<?php

namespace App\Http\Controllers;

use App\Exceptions\RefundException;
use App\Models\ReturnRequest;
use App\Models\WarrantyRequest;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnRequestController extends Controller
{
    public function index()
    {
        $returns = ReturnRequest::with(['orderDetail', 'refundTransaction'])
            ->forUser(auth()->id())
            ->latest()
            ->paginate(10);

        return view('returns.mine', compact('returns'));
    }

    public function create(Request $request)
    {
        $order = auth()->user()->orders()
            ->with('details')
            ->where('id', $request->input('order_id'))
            ->first();

        if (! $order) {
            return redirect()->route('returns.mine')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        if ($order->order_status !== 'completed' || $order->payment_status !== 'paid') {
            return redirect()->route('returns.mine')
                ->with('error', 'Đơn hàng chưa hoàn thành hoặc chưa thanh toán.');
        }

        if (is_null($order->delivered_at)) {
            return redirect()->route('returns.mine')
                ->with('error', 'Đơn hàng chưa có thông tin giao hàng.');
        }

        if (now()->gt($order->delivered_at->copy()->addDays(3))) {
            return redirect()->route('returns.mine')
                ->with('error', 'Đã quá hạn 3 ngày đổi trả.');
        }

        $returnableDetails = $order->details->map(function ($detail) {
            $refundedQty = app(\App\Services\RefundService::class)
                ->refundedQuantityForDetail($detail->id);

            $pendingReturnQty = ReturnRequest::where('order_detail_id', $detail->id)
                ->whereIn('status', [ReturnRequest::STATUS_REQUESTED, ReturnRequest::STATUS_APPROVED, ReturnRequest::STATUS_RECEIVED])
                ->sum('quantity');

            $pendingWarrantyQty = WarrantyRequest::where('order_detail_id', $detail->id)
                ->active()
                ->sum('quantity');

            $remaining = (int) $detail->quantity - $refundedQty - (int) $pendingReturnQty - (int) $pendingWarrantyQty;

            return [
                'detail' => $detail,
                'remaining' => $remaining,
            ];
        })->filter(fn ($item) => $item['remaining'] > 0);

        $selectedDetailId = (int) $request->input('order_detail_id', 0);

        return view('returns.create', compact('order', 'returnableDetails', 'selectedDetailId'));
    }

    public function store(Request $request, ReturnService $returnService)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_detail_id' => 'required|exists:order_details,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
            'runner_condition' => 'nullable|in:sealed,opened,unknown',
        ]);

        // Double-submit protection (no schema change): an identical payload
        // submitted within 10s reuses the existing request instead of
        // creating a duplicate (e.g. double click).
        $recentDuplicate = ReturnRequest::where('requested_by', auth()->id())
            ->where('order_id', $validated['order_id'])
            ->where('order_detail_id', $validated['order_detail_id'])
            ->where('quantity', $validated['quantity'])
            ->where('reason', $validated['reason'])
            ->where('created_at', '>=', now()->subSeconds(10))
            ->latest('id')
            ->first();

        if ($recentDuplicate) {
            return redirect()->route('returns.show', $recentDuplicate)
                ->with('success', "Yêu cầu trả hàng #{$recentDuplicate->id} đã được gửi.");
        }

        try {
            $return = $returnService->request(
                (int) $validated['order_id'],
                (int) $validated['order_detail_id'],
                (int) $validated['quantity'],
                $validated['reason'],
                auth()->id(),
                $validated['runner_condition'] ?? null,
            );
        } catch (RefundException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('returns.show', $return)
            ->with('success', "Đã gửi yêu cầu trả hàng #{$return->id}.");
    }

    public function show(ReturnRequest $returnRequest)
    {
        abort_unless(
            $returnRequest->requested_by === auth()->id() || auth()->user()->role === 'admin',
            403
        );

        $returnRequest->load(['order', 'orderDetail', 'refundTransaction', 'handledBy']);

        return view('returns.show', compact('returnRequest'));
    }

    public function cancel(ReturnRequest $returnRequest, ReturnService $returnService)
    {
        abort_unless($returnRequest->requested_by === auth()->id(), 403);

        try {
            $returnService->cancel($returnRequest->id, auth()->id());
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã hủy yêu cầu trả hàng.');
    }
}
