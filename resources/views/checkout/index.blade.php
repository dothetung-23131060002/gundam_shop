@extends('layouts.app')

@section('title', 'Thanh toán - Gundam Shop')

@section('content')

<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">THANH TOÁN</h1>
        </div>
    </div>
</section>

<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form action="{{ route('checkout.store') }}" method="POST">
            @csrf
            <div class="grid lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold text-lg mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Thông tin giao hàng
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="co-name" class="block text-text-secondary text-sm mb-2">Họ và tên <span class="text-accent-red" aria-hidden="true">*</span></label>
                                <input type="text" id="co-name" name="customer_name" value="{{ auth()->user()->name ?? '' }}" required autocomplete="name" class="form-input" placeholder="Nguyễn Văn A">
                                @error('customer_name') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="co-phone" class="block text-text-secondary text-sm mb-2">Số điện thoại <span class="text-accent-red" aria-hidden="true">*</span></label>
                                <input type="tel" id="co-phone" name="customer_phone" value="{{ auth()->user()->phone ?? '' }}" required autocomplete="tel" class="form-input" placeholder="0912345678">
                                @error('customer_phone') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="co-email" class="block text-text-secondary text-sm mb-2">Email</label>
                                <input type="email" id="co-email" name="customer_email" value="{{ auth()->user()->email ?? '' }}" autocomplete="email" class="form-input" placeholder="email@example.com">
                                @error('customer_email') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="co-address" class="block text-text-secondary text-sm mb-2">Địa chỉ giao hàng <span class="text-accent-red" aria-hidden="true">*</span></label>
                                <input type="text" id="co-address" name="shipping_address" value="{{ auth()->user()->address ?? '' }}" required autocomplete="street-address" class="form-input" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố">
                                @error('shipping_address') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold text-lg mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            Phương thức thanh toán
                        </h3>
                        <div class="space-y-3">
                            <label class="flex items-center gap-4 p-4 bg-bg-primary border border-border rounded-xl cursor-pointer hover:border-accent-blue/50 transition-colors has-[:checked]:border-accent-blue has-[:checked]:bg-accent-blue/5">
                                <input type="radio" name="payment_method" value="cod" checked class="w-4 h-4 accent-accent-blue">
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="w-10 h-10 bg-green-500/10 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium text-sm">Thanh toán khi nhận hàng (COD)</p>
                                        <p class="text-text-secondary text-xs">Thanh toán bằng tiền mặt khi nhận hàng</p>
                                    </div>
                                </div>
                            </label>
                            <label class="flex items-center gap-4 p-4 bg-bg-primary border border-border rounded-xl cursor-pointer hover:border-accent-blue/50 transition-colors has-[:checked]:border-accent-blue has-[:checked]:bg-accent-blue/5">
                                <input type="radio" name="payment_method" value="qr" class="w-4 h-4 accent-accent-blue">
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="w-10 h-10 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium text-sm">Chuyển khoản ngân hàng (QR Code)</p>
                                        <p class="text-text-secondary text-xs">Quét mã QR để thanh toán trực tuyến</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                        @error('payment_method') <p class="text-accent-red text-xs mt-2">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                        <h3 class="text-white font-semibold text-lg mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Đơn hàng của bạn
                        </h3>
                        <div class="space-y-4 mb-6 max-h-64 overflow-y-auto">
                            @foreach($cart as $productId => $item)
                                @php $subtotal = $item['price'] * $item['quantity']; @endphp
                                <div class="flex gap-3">
                                    <div class="w-16 h-16 rounded-lg overflow-hidden bg-bg-primary flex-shrink-0">
                                        <img src="{{ $item['image_url'] ?? asset('assets/images/products/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-white text-sm line-clamp-2">{{ $item['name'] }}</p>
                                        <p class="text-text-secondary text-xs">x{{ $item['quantity'] }}</p>
                                    </div>
                                    <span class="text-white text-sm font-medium">{{ number_format($subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="space-y-3 border-t border-border pt-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-text-secondary">Tạm tính</span>
                                <span class="text-white">{{ number_format($total, 0, ',', '.') }} VNĐ</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-text-secondary">Phí vận chuyển</span>
                                <span class="text-green-400">Miễn phí</span>
                            </div>
                            <div class="border-t border-border pt-3">
                                <div class="flex justify-between">
                                    <span class="text-white font-semibold">Tổng cộng</span>
                                    <span class="price-display text-xl">{{ number_format($total, 0, ',', '.') }} VNĐ</span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="w-full btn-primary py-4 text-base tracking-wide mt-6 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            ĐẶT HÀNG
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

@endsection
