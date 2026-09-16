@props(['product', 'index' => 1])

<div class="product-card group animate-fade-in-up stagger-{{ $index }}">
    <!-- Image -->
    <div class="relative overflow-hidden aspect-square">
        <img src="{{ $product->image_url }}"
             alt="{{ $product->name }}"
             class="product-image w-full h-full object-cover"
             onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">

        <!-- Badge -->
        <div class="absolute top-3 left-3">
            <span class="badge-featured">Bán chạy</span>
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
            @if($product->category)
                <span class="text-xs text-accent-blue font-medium">{{ $product->category->name }}</span>
            @endif
            @if($product->brand)
                <span class="text-xs text-text-secondary">• {{ $product->brand->name }}</span>
            @endif
        </div>
        <h3 class="text-white font-medium text-sm line-clamp-2 mb-3 min-h-[40px]">
            {{ $product->name }}
        </h3>
        <div class="flex items-center justify-between">
            <span class="price-display text-lg">{{ number_format($product->price, 0, ',', '.') }} VNĐ</span>
            <span class="text-xs text-accent-gold font-medium font-mono tabular-nums">Đã bán {{ number_format($product->total_sold ?? 0, 0, ',', '.') }}</span>
        </div>
        <p class="text-xs mt-2 {{ $product->quantity > 0 ? 'text-green-400' : 'text-accent-red' }}">
            {{ $product->quantity > 0 ? 'Còn '.$product->quantity.' sản phẩm' : 'Hết hàng' }}
        </p>
    </div>
</div>
