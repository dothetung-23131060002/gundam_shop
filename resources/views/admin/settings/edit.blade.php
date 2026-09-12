@extends('layouts.app')

@section('title', 'Cài đặt chung - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="text-white font-semibold text-lg">Cài đặt chung</h2>
            </div>
        </header>

        <main class="p-6 max-w-3xl">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm">{{ session('success') }}</div>
            @endif

            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6">
                    <h3 class="text-white font-semibold mb-4">Cửa hàng</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="shop_name" class="block text-text-secondary text-sm mb-1">Tên shop <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <input type="text" id="shop_name" name="shop_name" value="{{ $settings['shop_name'] }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                            @error('shop_name') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6">
                    <h3 class="text-white font-semibold mb-4">Mặc định khi tạo đợt gom</h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="default_deposit_amount" class="block text-text-secondary text-sm mb-1">Tiền cọc/slot (VNĐ) <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <input type="number" id="default_deposit_amount" name="default_deposit_amount" value="{{ $settings['default_deposit_amount'] }}" min="1000" step="1000" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue font-mono tabular-nums">
                            @error('default_deposit_amount') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="default_deadline_days" class="block text-text-secondary text-sm mb-1">Deadline mặc định (ngày) <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <input type="number" id="default_deadline_days" name="default_deadline_days" value="{{ $settings['default_deadline_days'] }}" min="1" max="365" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue font-mono tabular-nums">
                            @error('default_deadline_days') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-text-secondary text-xs mt-3">Tự điền sẵn khi mở form tạo đợt gom — vẫn sửa được từng đợt.</p>
                </div>

                <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6">
                    <h3 class="text-white font-semibold mb-4">Liên hệ (hiện ở chân trang)</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="contact_address" class="block text-text-secondary text-sm mb-1">Địa chỉ</label>
                            <input type="text" id="contact_address" name="contact_address" value="{{ $settings['contact_address'] }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                            @error('contact_address') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact_phone" class="block text-text-secondary text-sm mb-1">Điện thoại</label>
                                <input type="tel" id="contact_phone" name="contact_phone" autocomplete="tel" value="{{ $settings['contact_phone'] }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                                @error('contact_phone') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="contact_email" class="block text-text-secondary text-sm mb-1">Email</label>
                                <input type="email" id="contact_email" name="contact_email" autocomplete="email" value="{{ $settings['contact_email'] }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                                @error('contact_email') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary px-8 py-3 text-sm font-medium">Lưu cấu hình</button>
            </form>
        </main>
    </div>
</div>

@endsection
