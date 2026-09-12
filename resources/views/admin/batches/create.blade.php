@extends('layouts.app')

@section('title', 'Tạo đợt gom hàng - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.batches.index') }}" aria-label="Quay lại danh sách đợt gom" class="text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-white font-semibold text-lg">Tạo đợt gom hàng mới</h2>
            </div>
        </header>

        <main class="p-6">
            <form action="{{ route('admin.batches.store') }}" method="POST">
                @csrf
                <div class="grid lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <div class="bg-bg-secondary border border-border rounded-xl p-6">
                            <h3 class="text-white font-semibold mb-4">Thông tin đợt gom</h3>

                            <div class="space-y-4">
                                <div>
                                    <label for="product_id" class="block text-text-secondary text-sm mb-1">Sản phẩm <span class="text-accent-red" aria-hidden="true">*</span></label>
                                    <select id="product_id" name="product_id" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                                        <option value="">Chọn sản phẩm</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                {{ $product->name }} — {{ number_format($product->price, 0, ',', '.') }} VNĐ
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_id') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="threshold" class="block text-text-secondary text-sm mb-1">Ngưỡng tối thiểu (slot) <span class="text-accent-red" aria-hidden="true">*</span></label>
                                        <input type="number" id="threshold" name="threshold" value="{{ old('threshold', 10) }}" min="2" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue font-mono tabular-nums">
                                        @error('threshold') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="deposit_amount" class="block text-text-secondary text-sm mb-1">Tiền cọc mỗi slot (VNĐ) <span class="text-accent-red" aria-hidden="true">*</span></label>
                                        <input type="number" id="deposit_amount" name="deposit_amount" value="{{ old('deposit_amount', \App\Models\Setting::get('default_deposit_amount', 200000)) }}" min="1000" step="1000" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue font-mono tabular-nums">
                                        @error('deposit_amount') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="deadline" class="block text-text-secondary text-sm mb-1">Hạn chót (deadline) <span class="text-accent-red" aria-hidden="true">*</span></label>
                                    <input type="datetime-local" id="deadline" name="deadline" value="{{ old('deadline', now()->addDays((int) \App\Models\Setting::get('default_deadline_days', 14))->format('Y-m-d\TH:i')) }}" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                                    @error('deadline') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                            <h3 class="text-white font-semibold mb-4">Hướng dẫn</h3>
                            <div class="space-y-3 text-text-secondary text-sm">
                                <p>• <strong class="text-white">Ngưỡng:</strong> Số slot tối thiểu cần đạt để đợt gom thành công.</p>
                                <p>• <strong class="text-white">Tiền cọc:</strong> Số tiền khách đặt cọc mỗi slot khi tham gia.</p>
                                <p>• <strong class="text-white">Deadline:</strong> Thời gian hết hạn đợt gom. Sau deadline, hệ thống sẽ kiểm tra và quyết định thành công/thất bại.</p>
                            </div>
                            <div class="mt-6 space-y-3">
                                <button type="submit" class="w-full btn-primary py-3 text-sm font-medium">Tạo đợt gom</button>
                                <a href="{{ route('admin.batches.index') }}" class="block w-full text-center py-3 text-text-secondary hover:text-white text-sm transition-colors">Hủy</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

@endsection
