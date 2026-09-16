<?php

namespace App\Http\Controllers;

use App\Exceptions\RefundException;
use App\Models\ReturnRequest;
use App\Models\WarrantyRequest;
use App\Services\RefundService;
use App\Services\WarrantyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WarrantyRequestController extends Controller
{
    public function index()
    {
        $warranties = WarrantyRequest::with(['orderDetail', 'order'])
            ->forUser(auth()->id())
            ->latest()
            ->paginate(10);

        return view('warranties.mine', compact('warranties'));
    }

    public function create(Request $request)
    {
        $order = auth()->user()->orders()
            ->with('details')
            ->where('id', $request->input('order_id'))
            ->first();

        if (! $order) {
            return redirect()->route('warranties.mine')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        if ($order->order_status !== 'completed' || $order->payment_status !== 'paid') {
            return redirect()->route('warranties.mine')
                ->with('error', 'Đơn hàng chưa hoàn thành hoặc chưa thanh toán.');
        }

        if (is_null($order->delivered_at)) {
            return redirect()->route('warranties.mine')
                ->with('error', 'Đơn hàng chưa có thông tin giao hàng.');
        }

        $warrantableDetails = $order->details->map(function ($detail) {
            $refundedQty = app(RefundService::class)
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

        return view('warranties.create', compact('order', 'warrantableDetails', 'selectedDetailId'));
    }

    public function store(Request $request, WarrantyService $warrantyService)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_detail_id' => 'required|exists:order_details,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
            'description' => 'nullable|string|max:2000',
            'evidence' => 'required|array|min:1|max:5',
            'evidence.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // Double-submit protection (no schema change): an identical payload
        // submitted within 10s reuses the existing request instead of
        // creating a duplicate (e.g. double click).
        $recentDuplicate = WarrantyRequest::where('user_id', auth()->id())
            ->where('order_id', $validated['order_id'])
            ->where('order_detail_id', $validated['order_detail_id'])
            ->where('quantity', $validated['quantity'])
            ->where('reason', $validated['reason'])
            ->where('created_at', '>=', now()->subSeconds(10))
            ->latest('id')
            ->first();

        if ($recentDuplicate) {
            return redirect()->route('warranties.show', $recentDuplicate)
                ->with('success', "Yêu cầu bảo hành #{$recentDuplicate->id} đã được gửi.");
        }

        // Keep UploadedFile instances in memory for this request lifecycle (no tmp pre-storage).
        $uploadedFiles = $request->file('evidence');
        if (! is_array($uploadedFiles)) {
            $uploadedFiles = $uploadedFiles ? [$uploadedFiles] : [];
        }
        $uploadedFiles = array_values(array_filter($uploadedFiles));

        try {
            // Placeholder names preserve 1-5 count rule for WarrantyService::request().
            // Real files are validated + stored right after we have the warranty ID.
            $evidencePlaceholders = array_map(
                fn ($file) => $file->getClientOriginalName(),
                $uploadedFiles
            );

            // First create warranty to get ID for evidence storage
            $warranty = $warrantyService->request(
                (int) $validated['order_id'],
                (int) $validated['order_detail_id'],
                (int) $validated['quantity'],
                $validated['reason'],
                $validated['description'] ?? null,
                auth()->id(),
                $evidencePlaceholders,
            );

            // Store evidence files
            try {
                $evidencePaths = $warrantyService->validateAndStoreEvidence(
                    $uploadedFiles,
                    $warranty->id
                );
            } catch (\Throwable $e) {
                // Avoid orphan warranty + partial files when storage/validation fails.
                Storage::disk('public')->deleteDirectory("warranty-evidence/{$warranty->id}");
                $warranty->delete();

                throw $e;
            }

            $warranty->update(['evidence' => $evidencePaths]);
        } catch (RefundException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('warranties.show', $warranty)
            ->with('success', "Đã gửi yêu cầu bảo hành #{$warranty->id}.");
    }

    public function show(WarrantyRequest $warrantyRequest)
    {
        abort_unless(
            $warrantyRequest->user_id === auth()->id() || auth()->user()->role === 'admin',
            403
        );

        $warrantyRequest->load(['order', 'orderDetail', 'handledBy']);

        return view('warranties.show', compact('warrantyRequest'));
    }

    public function cancel(WarrantyRequest $warrantyRequest, WarrantyService $warrantyService)
    {
        abort_unless($warrantyRequest->user_id === auth()->id(), 403);

        try {
            $warrantyService->cancel($warrantyRequest->id, auth()->id());
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã hủy yêu cầu bảo hành.');
    }
}
