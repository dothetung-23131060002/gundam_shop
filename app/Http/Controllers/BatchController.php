<?php

namespace App\Http\Controllers;

use App\Models\Batch;

class BatchController extends Controller
{
    public function index()
    {
        $batches = Batch::with(['product.category', 'product.brand'])
            ->withSum(['reservations as reserved_slots' => function ($query) {
                $query->where('status', 'reserved');
            }], 'quantity')
            ->where('status', 'open')
            ->where('deadline', '>', now())
            ->latest()
            ->paginate(9);

        return view('batches.index', compact('batches'));
    }

    public function show(Batch $batch)
    {
        abort_unless($batch->isOpen(), 404);

        $batch->load(['product.category', 'product.brand']);

        $reservedCount = $batch->reservedSlotCount();
        $progressPercent = $batch->progressPercent();
        $timeRemaining = $batch->deadline->diffForHumans();

        $userReservation = null;
        if (auth()->check()) {
            $userReservation = $batch->reservations()
                ->where('user_id', auth()->id())
                ->where('status', 'reserved')
                ->first();
        }

        return view('batches.show', compact('batch', 'reservedCount', 'progressPercent', 'timeRemaining', 'userReservation'));
    }

    public function progress(Batch $batch)
    {
        $reserved = $batch->reservedSlotCount();

        return response()->json([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'reserved' => $reserved,
            'threshold' => $batch->threshold,
            'percent' => $batch->progressPercent(),
            'is_full' => $batch->isFull(),
            'is_open' => $batch->isOpen() && ! $batch->isExpired(),
            'time_remaining' => $batch->isExpired() ? 'Đã hết hạn' : $batch->deadline->diffForHumans(),
        ]);
    }
}
