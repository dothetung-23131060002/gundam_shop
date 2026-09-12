@extends('layouts.app')

@section('title', 'Thu hộ giữ slot #' . $reservation->id . ' - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.reservations.show', $reservation) }}" aria-label="Quay lại chi tiết giữ slot" class="text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-white font-semibold text-lg">Thu hộ slot #{{ $reservation->id }}</h2>
            </div>
        </header>

        <main class="p-6">
            <div class="bg-accent-gold/10 border border-accent-gold/30 rounded-xl p-4 mb-6 text-sm">
                <p class="text-accent-gold font-medium">Xác nhận đã nhận đủ {{ number_format($reservation->balanceAmount(), 0, ',', '.') }}đ từ {{ $reservation->user->name }}.</p>
                <p class="text-text-secondary text-xs mt-1">Hệ thống sẽ tạo đơn hàng paid + chuyển slot sang đã chuyển đơn + thông báo cho khách.</p>
            </div>

            <form action="{{ route('admin.reservations.collect', $reservation) }}" method="POST" onsubmit="var b=this.querySelector('[type=submit]');b.disabled=true;b.textContent='ĐANG XỬ LÝ...';">
                @csrf
                <div class="grid lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <div class="bg-bg-secondary border border-border rounded-xl p-6">
                            <h3 class="text-white font-semibold mb-4">Thông tin giao hàng</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="customer_name" class="block text-text-secondary text-sm mb-1">Họ tên <span class="text-accent-red" aria-hidden="true">*</span></label>
                                    <input type="text" id="customer_name" name="customer_name" autocomplete="name" value="{{ old('customer_name', $reservation->user->name) }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-gold">
                                    @error('customer_name') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="customer_phone" class="block text-text-secondary text-sm mb-1">Số điện thoại <span class="text-accent-red" aria-hidden="true">*</span></label>
                                        <input type="tel" id="customer_phone" name="customer_phone" autocomplete="tel" value="{{ old('customer_phone', $reservation->user->phone) }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-gold">
                                        @error('customer_phone') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="customer_email" class="block text-text-secondary text-sm mb-1">Email</label>
                                        <input type="email" id="customer_email" name="customer_email" autocomplete="email" value="{{ old('customer_email', $reservation->user->email) }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-gold">
                                        @error('customer_email') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <div>
                                    <label for="shipping_address" class="block text-text-secondary text-sm mb-1">Địa chỉ giao hàng <span class="text-accent-red" aria-hidden="true">*</span></label>
                                    <textarea id="shipping_address" name="shipping_address" rows="2" autocomplete="street-address" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-gold">{{ old('shipping_address', $reservation->user->address) }}</textarea>
                                    @error('shipping_address') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <fieldset>
                                    <legend class="block text-text-secondary text-sm mb-2">Hình thức đã thu <span class="text-accent-red" aria-hidden="true">*</span></legend>
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2 cursor-pointer text-white text-sm">
                                            <input type="radio" name="collect_method" value="cash" checked class="w-4 h-4 accent-accent-blue">
                                            Tiền mặt
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer text-white text-sm">
                                            <input type="radio" name="collect_method" value="transfer" class="w-4 h-4 accent-accent-blue">
                                            Chuyển khoản
                                        </label>
                                    </div>
                                    @error('collect_method') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                </fieldset>
                                <div>
                                    <label for="reason" class="block text-text-secondary text-sm mb-1">Ghi chú nội bộ (lý do thu hộ)</label>
                                    <input type="text" id="reason" name="reason" value="{{ old('reason') }}" maxlength="500" placeholder="Không bắt buộc — lưu vào nhật ký" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-gold">
                                    @error('reason') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                            <h3 class="text-white font-semibold mb-4">Tóm tắt</h3>
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
                                    <span class="text-white font-medium">Đã nhận thu hộ</span>
                                    <span class="text-accent-gold font-bold font-mono tabular-nums text-lg">{{ number_format($reservation->balanceAmount(), 0, ',', '.') }}đ</span>
                                </div>
                            </div>
                            <button type="submit" class="w-full btn-primary py-3 text-sm font-medium mt-6">XÁC NHẬN THU HỘ & TẠO ĐƠN</button>
                            <a href="{{ route('admin.reservations.show', $reservation) }}" class="block w-full text-center py-3 text-text-secondary hover:text-white text-sm transition-colors">Hủy</a>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

@endsection
