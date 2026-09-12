@extends('layouts.app')

@section('title', 'Giỏ hàng - Gundam Shop')

@section('content')

<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">GIỎ HÀNG</h1>
        </div>
    </div>
</section>

<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(count($cart) > 0)
            <div class="grid lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-4">
                    @foreach($cart as $productId => $item)
                        @php $subtotal = $item['price'] * $item['quantity']; @endphp
                        <div class="bg-bg-secondary border border-border rounded-xl p-4 lg:p-6">
                            <div class="flex gap-4 lg:gap-6">
                                <a href="{{ route('products.show', $productId) }}" class="flex-shrink-0 w-24 h-24 lg:w-32 lg:h-32 rounded-lg overflow-hidden bg-bg-primary">
                                    <img src="{{ $item['image_url'] ?? asset('assets/images/products/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                                </a>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <a href="{{ route('products.show', $productId) }}" class="text-white font-medium text-sm lg:text-base hover:text-accent-blue transition-colors line-clamp-2">{{ $item['name'] }}</a>
                                        </div>
                                        <form action="{{ route('cart.remove', $productId) }}" method="POST" class="flex-shrink-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Xóa sản phẩm này?')" class="text-text-secondary hover:text-accent-red transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="flex items-center justify-between mt-4">
                                        <div class="flex items-center border border-border rounded-lg">
                                            <form action="{{ route('cart.update', $productId) }}" method="POST" class="contents">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ max(1, $item['quantity'] - 1) }}">
                                                <button type="submit" class="px-3 py-2 text-text-secondary hover:text-white transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                                </button>
                                            </form>
                                            <span class="px-4 py-2 text-white font-medium text-sm min-w-[40px] text-center">{{ $item['quantity'] }}</span>
                                            <form action="{{ route('cart.update', $productId) }}" method="POST" class="contents">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $item['quantity'] + 1 }}">
                                                <button type="submit" class="px-3 py-2 text-text-secondary hover:text-white transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                        <span class="price-display text-base lg:text-lg">{{ number_format($subtotal, 0, ',', '.') }} VNĐ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-end">
                        <form action="{{ route('cart.clear') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Xóa toàn bộ giỏ hàng?')" class="text-text-secondary hover:text-accent-red transition-colors text-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Xóa tất cả
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                        <h3 class="text-white font-semibold text-lg mb-6">Tóm tắt đơn hàng</h3>
                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-text-secondary">Tạm tính ({{ count($cart) }} sản phẩm)</span>
                                <span class="text-white">{{ number_format($total, 0, ',', '.') }} VNĐ</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-text-secondary">Phí vận chuyển</span>
                                <span class="text-green-400">Miễn phí</span>
                            </div>
                            <div class="border-t border-border pt-4">
                                <div class="flex justify-between">
                                    <span class="text-white font-semibold">Tổng cộng</span>
                                    <span class="price-display text-xl">{{ number_format($total, 0, ',', '.') }} VNĐ</span>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('checkout.index') }}" class="w-full btn-primary py-4 text-base tracking-wide flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            TIẾN HÀNH THANH TOÁN
                        </a>
                        <a href="{{ route('products.index') }}" class="mt-4 w-full btn-secondary py-3 text-sm flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Tiếp tục mua sắm
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-16">
                <svg class="w-24 h-24 mx-auto text-text-secondary mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h2 class="text-2xl font-bold text-white mb-4">Giỏ hàng trống</h2>
                <p class="text-text-secondary mb-8">Hãy khám phá sản phẩm và thêm vào giỏ hàng</p>
                <a href="{{ route('products.index') }}" class="btn-primary py-4 px-8 text-base tracking-wide">KHÁM PHÁ SẢN PHẨM</a>
            </div>
        @endif
    </div>
</section>

@endsection
