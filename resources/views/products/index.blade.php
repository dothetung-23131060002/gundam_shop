@extends('layouts.app')

@section('title', 'Sản phẩm - Gundam Shop')

@section('content')

<!-- Page Header -->
<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">SẢN PHẨM</h1>
        </div>
        <p class="text-text-secondary">Khám phá bộ sưu tập mô hình Gundam chính hãng</p>
    </div>
</section>

<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Sidebar Filter -->
            <aside class="w-full lg:w-64 flex-shrink-0">
                <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                    <h3 class="text-white font-semibold mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Lọc sản phẩm
                    </h3>

                    <!-- Search + Filter Form (một form duy nhất cho toàn bộ filter) -->
                    <form action="{{ route('products.index') }}" method="GET">
                        <!-- Search -->
                        <div class="relative mb-6">
                            <input type="text" 
                                   name="keyword" 
                                   value="{{ request('keyword') }}"
                                   placeholder="Tìm kiếm..."
                                   aria-label="Tìm kiếm sản phẩm"
                                   class="form-input pl-10 text-sm">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <!-- Category Filter -->
                        <div class="mb-6">
                            <h4 class="text-text-secondary text-sm font-medium mb-3 uppercase tracking-wider">Danh mục</h4>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="radio" name="category_id" value="" 
                                           {{ !request('category_id') ? 'checked' : '' }}
                                           onchange="this.form.submit()"
                                           class="w-4 h-4 accent-accent-blue">
                                    <span class="text-text-secondary text-sm group-hover:text-white transition-colors">Tất cả</span>
                                </label>
                                @foreach($categories as $category)
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="radio" name="category_id" value="{{ $category->id }}" 
                                               {{ request('category_id') == $category->id ? 'checked' : '' }}
                                               onchange="this.form.submit()"
                                               class="w-4 h-4 accent-accent-blue">
                                        <span class="text-text-secondary text-sm group-hover:text-white transition-colors">{{ $category->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Price Filter -->
                        <div class="mb-6">
                            <h4 class="text-text-secondary text-sm font-medium mb-3 uppercase tracking-wider">Khoảng giá</h4>
                            <div class="flex gap-2">
                                <input type="number" 
                                       name="min_price" 
                                       value="{{ request('min_price') }}"
                                       placeholder="Từ"
                                       aria-label="Giá tối thiểu"
                                       class="form-input text-sm w-1/2">
                                <input type="number" 
                                       name="max_price" 
                                       value="{{ request('max_price') }}"
                                       placeholder="Đến"
                                       aria-label="Giá tối đa"
                                       class="form-input text-sm w-1/2">
                            </div>
                            <button type="submit" class="mt-3 w-full btn-primary text-sm py-2">
                                Áp dụng
                            </button>
                        </div>

                        <!-- Sort -->
                        <div>
                            <h4 class="text-text-secondary text-sm font-medium mb-3 uppercase tracking-wider">Sắp xếp</h4>
                            <select name="sort" 
                                    onchange="this.form.submit()"
                                    class="form-input text-sm">
                                <option value="">Mới nhất</option>
                                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Giá thấp → cao</option>
                                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Giá cao → thấp</option>
                                <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Tên A → Z</option>
                            </select>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Product Grid -->
            <div class="flex-1">
                <!-- Results Count -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-text-secondary text-sm">
                        Hiển thị <span class="text-white font-medium">{{ $products->count() }}</span> sản phẩm
                    </p>
                    <div class="flex items-center gap-2">
                        <button class="p-2 bg-accent-blue/20 text-accent-blue rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Products -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($products as $product)
                        <div class="product-card group">
                            <!-- Image -->
                            <div class="relative overflow-hidden aspect-square">
                                <img src="{{ $product->image_url }}"
                                     alt="{{ $product->name }}"
                                     class="product-image w-full h-full object-cover"
                                     onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                                
                                <!-- Badges -->
                                <div class="absolute top-3 left-3 flex flex-col gap-2">
                                    @if($product->category)
                                        <span class="badge-new">{{ $product->category->name }}</span>
                                    @endif
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
                            <p class="text-text-secondary text-lg">Không tìm thấy sản phẩm nào</p>
                            <a href="{{ route('products.index') }}" class="mt-4 inline-block btn-primary text-sm">Xem tất cả sản phẩm</a>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $products->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
