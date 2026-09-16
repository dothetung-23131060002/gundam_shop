<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('error', 'Giỏ hàng đang trống.');
        }

        $total = 0;

        foreach ($cart as $productId => $item) {
            $product = Product::findOrFail($productId);
            $quantity = max(1, (int) $item['quantity']);
            $total += $product->price * $quantity;
        }

        return view('checkout.index', compact('cart', 'total'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email',
            'shipping_address' => 'required|string|max:500',
            'payment_method' => 'required|in:cod,qr',
        ]);

        $cart = session('cart', []);

        if (empty($cart)) {
            return back()->with('error', 'Giỏ hàng đang trống.');
        }

        $order = DB::transaction(function () use ($validated, $cart) {
            $total = 0;
            $lockedProducts = [];

            foreach ($cart as $productId => $item) {
                $product = Product::lockForUpdate()->find($productId);
                if (! $product) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Sản phẩm không tồn tại.',
                    ]);
                }
                $quantity = max(1, (int) $item['quantity']);

                if ($quantity > $product->quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Sản phẩm '.$product->name.' không đủ số lượng.',
                    ]);
                }

                $total += $product->price * $quantity;
                $lockedProducts[$productId] = ['product' => $product, 'quantity' => $quantity];
            }

            $order = Order::create([
                'user_id' => auth()->id(),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'shipping_address' => $validated['shipping_address'],
                'total_amount' => $total,
                'payment_method' => $validated['payment_method'],
                // QR/MoMo đi flow xác thực thủ công; COD giữ legacy unpaid.
                'payment_status' => $validated['payment_method'] === 'qr'
                    ? Order::PAY_PENDING
                    : 'unpaid',
                'order_status' => 'pending',
            ]);

            foreach ($lockedProducts as $productId => $data) {
                $product = $data['product'];
                $quantity = $data['quantity'];

                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                ]);

                $product->decrement('quantity', $quantity);
            }

            return $order;
        });

        session()->forget('cart');

        if ($order->payment_method === 'qr') {
            return redirect()->route('payment.qr', $order);
        }

        return redirect()->route('orders.show', $order);
    }

    public function success(Order $order)
    {
        abort_unless(
            $order->user_id === auth()->id() || auth()->user()->role === 'admin',
            403
        );

        $order->load('details');

        return view('checkout.success', compact('order'));
    }
}
