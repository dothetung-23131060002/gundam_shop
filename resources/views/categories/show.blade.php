@extends('layouts.app')

@section('title', $category->name . ' - Gundam Shop')

@section('content')

<!-- Page Header -->
<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-4">
            <a href="{{ route('home') }}" class="text-text-secondary hover:text-white transition-colors">Trang chủ</a>
            <span class="text-border">/</span>
            <a href="{{ route('products.index') }}" class="text-text-secondary hover:text-white transition-colors">Sản phẩm</a>
            <span class="text-border">/</span>
            <span class="text-white">{{ $category->name }}</span>
        </nav>

        <div class="flex items-center gap-4">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">{{ $category->name }}</h1>
        </div>
        @if($category->description)
            <p class="mt-4 text-text-secondary max-w-2xl">{{ $category->description }}</p>
        @endif
    </div>
</section>

<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Results Count -->
        <div class="flex items-center justify-between mb-6">
            <p class="text-text-secondary text-sm">
                Hiển thị <span class="text-white font-medium">{{ $products->count() }}</span> sản phẩm trong danh mục {{ $category->name }}
            </p>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($products as $product)
                <div class="product-card group">
                    <!-- Image -->
                    <div class="relative overflow-hidden aspect-square">
                        <img src="{{ $product->image_url }}"
                             alt="{{ $product->name }}"
                             class="product-image w-full h-full object-cover"
                             onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                        
                        <!-- Badges -->
                        <div class="absolute top-3 left-3">
                            <span class="badge-new">{{ $category->name }}</span>
                        </div>

                        <!-- Quick Actions -->
                        <div class="absolute inset-0 bg-bg-primary/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                            <a href="{{ route('products.show', $product) }}" class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-bg-primary hover:bg-accent-blue hover:text-white transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <form action="{{ route('cart.add', $product) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-bg-primary hover:bg-accent-blue hover:text-white transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs text-accent-blue font-medium">{{ $category->name }}</span>
                            @if($product->brand)
                                <span class="text-xs text-text-secondary">• {{ $product->brand->name }}</span>
                            @endif
                        </div>
                        <h3 class="text-white font-medium text-sm line-clamp-2 mb-3 min-h-[40px]">
                            <a href="{{ route('products.show', $product) }}" class="hover:text-accent-blue transition-colors">
                                {{ $product->name }}
                            </a>
                        </h3>
                        <div class="flex items-center justify-between">
                            <span class="price-display text-lg">{{ number_format($product->price, 0, ',', '.') }} VNĐ</span>
                            @if($product->quantity > 0)
                                <span class="text-xs text-green-400">Còn hàng</span>
                            @else
                                <span class="text-xs text-accent-red">Hết hàng</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-text-secondary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-text-secondary text-lg">Chưa có sản phẩm nào trong danh mục này</p>
                    <a href="{{ route('products.index') }}" class="mt-4 inline-block btn-primary text-sm">Xem tất cả sản phẩm</a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $products->links() }}
        </div>
    </div>
</section>

@endsection
