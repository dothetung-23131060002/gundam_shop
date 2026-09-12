<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundTransaction;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $query = RefundTransaction::with(['reservation.user', 'reservation.batch.product'])->latest();

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->whereHas('reservation.user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $refunds = $query->paginate(10)->withQueryString();

        $totalRefunded = RefundTransaction::sum('amount');

        return view('admin.refunds.index', compact('refunds', 'totalRefunded'));
    }

    public function show(RefundTransaction $refund)
    {
        $refund->load(['reservation.user', 'reservation.batch.product', 'reservation.payments']);

        return view('admin.refunds.show', compact('refund'));
    }
}
