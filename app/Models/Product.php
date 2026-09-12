<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
