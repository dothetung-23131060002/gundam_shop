@extends('layouts.app')

@section('title', $product->name . ' - Gundam Shop')

@section('content')

<!-- Breadcrumb -->
<section class="py-4 bg-bg-secondary/50 border-b border-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('home') }}" class="text-text-secondary hover:text-white transition-colors">Trang chủ</a>
            <span class="text-border">/</span>
            <a href="{{ route('products.index') }}" class="text-text-secondary hover:text-white transition-colors">Sản phẩm</a>
            <span class="text-border">/</span>
            @if($product->category)
                <a href="{{ route('categories.show', $product->category) }}" class="text-text-secondary hover:text-white transition-colors">{{ $product->category->name }}</a>
                <span class="text-border">/</span>
            @endif
            <span class="text-white truncate max-w-xs">{{ $product->name }}</span>
        </nav>
    </div>
</section>

<!-- Product Detail -->
<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">
            
            <!-- Product Image -->
            <div class="relative">
                <div class="relative aspect-square rounded-2xl overflow-hidden bg-bg-secondary border border-border">
                    <img src="{{ $product->image_url }}"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover"
                         onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                    
                    <!-- Badges -->
                    <div class="absolute top-4 left-4 flex flex-col gap-2">
                        @if($product->category)
                            <span class="badge-new text-sm">{{ $product->category->name }}</span>
                        @endif
                        @if($product->quantity > 0)
                            <span class="bg-green-500/20 text-green-400 text-xs px-3 py-1 rounded-full border border-green-500/30">Còn hàng</span>
                        @else
                            <span class="badge-sale text-sm">Hết hàng</span>
                        @endif
                    </div>
                </div>

                <!-- Mecha Decoration -->
                <div class="absolute -bottom-4 -right-4 w-32 h-32 border border-accent-blue/10 rounded-xl -z-10"></div>
                <div class="absolute -top-4 -left-4 w-24 h-24 border border-accent-red/10 rounded-xl -z-10"></div>
            </div>

            <!-- Product Info -->
            <div class="space-y-6">
                <!-- Category & Brand -->
                <div class="flex items-center gap-3">
                    @if($product->category)
                        <a href="{{ route('categories.show', $product->category) }}" 
                           class="px-3 py-1 bg-accent-blue/10 border border-accent-blue/30 rounded-full text-accent-blue text-sm hover:bg-accent-blue/20 transition-colors">
                            {{ $product->category->name }}
                        </a>
                    @endif
                    @if($product->brand)
                        <span class="text-text-secondary text-sm">{{ $product->brand->name }}</span>
                    @endif
                </div>

                <!-- Name -->
                <h1 class="text-2xl lg:text-3xl font-bold text-white leading-tight">
                    {{ $product->name }}
                </h1>

                <!-- Rating -->
                <div class="flex items-center gap-4">
                    <div class="flex gap-1">
                        @php
                            $avgRating = $product->reviews->avg('rating') ?: 0;
                        @endphp
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-5 h-5 {{ $i <= $avgRating ? 'text-accent-gold' : 'text-border' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <span class="text-text-secondary text-sm">({{ $product->reviews->count() }} đánh giá)</span>
                </div>

                <!-- Price -->
                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <div class="flex items-baseline gap-3">
                        <span class="price-display text-3xl lg:text-4xl">{{ number_format($product->price, 0, ',', '.') }} VNĐ</span>
                    </div>
                    <p class="text-text-secondary text-sm mt-2">Bao gồm VAT. Miễn phí vận chuyển cho đơn từ 2 triệu</p>
                </div>

                <!-- Description -->
                @if($product->description)
                    <div>
                        <h3 class="text-white font-semibold mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Mô tả sản phẩm
                        </h3>
                        <div class="text-text-secondary text-sm leading-relaxed prose prose-invert max-w-none">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>
                @endif

                <!-- Quantity & Add to Cart -->
                <div class="bg-bg-secondary border border-border rounded-xl p-6 space-y-4">
                    <div class="flex items-center gap-4">
                        <label for="quantity" class="text-white font-medium">Số lượng:</label>
                        <div class="flex items-center border border-border rounded-lg">
                            <button type="button" onclick="updateQuantity(-1)" aria-label="Giảm số lượng" class="px-4 py-2 text-text-secondary hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                </svg>
                            </button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="{{ $product->quantity }}" 
                                   class="w-16 text-center bg-transparent border-x border-border py-2 text-white focus:outline-none">
                            <button type="button" onclick="updateQuantity(1)" aria-label="Tăng số lượng" class="px-4 py-2 text-text-secondary hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </div>
                        <span class="text-text-secondary text-sm">({{ $product->quantity }} sản phẩm có sẵn)</span>
                    </div>

                    <div class="flex gap-4">
                        <x-heart-button :product="$product" :isWishlisted="$isWishlisted ?? false" :wishlistCount="$wishlistCount ?? null" />
                        <form action="{{ route('cart.add', $product) }}" method="POST" class="flex-1" id="add-to-cart-form">
                            @csrf
                            <input type="hidden" name="quantity" id="cart-quantity" value="1">
                            <button type="submit" class="w-full btn-primary py-4 text-base tracking-wide flex items-center justify-center gap-2" {{ $product->quantity <= 0 ? 'disabled' : '' }}>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                {{ $product->quantity > 0 ? 'THÊM VÀO GIỎ' : 'HẾT HÀNG' }}
                            </button>
                        </form>
                    </div>

                    <!-- Features -->
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-border">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium">Chính hãng</p>
                                <p class="text-text-secondary text-xs">100% Bandai</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-accent-red/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-accent-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium">Giao nhanh</p>
                                <p class="text-text-secondary text-xs">24-48h toàn quốc</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reviews Section -->
<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="flex items-center gap-4 mb-8">
            <div class="w-1 h-8 bg-accent-gold"></div>
            <h2 class="text-2xl lg:text-3xl font-bold text-white tracking-wider font-display">ĐÁNH GIÁ SẢN PHẨM</h2>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Review Form -->
            <div class="lg:col-span-1">
                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <h3 class="text-white font-semibold mb-4">Viết đánh giá</h3>

                    @guest
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-text-secondary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <p class="text-text-secondary mb-4">Vui lòng đăng nhập để đánh giá</p>
                            <a href="{{ route('login.form') }}" class="btn-primary text-sm">Đăng nhập</a>
                        </div>
                    @elseif($alreadyReviewed)
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-green-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-green-400 font-medium mb-1">Bạn đã đánh giá sản phẩm này</p>
                            <p class="text-text-secondary text-sm">Cảm ơn bạn đã chia sẻ!</p>
                        </div>
                    @elseif($canReview)
                        <form action="{{ route('reviews.store', $product) }}" method="POST">
                            @csrf

                            <!-- Rating -->
                            <div class="mb-4">
                                <span id="rating-label" class="block text-text-secondary text-sm mb-2">Đánh giá sao</span>
                                <div class="flex gap-2" id="rating-stars" role="radiogroup" aria-labelledby="rating-label">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" onclick="setRating({{ $i }})" role="radio" aria-checked="{{ $i === 5 ? 'true' : 'false' }}" aria-label="Đánh giá {{ $i }} sao" class="star-btn text-2xl text-border hover:text-accent-gold transition-colors" data-rating="{{ $i }}">
                                            <span aria-hidden="true">★</span>
                                        </button>
                                    @endfor
                                </div>
                                <input type="hidden" name="rating" id="rating-input" value="5">
                            </div>

                            <!-- Comment -->
                            <div class="mb-4">
                                <label for="review-comment" class="block text-text-secondary text-sm mb-2">Nội dung</label>
                                <textarea id="review-comment" name="comment" 
                                          rows="4" 
                                          maxlength="1000"
                                          class="form-input text-sm"
                                          placeholder="Chia sẻ cảm nhận của bạn..."></textarea>
                            </div>

                            <button type="submit" class="w-full btn-primary py-3 text-sm">
                                Gửi đánh giá
                            </button>
                        </form>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-text-secondary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <p class="text-text-secondary mb-1">Chỉ khách đã mua hàng mới được đánh giá</p>
                            <p class="text-text-secondary text-sm">Hãy đặt hàng sản phẩm này để viết đánh giá.</p>
                        </div>
                    @endguest
                </div>
            </div>

            <!-- Reviews List -->
            <div class="lg:col-span-2">
                @if($product->reviews->count() > 0)
                    <div class="space-y-4">
                        @foreach($product->reviews->sortByDesc('created_at') as $review)
                            <div class="bg-bg-secondary border border-border rounded-xl p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-accent-blue/20 flex items-center justify-center">
                                            <span class="text-accent-blue font-medium text-sm">{{ substr($review->user->name ?? 'A', 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <p class="text-white font-medium text-sm">{{ $review->user->name ?? 'Anonymous' }}</p>
                                            <p class="text-text-secondary text-xs">{{ $review->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-accent-gold' : 'text-border' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                                <p class="text-text-secondary text-sm leading-relaxed">{{ $review->comment }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-text-secondary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="text-text-secondary text-lg">Chưa có đánh giá nào</p>
                        <p class="text-text-secondary text-sm mt-2">Hãy là người đầu tiên đánh giá sản phẩm này</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // Quantity controls
    function updateQuantity(change) {
        const input = document.getElementById('quantity');
        const cartInput = document.getElementById('cart-quantity');
        let value = parseInt(input.value) + change;
        value = Math.max(1, Math.min(value, parseInt(input.max) || 999));
        input.value = value;
        cartInput.value = value;
    }

    document.getElementById('quantity').addEventListener('change', function() {
        document.getElementById('cart-quantity').value = this.value;
    });

    // Rating stars
    function setRating(rating) {
        document.getElementById('rating-input').value = rating;
        const stars = document.querySelectorAll('.star-btn');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('text-accent-gold');
                star.classList.remove('text-border');
                star.setAttribute('aria-checked', 'true');
            } else {
                star.classList.remove('text-accent-gold');
                star.classList.add('text-border');
                star.setAttribute('aria-checked', 'false');
            }
        });
    }

    // Initialize rating (only when review form is rendered)
    if (document.getElementById('rating-input')) {
        setRating(5);
    }
</script>
@endpush
