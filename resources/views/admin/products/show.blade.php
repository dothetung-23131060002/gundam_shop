@extends('layouts.app')

@section('title', $product->name . ' - Admin')

@section('content')
<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h2 class="text-white font-semibold text-lg">Chi tiết sản phẩm</h2>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn-primary py-2 px-4 text-sm">Sửa</a>
                    <a href="{{ route('admin.products.index') }}" class="btn-secondary py-2 px-4 text-sm">Quay lại</a>
                </div>
            </div>
        </header>

        <main class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-96 object-cover rounded-lg">
                </div>

                <div class="bg-bg-secondary border border-border rounded-xl p-6 space-y-4">
                    <h1 class="text-2xl font-bold text-white">{{ $product->name }}</h1>

                    <div class="flex items-center gap-2">
                        <span class="text-accent-blue text-2xl font-bold tabular-nums">{{ number_format($product->price) }} VNĐ</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-text-secondary">Danh mục:</span>
                            <span class="text-white ml-2">{{ $product->category->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-text-secondary">Hãng:</span>
                            <span class="text-white ml-2">{{ $product->brand->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-text-secondary">Tồn kho:</span>
                            <span class="text-white ml-2">{{ $product->quantity }}</span>
                        </div>
                        <div>
                            <span class="text-text-secondary">Trạng thái:</span>
                            @if($product->quantity > 0)
                                <x-status-pill tone="green" class="ml-2">Còn hàng</x-status-pill>
                            @else
                                <x-status-pill tone="red" class="ml-2">Hết hàng</x-status-pill>
                            @endif
                        </div>
                    </div>

                    <div class="pt-4 border-t border-border">
                        <h3 class="text-white font-semibold mb-2">Mô tả</h3>
                        <p class="text-text-secondary leading-relaxed">{{ $product->description }}</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
