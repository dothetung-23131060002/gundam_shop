<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundTransaction;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $query = RefundTransaction::with(['reservation.user', 'reservation.batch.product', 'order.user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('reservation.user', function ($sq) use ($keyword) {
                    $sq->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                })->orWhereHas('order.user', function ($sq) use ($keyword) {
                    $sq->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            });
        }

        $refunds = $query->paginate(10)->withQueryString();

        // Accounting contract: only completed monetary refunds count
        // (excludes pending/failed/forfeiture), same as dashboard Net Revenue.
        $totalRefunded = RefundTransaction::monetary()->sum('amount');

        return view('admin.refunds.index', compact('refunds', 'totalRefunded'));
    }

    public function show(RefundTransaction $refund)
    {
        $refund->load(['reservation.user', 'reservation.batch.product', 'reservation.payments', 'order.user', 'details']);

        return view('admin.refunds.show', compact('refund'));
    }
}
