@extends('layouts.app')

@section('title', 'Danh sách yêu thích - Gundam Shop')

@section('content')

<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-2 text-sm mb-4">
            <a href="{{ route('home') }}" class="text-text-secondary hover:text-white transition-colors">Trang chủ</a>
            <span class="text-border">/</span>
            <span class="text-white">Yêu thích</span>
        </nav>
        <div class="flex items-center gap-4">
            <div class="w-1 h-8 bg-accent-red"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">DANH SÁCH YÊU THÍCH</h1>
        </div>
        <p class="text-text-secondary text-sm mt-2">Theo dõi các mẫu Gundam bạn quan tâm và nhận thông báo khi có đợt gom mới.</p>
    </div>
</section>

<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4 mb-6 text-green-400 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6 text-red-400 text-sm">{{ session('error') }}</div>
        @endif

        @if($wishlists->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($wishlists as $wishlist)
                    @php
                        $item = $wishlist->product;
                        $openBatch = $item ? $item->openBatch : null;
                        $remainingSlots = $openBatch ? max(0, (int) $openBatch->threshold - (int) $openBatch->reservedSlotCount()) : 0;
                    @endphp
                    @if($item)
                        <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                            <a href="{{ route('products.show', $item) }}" class="block aspect-square overflow-hidden bg-bg-primary">
                                <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover hover:scale-105 transition-transform" onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                            </a>
                            <div class="p-4 space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('products.show', $item) }}" class="text-white font-medium text-sm hover:text-accent-blue transition-colors line-clamp-1">{{ $item->name }}</a>
                                        <p class="price-display text-sm mt-1">{{ number_format($item->price, 0, ',', '.') }} VNĐ</p>
                                    </div>
                                    <x-heart-button :product="$item" :isWishlisted="true" :showCount="false" />
                                </div>

                                @if($openBatch)
                                    <p class="text-green-400 text-xs">Đang mở đợt gom - còn {{ $remainingSlots }} slot</p>
                                    <a href="{{ route('batches.show', $openBatch) }}" class="block w-full text-center py-3 btn-primary text-sm">Giữ chỗ ngay</a>
                                @else
                                    <p class="text-text-secondary text-xs">Chưa có đợt gom mới</p>
                                    <form action="{{ route('wishlist.destroy', $wishlist) }}" method="POST" onsubmit="return confirm('Xóa sản phẩm này khỏi danh sách yêu thích?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full py-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm hover:bg-red-500/20 transition-colors">Xóa khỏi wishlist</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @if($wishlists->hasPages())
                <div class="mt-8">{{ $wishlists->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="bg-bg-secondary border border-border rounded-xl p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-text-secondary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <h2 class="text-white font-semibold text-lg mb-2">Danh sách yêu thích đang trống</h2>
                <p class="text-text-secondary text-sm mb-6">Hãy thêm những mẫu Gundam bạn quan tâm để theo dõi các đợt gom hàng mới.</p>
                <a href="{{ route('products.index') }}" class="btn-primary py-3 px-8 text-sm">XEM SẢN PHẨM</a>
            </div>
        @endif
    </div>
</section>

@endsection
