<?php

namespace App\Http\Controllers;

use App\Models\Order;

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

        $order->load('details');

        return view('orders.show', compact('order'));
    }
}
