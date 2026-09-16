<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RefundException;
use App\Http\Controllers\Admin\Concerns\LogsAdminActions;
use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\RefundService;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReturnRequestController extends Controller
{
    use LogsAdminActions;

    public function index(Request $request)
    {
        $query = ReturnRequest::with(['order.user', 'orderDetail', 'requestedBy', 'handledBy'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->whereHas('order.user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $returns = $query->paginate(10)->withQueryString();

        $counts = [
            'requested' => ReturnRequest::where('status', ReturnRequest::STATUS_REQUESTED)->count(),
            'approved' => ReturnRequest::where('status', ReturnRequest::STATUS_APPROVED)->count(),
            'received' => ReturnRequest::where('status', ReturnRequest::STATUS_RECEIVED)->count(),
        ];

        return view('admin.returns.index', compact('returns', 'counts'));
    }

    public function show(ReturnRequest $returnRequest)
    {
        $returnRequest->load(['order.user', 'orderDetail', 'requestedBy', 'handledBy', 'refundTransaction']);

        return view('admin.returns.show', compact('returnRequest'));
    }

    public function approve(ReturnRequest $returnRequest, ReturnService $returnService)
    {
        try {
            $returnService->approve($returnRequest->id, auth()->id());

            $this->logAdminAction(
                'return_approve',
                null,
                null,
                "ReturnRequest #{$returnRequest->id}: approved"
            );
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã approve ReturnRequest #{$returnRequest->id}.");
    }

    public function reject(ReturnRequest $returnRequest, ReturnService $returnService)
    {
        try {
            $returnService->reject($returnRequest->id, auth()->id());

            $this->logAdminAction(
                'return_reject',
                null,
                null,
                "ReturnRequest #{$returnRequest->id}: rejected"
            );
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã reject ReturnRequest #{$returnRequest->id}.");
    }

    public function receive(ReturnRequest $returnRequest, ReturnService $returnService)
    {
        try {
            $returnService->receive($returnRequest->id, auth()->id());

            $this->logAdminAction(
                'return_receive',
                null,
                null,
                "ReturnRequest #{$returnRequest->id}: received"
            );
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã đánh dấu nhận hàng ReturnRequest #{$returnRequest->id}.");
    }

    public function inspect(Request $request, ReturnRequest $returnRequest, ReturnService $returnService)
    {
        $validated = $request->validate([
            'inspection' => 'required|in:resellable,defective,dispose',
        ]);

        try {
            $returnService->inspect($returnRequest->id, $validated['inspection'], auth()->id());

            $this->logAdminAction(
                'return_inspect',
                null,
                null,
                "ReturnRequest #{$returnRequest->id}: inspection={$validated['inspection']}"
            );
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã inspect ReturnRequest #{$returnRequest->id}.");
    }

    public function completeWithoutReturn(Request $request, ReturnRequest $returnRequest, ReturnService $returnService)
    {
        $validated = $request->validate([
            'refund_reason' => ['required', Rule::in(RefundService::ORDER_REASONS)],
        ]);

        try {
            $result = $returnService->completeWithoutReturn($returnRequest->id, auth()->id(), $validated['refund_reason']);

            $this->logAdminAction(
                'return_complete',
                null,
                null,
                "ReturnRequest #{$returnRequest->id}: completed (no-return) → refund #{$result->refund_transaction_id}"
            );
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã hoàn thành ReturnRequest #{$returnRequest->id} (không cần thu hồi).");
    }

    public function complete(Request $request, ReturnRequest $returnRequest, ReturnService $returnService)
    {
        $validated = $request->validate([
            'restocked_qty' => 'required|integer|min:0',
            'refund_reason' => ['required', Rule::in(RefundService::ORDER_REASONS)],
        ]);

        try {
            $result = $returnService->complete($returnRequest->id, (int) $validated['restocked_qty'], auth()->id(), $validated['refund_reason']);

            $restockNote = ($returnRequest->inspection === ReturnRequest::INSPECTION_RESELLABLE && $validated['restocked_qty'] > 0)
                ? " + restock {$validated['restocked_qty']}"
                : '';

            $this->logAdminAction(
                'return_complete',
                null,
                null,
                "ReturnRequest #{$returnRequest->id}: completed → refund #{$result->refund_transaction_id}{$restockNote}"
            );
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã hoàn thành ReturnRequest #{$returnRequest->id}.");
    }
}
