<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RefundException;
use App\Http\Controllers\Admin\Concerns\LogsAdminActions;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchIncident;
use App\Services\BatchIncidentService;
use Illuminate\Http\Request;

class BatchIncidentController extends Controller
{
    use LogsAdminActions;

    public function index()
    {
        $incidents = BatchIncident::with(['batch.product', 'admin'])->latest()->paginate(10);

        return view('admin.batch_incidents.index', compact('incidents'));
    }

    public function create(Request $request)
    {
        $batches = Batch::with('product')->latest()->take(50)->get();
        $selectedBatch = $request->filled('batch_id') ? Batch::find($request->input('batch_id')) : null;

        return view('admin.batch_incidents.create', compact('batches', 'selectedBatch'));
    }

    public function store(Request $request, BatchIncidentService $incidents)
    {
        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'type' => 'required|in:'.implode(',', BatchIncident::TYPES),
            'description' => 'nullable|string|max:2000',
        ]);

        try {
            $incident = $incidents->record(
                (int) $validated['batch_id'],
                $validated['type'],
                $validated['description'] ?? null,
                auth()->id()
            );
        } catch (RefundException $e) {
            return back()->withInput()->with('error', 'Không thể ghi nhận sự cố: '.$e->getMessage());
        }

        $this->logAdminAction('report_incident', $incident->batch, null, "{$incident->type}: {$incident->description}");

        return redirect()->route('admin.batch-incidents.show', $incident)
            ->with('success', "Đã ghi nhận sự cố #{$incident->id}.");
    }

    public function show(BatchIncident $batchIncident)
    {
        $batchIncident->load(['batch.product', 'admin']);

        $orders = $batchIncident->batch->orders()
            ->with(['user', 'details'])
            ->where('order_status', '!=', 'cancelled')
            ->latest()
            ->take(50)
            ->get();

        return view('admin.batch_incidents.show', compact('batchIncident', 'orders'));
    }

    /**
     * Resolve incident: có order_id → resolve từng order (full phần còn lại);
     * không có → resolve cả đợt (chỉ supplier_shortage).
     */
    public function resolve(Request $request, BatchIncident $batchIncident, BatchIncidentService $incidents)
    {
        $validated = $request->validate([
            'order_id' => 'nullable|integer',
        ]);

        try {
            if (! empty($validated['order_id'])) {
                $refund = $incidents->resolveOrderRefund(
                    $batchIncident->id,
                    (int) $validated['order_id'],
                    [],
                    auth()->id()
                );

                $this->logAdminAction(
                    'resolve_incident_order',
                    $batchIncident->batch,
                    null,
                    "incident #{$batchIncident->id} → order #{$validated['order_id']}".($refund ? " (refund #{$refund->id})" : ' (cancel 0đ)')
                );

                $message = $refund
                    ? "Đã resolve order #{$validated['order_id']} (refund #{$refund->id})."
                    : "Đã hủy order #{$validated['order_id']} (chưa thu tiền, không hoàn).";
            } else {
                $summary = $incidents->resolveBatchShortage($batchIncident->id, auth()->id());

                $this->logAdminAction(
                    'resolve_incident_batch',
                    $batchIncident->batch,
                    null,
                    'incident #'.$batchIncident->id.': '.count($summary['refunded']).' refunded, '.count($summary['cancelled']).' cancelled, '.count($summary['skipped']).' skipped'
                );

                $message = 'Đã resolve cả đợt: '.count($summary['refunded']).' hoàn tiền, '
                    .count($summary['cancelled']).' hủy 0đ, '.count($summary['skipped']).' bỏ qua.';
            }
        } catch (RefundException $e) {
            return back()->with('error', 'Không thể resolve: '.$e->getMessage());
        }

        return back()->with('success', $message);
    }
}
