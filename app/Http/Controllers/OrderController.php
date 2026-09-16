<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\WarrantyRequest;
use App\Services\RefundService;

class OrderController extends Controller
{
    public function myOrders()
    {
        $orders = auth()->user()->orders()->latest()->paginate(10);

        return view('orders.mine', compact('orders'));
    }

    public function show(Order $order)
    {
        abort_unless(
            $order->user_id === auth()->id() || auth()->user()->role === 'admin',
            403
        );

        $userId = auth()->id();

        $order->load([
            'details' => function ($q) use ($userId) {
                $q->withCount([
                    'returnRequests as active_return_count' => function ($q2) use ($userId) {
                        $q2->where('requested_by', $userId)
                            ->whereIn('status', [
                                ReturnRequest::STATUS_REQUESTED,
                                ReturnRequest::STATUS_APPROVED,
                                ReturnRequest::STATUS_RECEIVED,
                            ]);
                    },
                    'warrantyRequests as active_warranty_count' => function ($q3) use ($userId) {
                        $q3->where('user_id', $userId)
                            ->whereIn('status', [
                                WarrantyRequest::STATUS_REQUESTED,
                                WarrantyRequest::STATUS_APPROVED,
                                WarrantyRequest::STATUS_PROCESSING,
                            ]);
                    },
                ]);
            },
            'details.returnRequests' => function ($q) use ($userId) {
                $q->where('requested_by', $userId)
                    ->whereNotIn('status', [
                        ReturnRequest::STATUS_REJECTED,
                        ReturnRequest::STATUS_CANCELLED,
                    ])
                    ->latest();
            },
            'details.warrantyRequests' => function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->whereNotIn('status', [
                        WarrantyRequest::STATUS_REJECTED,
                        WarrantyRequest::STATUS_CANCELLED,
                    ])
                    ->latest();
            },
        ]);

        $refundService = app(RefundService::class);
        $detailStates = [];

        foreach ($order->details as $detail) {
            $refundedQty = $refundService->refundedQuantityForDetail($detail->id);

            $pendingReturnQty = $detail->returnRequests
                ->whereIn('status', [
                    ReturnRequest::STATUS_REQUESTED,
                    ReturnRequest::STATUS_APPROVED,
                    ReturnRequest::STATUS_RECEIVED,
                ])
                ->sum('quantity');

            $pendingWarrantyQty = $detail->warrantyRequests
                ->whereIn('status', [
                    WarrantyRequest::STATUS_REQUESTED,
                    WarrantyRequest::STATUS_APPROVED,
                    WarrantyRequest::STATUS_PROCESSING,
                ])
                ->sum('quantity');

            $remaining = (int) $detail->quantity
                - $refundedQty
                - (int) $pendingReturnQty
                - (int) $pendingWarrantyQty;

            $detailStates[$detail->id] = [
                'remaining' => max(0, $remaining),
                'refunded_qty' => $refundedQty,
                'pending_return_qty' => (int) $pendingReturnQty,
                'pending_warranty_qty' => (int) $pendingWarrantyQty,
                'active_return' => $detail->returnRequests
                    ->whereIn('status', [
                        ReturnRequest::STATUS_REQUESTED,
                        ReturnRequest::STATUS_APPROVED,
                        ReturnRequest::STATUS_RECEIVED,
                    ])
                    ->first(),
                'active_warranty' => $detail->warrantyRequests
                    ->whereIn('status', [
                        WarrantyRequest::STATUS_REQUESTED,
                        WarrantyRequest::STATUS_APPROVED,
                        WarrantyRequest::STATUS_PROCESSING,
                    ])
                    ->first(),
            ];
        }

        return view('orders.show', compact('order', 'detailStates'));
    }
}
