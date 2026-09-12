<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ExportsCsv;
use App\Http\Controllers\Admin\Concerns\LogsAdminActions;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Services\BatchService;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    use ExportsCsv, LogsAdminActions;

    protected function filteredQuery(Request $request)
    {
        $query = Batch::with(['product'])
            ->withSum(['reservations as reserved_slots' => function ($q) {
                $q->where('status', 'reserved');
            }], 'quantity')
            ->withCount('reservations as total_reservations');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->whereHas('product', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $batches = $this->filteredQuery($request)->latest()->paginate(10)->withQueryString();

        return view('admin.batches.index', compact('batches'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();

        return view('admin.batches.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'threshold' => 'required|integer|min:2',
            'deposit_amount' => 'required|numeric|min:1000',
            'deadline' => 'required|date|after:now',
        ]);

        Batch::create($validated);

        return redirect()->route('admin.batches.index')
            ->with('success', 'Đã tạo đợt gom hàng mới.');
    }

    public function show(Batch $batch)
    {
        $batch->load(['product', 'activeReservations.user']);

        $reservedCount = $batch->reservedSlotCount();
        $progressPercent = $batch->progressPercent();
        $timeRemaining = $batch->deadline->isPast() ? 'Đã hết hạn' : $batch->deadline->diffForHumans();

        return view('admin.batches.show', compact('batch', 'reservedCount', 'progressPercent', 'timeRemaining'));
    }

    public function edit(Batch $batch)
    {
        $products = Product::orderBy('name')->get();

        return view('admin.batches.edit', compact('batch', 'products'));
    }

    public function update(Request $request, Batch $batch)
    {
        if ($batch->status !== 'open') {
            return back()->with('error', 'Chỉ có thể chỉnh sửa đợt gom đang mở.');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'threshold' => 'required|integer|min:2',
            'deposit_amount' => 'required|numeric|min:1000',
            'deadline' => 'required|date',
        ]);

        $batch->update($validated);

        return redirect()->route('admin.batches.index')
            ->with('success', 'Đã cập nhật đợt gom hàng.');
    }

    public function destroy(Batch $batch)
    {
        if ($batch->status !== 'open') {
            return back()->with('error', 'Chỉ có thể xóa đợt gom đang mở.');
        }

        if ($batch->reservations()->exists()) {
            return back()->with('error', 'Không thể xóa đợt gom đã có khách giữ slot (kể cả lịch sử hoàn cọc) — refund_transactions là bằng chứng đối soát.');
        }

        $batch->delete();

        return redirect()->route('admin.batches.index')
            ->with('success', 'Đã xóa đợt gom hàng.');
    }

    public function forceSuccess(Batch $batch, BatchService $batchService)
    {
        if ($batch->status !== 'open') {
            return back()->with('error', 'Chỉ có thể chốt thành công đợt gom đang mở.');
        }

        $batchService->markSuccess($batch);
        $this->logAdminAction('force_success', $batch, null, $this->inputReason());

        return back()->with('success', "Đã BUỘC thành công (demo) đợt gom #{$batch->id}.");
    }

    public function closeEarly(Batch $batch, BatchService $batchService)
    {
        if ($batch->status !== 'open') {
            return back()->with('error', 'Chỉ có thể đóng sớm đợt gom đang mở.');
        }

        if (! $batch->isFull()) {
            return back()->with('error', 'Chưa đủ ngưỡng — muốn ép thì dùng Buộc thành công (demo).');
        }

        $batchService->markSuccess($batch);
        $this->logAdminAction('close_early', $batch, null, $this->inputReason());

        return back()->with('success', "Đã đóng sớm đợt gom #{$batch->id} (đủ ngưỡng).");
    }

    public function forceFail(Batch $batch, BatchService $batchService)
    {
        if ($batch->status !== 'open') {
            return back()->with('error', 'Chỉ có thể chốt thất bại đợt gom đang mở.');
        }

        $batchService->markFailed($batch, 'Admin chốt thất bại thủ công');
        $this->logAdminAction('force_fail', $batch, null, $this->inputReason('Admin chốt thất bại thủ công'));

        return back()->with('success', "Đã chốt thất bại đợt gom #{$batch->id}. Đã hoàn cọc cho tất cả.");
    }

    public function export(Request $request)
    {
        $statusLabels = [
            'open' => 'Đang mở', 'success' => 'Thành công',
            'failed' => 'Thất bại', 'processing' => 'Đang xử lý',
        ];

        $batches = $this->filteredQuery($request)->latest()->cursor();

        $rows = function () use ($batches, $statusLabels) {
            $held = 0;

            foreach ($batches as $batch) {
                $reserved = $batch->reservedSlotCount();
                $held += $batch->deposit_amount * $reserved;

                yield [
                    $batch->id,
                    $batch->product->name ?? 'N/A',
                    $batch->threshold,
                    $reserved,
                    $batch->progressPercent().'%',
                    $batch->deposit_amount,
                    $batch->deadline->format('d/m/Y H:i'),
                    $statusLabels[$batch->status] ?? $batch->status,
                ];
            }

            yield [];
            yield ['Tổng cọc đang giữ', $held];
        };

        return $this->streamCsv(
            'dot-gom-'.now()->format('Ymd-His').'.csv',
            ['Mã đợt', 'Sản phẩm', 'Ngưỡng', 'Đã cọc', 'Tiến độ', 'Cọc/slot', 'Deadline', 'Trạng thái'],
            $rows()
        );
    }
}
