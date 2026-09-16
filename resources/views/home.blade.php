@extends('layouts.app')

@section('title', 'Gundam Shop - Mô hình Gundam Chính hãng')

@section('content')

<!-- ============================================
     SECTION 1: HERO — Option A "Neon Assault" (Phase 6a, skill banner-design)
     Partial: components/hero/option-a
     ============================================ -->
@include('components.hero.option-a')

<!-- ============================================
     SECTION 2: CATEGORIES (HG / RG / MG / PG)
     ============================================ -->
<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="flex items-center gap-4 mb-12">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h2 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">PHÂN LOẠI GRADE</h2>
            <div class="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
        </div>

        <!-- Categories Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
            @php
                $hg = $categories->firstWhere('name', 'HG');
                $rg = $categories->firstWhere('name', 'RG');
                $mg = $categories->firstWhere('name', 'MG');
                $pg = $categories->firstWhere('name', 'PG');
            @endphp

            <!-- HG Category -->
            <a href="{{ $hg ? route('categories.show', $hg) : '#' }}" class="category-card group p-6 text-center">
                <div class="relative w-20 h-20 mx-auto mb-4">
                    <div class="absolute inset-0 bg-accent-blue/10 rounded-full group-hover:bg-accent-blue/20 transition-colors"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-3xl font-bold text-accent-blue font-display">HG</span>
                    </div>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">HIGH GRADE</h3>
                <p class="text-text-secondary text-sm">1/144 • Dễ lắp • Phù hợp người mới</p>
                <div class="mt-4 text-accent-blue text-sm font-medium group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                    Khám phá
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>

            <!-- RG Category -->
            <a href="{{ $rg ? route('categories.show', $rg) : '#' }}" class="category-card group p-6 text-center">
                <div class="relative w-20 h-20 mx-auto mb-4">
                    <div class="absolute inset-0 bg-accent-red/10 rounded-full group-hover:bg-accent-red/20 transition-colors"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-3xl font-bold text-accent-red font-display">RG</span>
                    </div>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">REAL GRADE</h3>
                <p class="text-text-secondary text-sm">1/144 • Chi tiết cao • Khung trong</p>
                <div class="mt-4 text-accent-red text-sm font-medium group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                    Khám phá
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>

            <!-- MG Category -->
            <a href="{{ $mg ? route('categories.show', $mg) : '#' }}" class="category-card group p-6 text-center">
                <div class="relative w-20 h-20 mx-auto mb-4">
                    <div class="absolute inset-0 bg-accent-gold/10 rounded-full group-hover:bg-accent-gold/20 transition-colors"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-3xl font-bold text-accent-gold font-display">MG</span>
                    </div>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">MASTER GRADE</h3>
                <p class="text-text-secondary text-sm">1/100 • Cao cấp • Chi tiết tuyệt vời</p>
                <div class="mt-4 text-accent-gold text-sm font-medium group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                    Khám phá
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>

            <!-- PG Category -->
            <a href="{{ $pg ? route('categories.show', $pg) : '#' }}" class="category-card group p-6 text-center">
                <div class="relative w-20 h-20 mx-auto mb-4">
                    <div class="absolute inset-0 bg-purple-500/10 rounded-full group-hover:bg-purple-500/20 transition-colors"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-3xl font-bold text-purple-400 font-display">PG</span>
                    </div>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">PERFECT GRADE</h3>
                <p class="text-text-secondary text-sm">1/60 • Tuyệt phẩm • Đỉnh cao</p>
                <div class="mt-4 text-purple-400 text-sm font-medium group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                    Khám phá
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Mecha Line Divider -->
<div class="max-w-7xl mx-auto px-4">
    <div class="mecha-line"></div>
</div>

<!-- ============================================
     SECTION 3: FEATURED PRODUCTS
     ============================================ -->
<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="flex items-center gap-4 mb-12">
            <div class="w-1 h-8 bg-accent-gold"></div>
            <h2 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">SẢN PHẨM NỔI BẬT</h2>
            <div class="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
            <a href="{{ route('products.index') }}" class="text-accent-blue text-sm font-medium hover:text-accent-blue-dark transition-colors flex items-center gap-1">
                Xem tất cả
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($products->take(4) as $product)
                <div class="product-card group animate-fade-in-up stagger-{{ $loop->iteration }}">
                    <!-- Image -->
                    <div class="relative overflow-hidden aspect-square">
                        <img src="{{ $product->image_url }}"
                             alt="{{ $product->name }}"
                             class="product-image w-full h-full object-cover"
                             onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                        
                        <!-- Badge -->
                        <div class="absolute top-3 left-3">
                            <span class="badge-featured">Nổi bật</span>
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
                            <div class="flex items-center gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-3 h-3 {{ $i <= 4 ? 'text-accent-gold' : 'text-border' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                @for($i = 0; $i < 4; $i++)
                    <div class="product-card group">
                        <div class="relative overflow-hidden aspect-square bg-bg-tertiary">
                            <img src="{{ asset('assets/images/no-image.jpg') }}" 
                                 alt="Sample Product"
                                 class="product-image w-full h-full object-cover opacity-50">
                        </div>
                        <div class="p-4">
                            <p class="text-text-secondary text-sm">Sản phẩm mẫu</p>
                            <p class="price-display text-lg">1.500.000 VNĐ</p>
                        </div>
                    </div>
                @endfor
            @endforelse
        </div>
    </div>
</section>

<!-- ============================================
     SECTION: BEST SELLERS (theo đơn hàng thực tế)
     ============================================ -->
@if(($bestSellers ?? collect())->isNotEmpty())
<section class="py-16 lg:py-24 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="flex items-center gap-4 mb-12">
            <div class="w-1 h-8 bg-accent-red"></div>
            <h2 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">SẢN PHẨM BÁN CHẠY</h2>
            <div class="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
            <a href="{{ route('products.index') }}" class="text-accent-blue text-sm font-medium hover:text-accent-blue-dark transition-colors flex items-center gap-1">
                Xem tất cả
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($bestSellers as $product)
                @include('components.product-card', ['product' => $product, 'index' => $loop->iteration])
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- ============================================
     SECTION 4: NEW PRODUCTS
     ============================================ -->
<section class="py-16 lg:py-24 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="flex items-center gap-4 mb-12">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h2 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">SẢN PHẨM MỚI</h2>
            <div class="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
            <a href="{{ route('products.index') }}" class="text-accent-blue text-sm font-medium hover:text-accent-blue-dark transition-colors flex items-center gap-1">
                Xem tất cả
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($products->take(8)->skip(4) as $product)
                <div class="product-card group">
                    <div class="relative overflow-hidden aspect-square">
                        <img src="{{ $product->image_url }}"
                             alt="{{ $product->name }}"
                             class="product-image w-full h-full object-cover"
                             onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                        <div class="absolute top-3 left-3">
                            <span class="badge-new">Mới</span>
                        </div>
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
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            @if($product->category)
                                <span class="text-xs text-accent-blue font-medium">{{ $product->category->name }}</span>
                            @endif
                        </div>
                        <h3 class="text-white font-medium text-sm line-clamp-2 mb-3">{{ $product->name }}</h3>
                        <span class="price-display text-lg">{{ number_format($product->price, 0, ',', '.') }} VNĐ</span>
                    </div>
                </div>
            @empty
                @for($i = 0; $i < 4; $i++)
                    <div class="product-card group">
                        <div class="relative overflow-hidden aspect-square bg-bg-tertiary">
                            <img src="{{ asset('assets/images/no-image.jpg') }}" alt="Sample" class="product-image w-full h-full object-cover opacity-50">
                            <div class="absolute top-3 left-3"><span class="badge-new">Mới</span></div>
                        </div>
                        <div class="p-4">
                            <p class="text-text-secondary text-sm">Sản phẩm mới</p>
                            <p class="price-display text-lg">2.000.000 VNĐ</p>
                        </div>
                    </div>
                @endfor
            @endforelse
        </div>
    </div>
</section>

<!-- ============================================
     SECTION 5: PROMO BANNER
     ============================================ -->
<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative rounded-2xl overflow-hidden bg-gradient-to-r from-bg-secondary to-bg-tertiary border border-border">
            <!-- Background Image -->
            <div class="absolute inset-0">
                <img src="{{ asset('assets/images/banners/banner-01.jpg') }}" 
                     alt="Promotion" 
                     class="w-full h-full object-cover opacity-30"
                     onerror="this.style.display='none'">
                <div class="absolute inset-0 bg-gradient-to-r from-bg-primary/90 via-bg-primary/70 to-transparent"></div>
            </div>

            <!-- Mecha Decorations -->
            <div class="absolute top-0 right-0 w-64 h-64 border border-accent-blue/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 border border-accent-red/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>

            <!-- Content -->
            <div class="relative z-10 p-8 lg:p-16 flex flex-col lg:flex-row items-center justify-between gap-8">
                <div class="space-y-4 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-accent-red/20 border border-accent-red/50 rounded-full">
                        <span class="text-accent-red text-sm font-bold tracking-wider">GIẢM GIÁ LỚN</span>
                    </div>
                    <h2 class="text-4xl lg:text-6xl font-bold text-white tracking-wider font-display">
                        KHUYẾN MÃI
                        <br>
                        <span class="gradient-text">ĐẾN 30%</span>
                    </h2>
                    <p class="text-text-secondary text-lg max-w-md">
                        Cơ hội sở hữu mô hình Gundam chính hãng với giá ưu đãi chưa từng có. Số lượng có hạn!
                    </p>
                    <a href="{{ route('products.index') }}" class="btn-primary inline-flex items-center gap-2">
                        MUA NGAY
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
                
                <!-- Timer/Countdown visual -->
                <div class="flex flex-wrap justify-center gap-3 sm:gap-4">
                    <div class="text-center">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-bg-primary/80 border border-accent-blue/30 rounded-xl flex items-center justify-center">
                            <span class="text-2xl sm:text-3xl font-bold text-white font-display tabular-nums">07</span>
                        </div>
                        <p class="text-text-secondary text-xs mt-2 tracking-wider">NGÀY</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-bg-primary/80 border border-accent-blue/30 rounded-xl flex items-center justify-center">
                            <span class="text-2xl sm:text-3xl font-bold text-white font-display tabular-nums">12</span>
                        </div>
                        <p class="text-text-secondary text-xs mt-2 tracking-wider">GIỜ</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-bg-primary/80 border border-accent-blue/30 rounded-xl flex items-center justify-center">
                            <span class="text-2xl sm:text-3xl font-bold text-white font-display tabular-nums">45</span>
                        </div>
                        <p class="text-text-secondary text-xs mt-2 tracking-wider">PHÚT</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     SECTION 6: BRANDS
     ============================================ -->
<section class="py-16 lg:py-24 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="flex items-center gap-4 mb-12">
            <div class="w-1 h-8 bg-accent-red"></div>
            <h2 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">THƯƠNG HIỆU</h2>
            <div class="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
        </div>

        <!-- Brands Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @php
                $brands = [
                    ['name' => 'Bandai', 'color' => '#1E88E5'],
                    ['name' => 'Dragon Momoko', 'color' => '#E53935'],
                    ['name' => 'Motor Nuclear', 'color' => '#FFD54F'],
                    ['name' => 'MJH', 'color' => '#4CAF50'],
                    ['name' => 'Daban', 'color' => '#9C27B0'],
                    ['name' => 'TT Hongli', 'color' => '#FF9800'],
                ];
            @endphp
            
            @foreach($brands as $brand)
                <div class="group bg-bg-secondary border border-border rounded-xl p-6 flex flex-col items-center justify-center gap-3 hover:border-accent-blue/50 hover:bg-bg-tertiary transition-all cursor-pointer">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background: {{ $brand['color'] }}20; border: 1px solid {{ $brand['color'] }}40;">
                        <span class="text-xl font-bold font-display" style="color: {{ $brand['color'] }};">{{ substr($brand['name'], 0, 2) }}</span>
                    </div>
                    <span class="text-text-secondary text-sm font-medium group-hover:text-white transition-colors">{{ $brand['name'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ============================================
     SECTION 7: REVIEWS
     ============================================ -->
<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="flex items-center gap-4 mb-12">
            <div class="w-1 h-8 bg-accent-gold"></div>
            <h2 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">ĐÁNH GIÁ KHÁCH HÀNG</h2>
            <div class="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
        </div>

        <!-- Reviews Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php
                $reviews = [
                    ['name' => 'Nguyễn Văn A', 'rating' => 5, 'comment' => 'Sản phẩm chính hãng, đóng gói cẩn thận. Giao hàng nhanh, sẽ mua thêm!'],
                    ['name' => 'Trần Thị B', 'rating' => 5, 'comment' => 'Đồ chơi rất đẹp, đúng như mô tả. Shop nhiệt tình hỗ trợ.'],
                    ['name' => 'Lê Minh C', 'rating' => 4, 'comment' => 'Chất lượng tuyệt vời, giá cả hợp lý. Recommended!'],
                ];
            @endphp

            @foreach($reviews as $review)
                <div class="testimonial-card group">
                    <!-- Stars -->
                    <div class="flex gap-1 mb-4">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-5 h-5 {{ $i <= $review['rating'] ? 'text-accent-gold' : 'text-border' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    
                    <!-- Comment -->
                    <p class="text-text-secondary text-sm leading-relaxed mb-4">"{{ $review['comment'] }}"</p>
                    
                    <!-- Author -->
                    <div class="flex items-center gap-3 pt-4 border-t border-border">
                        <div class="w-10 h-10 rounded-full bg-accent-blue/20 flex items-center justify-center">
                            <span class="text-accent-blue font-medium text-sm">{{ substr($review['name'], 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="text-white text-sm font-medium">{{ $review['name'] }}</p>
                            <p class="text-text-secondary text-xs">Khách hàng verified</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
