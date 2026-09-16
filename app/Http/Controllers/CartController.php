<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return view('cart.index', compact('cart', 'total'));
    }

    public function add(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session()->get('cart', []);
        $newQuantity = (int) $validated['quantity'] + (int) ($cart[$product->id]['quantity'] ?? 0);

        // Stock guard (UX fast-fail). Checkout re-checks under lock to stop races.
        if ($newQuantity > $product->quantity) {
            return back()->with('error', "Sản phẩm {$product->name} chỉ còn {$product->quantity} sản phẩm.");
        }

        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] = $newQuantity;
        } else {
            $cart[$product->id] = [
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $validated['quantity'],
                'image' => $product->image,
                'image_url' => $product->image_url,
            ];
        }

        session()->put('cart', $cart);

        return redirect()->route('cart.index')
            ->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Stock guard (UX fast-fail). Checkout re-checks under lock to stop races.
        if ((int) $validated['quantity'] > $product->quantity) {
            return back()->with('error', "Sản phẩm {$product->name} chỉ còn {$product->quantity} sản phẩm.");
        }

        $cart = session()->get('cart', []);

        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] = (int) $validated['quantity'];
        }

        session()->put('cart', $cart);

        return redirect()->route('cart.index')
            ->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function remove(Product $product)
    {
        $cart = session()->get('cart', []);

        unset($cart[$product->id]);

        session()->put('cart', $cart);

        return redirect()->route('cart.index')
            ->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function clear()
    {
        session()->forget('cart');

        return redirect()->route('cart.index')
            ->with('success', 'Đã xóa toàn bộ giỏ hàng.');
    }
}
