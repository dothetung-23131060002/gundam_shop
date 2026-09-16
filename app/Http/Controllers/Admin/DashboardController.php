<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Order;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\Reservation;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $totalUsers = User::count();
        $totalOrders = Order::count();

        // Accounting contract: Gross = paid (kể cả cancelled);
        // Refund = completed monetary (loại forfeiture); Net = Gross - Refund.
        $grossCollected = (float) Order::paid()->sum('total_amount');
        $successfulRefunds = (float) RefundTransaction::monetary()->sum('amount');
        $netRevenue = $grossCollected - $successfulRefunds;
        $forfeitureIncome = (float) RefundTransaction::forfeitures()->sum('amount');
        $newOrders = Order::where('order_status', 'pending')->latest()->take(5)->get();

        $totalBatches = Batch::count();
        $openBatches = Batch::where('status', 'open')->count();
        $totalReservations = Reservation::where('status', 'reserved')->count();

        $closedBatches = Batch::whereIn('status', ['success', 'failed'])->count();
        $successBatches = Batch::where('status', 'success')->count();
        $successRate = $closedBatches > 0 ? round($successBatches / $closedBatches * 100, 1) : 0;
        $failedRate = $closedBatches > 0 ? round(100 - $successRate, 1) : 0;

        $heldDeposit = (float) Reservation::where('status', 'reserved')->sum('deposit_paid');

        $topProducts = Product::select('products.id', 'products.name')
            ->join('batches', 'batches.product_id', '=', 'products.id')
            ->join('reservations', function ($join) {
                $join->on('reservations.batch_id', '=', 'batches.id')
                    ->where('reservations.status', 'reserved');
            })
            ->groupBy('products.id', 'products.name')
            ->selectRaw('SUM(reservations.quantity) as total_slots, SUM(reservations.deposit_paid) as total_deposit')
            ->orderByDesc('total_slots')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalProducts', 'totalUsers', 'totalOrders',
            'grossCollected', 'successfulRefunds', 'netRevenue', 'forfeitureIncome', 'newOrders',
            'totalBatches', 'openBatches', 'totalReservations',
            'successRate', 'failedRate', 'heldDeposit', 'topProducts'
        ));
    }
}
