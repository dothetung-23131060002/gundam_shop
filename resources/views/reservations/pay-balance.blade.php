@extends('layouts.app')

@section('title', 'Thanh toán phần còn lại - Gundam Shop')

@section('content')

<section class="py-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('reservations.show', $reservation) }}" class="text-text-secondary hover:text-white text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Quay lại
        </a>
    </div>

    <h1 class="text-2xl font-bold text-white mb-4 font-display">THANH TOÁN PHẦN CÒN LẠI</h1>

    <ol class="flex items-center gap-2 text-xs mb-6" aria-label="Tiến trình đợt gom">
        <li class="flex items-center gap-1 text-green-400"><span aria-hidden="true">✓</span> Đặt cọc</li>
        <li aria-hidden="true" class="text-border">→</li>
        <li class="flex items-center gap-1 text-green-400"><span aria-hidden="true">✓</span> Batch thành công</li>
        <li aria-hidden="true" class="text-border">→</li>
        <li class="text-accent-gold font-medium" aria-current="step">3. Thanh toán phần còn lại</li>
    </ol>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @php
                $canPay = ($paymentOptions['has_methods'] ?? false) && ($paymentOptions['amount_valid'] ?? false);
            @endphp
            <div class="bg-bg-secondary border border-border rounded-xl p-6">
                <h3 class="text-white font-semibold mb-4">Phương thức thanh toán</h3>
                @include('components.payment-methods', ['paymentOptions' => $paymentOptions ?? []])
            </div>

            <form action="{{ route('reservations.process-balance', $reservation) }}" method="POST" onsubmit="var b=this.querySelector('[type=submit]');b.disabled=true;b.textContent='ĐANG XỬ LÝ...';">
                @csrf

                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <h3 class="text-white font-semibold mb-4">Thông tin giao hàng</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="customer_name" class="block text-text-secondary text-sm mb-1">Họ tên <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <input type="text" id="customer_name" name="customer_name" autocomplete="name" value="{{ old('customer_name', auth()->user()->name) }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-gold">
                            @error('customer_name') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="customer_phone" class="block text-text-secondary text-sm mb-1">Số điện thoại <span class="text-accent-red" aria-hidden="true">*</span></label>
                                <input type="tel" id="customer_phone" name="customer_phone" autocomplete="tel" value="{{ old('customer_phone', auth()->user()->phone) }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-gold">
                                @error('customer_phone') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="customer_email" class="block text-text-secondary text-sm mb-1">Email</label>
                                <input type="email" id="customer_email" name="customer_email" autocomplete="email" value="{{ old('customer_email', auth()->user()->email) }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-gold">
                                @error('customer_email') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label for="shipping_address" class="block text-text-secondary text-sm mb-1">Địa chỉ giao hàng <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <textarea id="shipping_address" name="shipping_address" rows="2" autocomplete="street-address" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-gold">{{ old('shipping_address', auth()->user()->address) }}</textarea>
                            @error('shipping_address') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" @if(! $canPay) disabled @endif class="btn-primary px-8 py-3 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">XÁC NHẬN THANH TOÁN</button>
                </div>
            </form>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                <h3 class="text-white font-semibold mb-4">Đơn hàng xem trước</h3>
                <div class="flex items-center gap-3 mb-4 pb-4 border-b border-border">
                    <img src="{{ $reservation->batch->product->image_url }}" alt="{{ $reservation->batch->product->name }}" class="w-14 h-14 rounded-lg object-cover">
                    <div>
                        <p class="text-white text-sm font-medium">{{ $reservation->batch->product->name }}</p>
                        <p class="text-text-secondary text-xs">{{ $reservation->quantity }} slot</p>
                    </div>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Tổng giá trị</span>
                        <span class="text-white font-mono tabular-nums">{{ number_format($reservation->totalProductPrice(), 0, ',', '.') }}đ</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Đã cọc</span>
                        <span class="text-green-400 font-mono tabular-nums">-{{ number_format($reservation->deposit_paid, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="border-t border-border pt-2 mt-2 flex justify-between">
                        <span class="text-white font-medium">Còn phải thanh toán</span>
                        <span class="text-accent-gold font-bold font-mono tabular-nums text-lg">{{ number_format($reservation->balanceAmount(), 0, ',', '.') }}đ</span>
                    </div>
                </div>

                <div class="mt-4 bg-accent-gold/10 border border-accent-gold/30 rounded-lg p-3 text-sm text-accent-gold">
                    <p>Chuyển khoản đúng số tiền và nội dung ở tab đã chọn, sau đó bấm xác nhận. Admin sẽ đối soát và tạo đơn hàng.</p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
