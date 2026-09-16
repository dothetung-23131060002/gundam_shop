<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'price',
        'quantity',
        'image',
        'description',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Batch đang mở hiện tại (READ-only, không chạm Batch logic).
     */
    public function openBatch()
    {
        return $this->hasOne(Batch::class)
            ->where('status', 'open')
            ->where('deadline', '>', now())
            ->latest('id');
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }

    public function isWishlistedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('wishlists')) {
            return $this->wishlists->contains('user_id', $user->id);
        }

        return $this->wishlists()->where('user_id', $user->id)->exists();
    }

    /**
     * URL anh dai dien: anh upload (storage) -> anh mau dong goi -> no-image.
     * Chi file trong storage/ moi bi xoa khi thay/xoa san pham.
     */
    public function getImageUrlAttribute()
    {
        if ($this->image && Str::startsWith($this->image, 'products/')) {
            return asset('storage/'.$this->image);
        }

        if ($this->image) {
            return asset('assets/images/products/'.$this->image);
        }

        return asset('assets/images/no-image.jpg');
    }

    public function isUploadedImage()
    {
        return $this->image && Str::startsWith($this->image, 'products/');
    }

    /**
     * Top sản phẩm bán chạy theo ACTUAL SOLD (net).
     * = SUM(OrderDetail.quantity của orders paid — KỂ CẢ paid+cancelled,
     *   KHÔNG phân biệt method, COD unpaid loại)
     *   TRỪ SUM(quantity_refunded completed, match chính xác order_detail_id).
     * Subquery gom refund theo detail trước để tránh join fan-out khi có
     * nhiều lần hoàn trên cùng một dòng.
     */
    public function scopeBestSellers($query)
    {
        $refundedPerDetail = RefundTransactionDetail::select(
            'order_detail_id',
            DB::raw('SUM(quantity_refunded) as refunded_qty')
        )
            ->whereHas('refund', function ($q) {
                $q->where('status', RefundTransaction::STATUS_COMPLETED);
            })
            ->groupBy('order_detail_id');

        return $query->select('products.*')
            ->join('order_details', 'order_details.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.payment_status', Order::PAY_PAID)
            ->leftJoinSub($refundedPerDetail, 'rr', 'rr.order_detail_id', '=', 'order_details.id')
            ->groupBy('products.id')
            ->selectRaw('SUM(order_details.quantity) - COALESCE(SUM(rr.refunded_qty), 0) as total_sold')
            ->havingRaw('total_sold > 0')
            ->orderByDesc('total_sold');
    }
}
