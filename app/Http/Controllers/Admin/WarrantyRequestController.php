<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RefundException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\LogsAdminActions;
use App\Models\WarrantyRequest;
use App\Services\WarrantyService;
use Illuminate\Http\Request;

class WarrantyRequestController extends Controller
{
    use LogsAdminActions;

    public function index(Request $request)
    {
        $query = WarrantyRequest::with(['order.user', 'orderDetail', 'user', 'handledBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $counts = [
            'requested' => WarrantyRequest::where('status', 'requested')->count(),
            'approved' => WarrantyRequest::where('status', 'approved')->count(),
            'processing' => WarrantyRequest::where('status', 'processing')->count(),
        ];

        $warranties = $query->latest()->paginate(10)->withQueryString();

        return view('admin.warranties.index', compact('warranties', 'counts'));
    }

    public function show(WarrantyRequest $warrantyRequest)
    {
        $warrantyRequest->load(['order.user', 'orderDetail', 'user', 'handledBy']);

        return view('admin.warranties.show', compact('warrantyRequest'));
    }

    public function approve(WarrantyRequest $warrantyRequest, WarrantyService $warrantyService)
    {
        try {
            $warrantyService->approve($warrantyRequest->id, auth()->id());
            $this->logAdminAction('warranty_approve', null, null, "Approved warranty #{$warrantyRequest->id}");
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã duyệt yêu cầu bảo hành.');
    }

    public function reject(WarrantyRequest $warrantyRequest, WarrantyService $warrantyService)
    {
        try {
            $warrantyService->reject($warrantyRequest->id, auth()->id());
            $this->logAdminAction('warranty_reject', null, null, "Rejected warranty #{$warrantyRequest->id}");
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã từ chối yêu cầu bảo hành.');
    }

    public function processing(WarrantyRequest $warrantyRequest, WarrantyService $warrantyService)
    {
        try {
            $warrantyService->processing($warrantyRequest->id, auth()->id());
            $this->logAdminAction('warranty_processing', null, null, "Warranty #{$warrantyRequest->id} moved to processing");
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã chuyển sang trạng thái đang xử lý.');
    }

    public function complete(Request $request, WarrantyRequest $warrantyRequest, WarrantyService $warrantyService)
    {
        $validated = $request->validate([
            'resolution' => 'required|in:part_replaced,runner_replaced,repaired,replaced_product',
        ]);

        try {
            $warrantyService->complete(
                $warrantyRequest->id,
                $validated['resolution'],
                auth()->id()
            );

            $this->logAdminAction('warranty_complete', null, null, "Warranty #{$warrantyRequest->id} completed with resolution: {$validated['resolution']}");
        } catch (RefundException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã hoàn thành xử lý bảo hành.');
    }
}
