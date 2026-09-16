<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::with(['product.openBatch', 'product.category', 'product.brand'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('wishlist.index', compact('wishlists'));
    }

    public function toggle(Product $product)
    {
        $userId = auth()->id();

        $existing = Wishlist::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'status' => 'removed',
                'wishlist_count' => $this->countFor($product->id),
            ]);
        }

        try {
            Wishlist::firstOrCreate([
                'user_id' => $userId,
                'product_id' => $product->id,
            ]);
        } catch (QueryException $e) {
            if (! $this->isDuplicateEntry($e)) {
                throw $e;
            }

            // Lost the race: re-read the final state instead of 500.
            $current = Wishlist::where('user_id', $userId)
                ->where('product_id', $product->id)
                ->first();

            return response()->json([
                'status' => $current ? 'added' : 'removed',
                'wishlist_count' => $this->countFor($product->id),
            ]);
        }

        return response()->json([
            'status' => 'added',
            'wishlist_count' => $this->countFor($product->id),
        ]);
    }

    public function destroy(Wishlist $wishlist)
    {
        abort_unless($wishlist->user_id === auth()->id(), 403);

        $wishlist->delete();

        return back()->with('success', 'Đã xóa khỏi danh sách yêu thích.');
    }

    protected function countFor(int $productId): int
    {
        return Wishlist::where('product_id', $productId)->count();
    }

    protected function isDuplicateEntry(QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }
}
