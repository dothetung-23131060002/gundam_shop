<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        if (auth()->guest()) {
            return back()->with('error', 'Vui lòng đăng nhập để đánh giá.');
        }

        $alreadyReviewed = Review::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->exists();

        if ($alreadyReviewed) {
            return back()->with('error', 'Bạn đã đánh giá sản phẩm này rồi.');
        }

        $hasPurchased = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.user_id', auth()->id())
            ->where('order_details.product_id', $product->id)
            ->where('orders.order_status', 'completed')
            ->where('orders.payment_status', 'paid')
            ->exists();

        if (! $hasPurchased) {
            return back()->with('error', 'Chỉ khách đã mua hàng mới được đánh giá sản phẩm này.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Review::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return back()->with('success', 'Đánh giá đã được gửi.');
    }
}
