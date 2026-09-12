@extends('layouts.app')

@section('title', 'Quản lý sản phẩm - Gundam Shop')

@section('content')

<div class="flex min-h-screen">
    <!-- Sidebar -->
    @include('admin.partials.sidebar')

    <!-- Main Content -->
    <div class="flex-1 ml-0 lg:ml-64">
        <!-- Top Bar -->
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h2 class="text-white font-semibold text-lg">Quản lý sản phẩm</h2>
                </div>
                <a href="{{ route('admin.products.create') }}" class="btn-primary py-2 px-4 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Thêm sản phẩm
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6">
            <!-- Filters -->
            <div class="bg-bg-secondary border border-border rounded-xl p-4 mb-6">
                <form action="{{ route('admin.products.index') }}" method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" 
                               name="keyword" 
                               value="{{ request('keyword') }}"
                               placeholder="Tìm kiếm sản phẩm..."
                               class="form-input text-sm">
                    </div>
                    <select name="category_id" class="form-input text-sm w-auto">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-secondary py-2 px-4 text-sm">
                        Tìm kiếm
                    </button>
                </form>
            </div>

            <!-- Products Table -->
            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Sản phẩm</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Danh mục</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Giá</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Kho</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Trạng thái</th>
                                <th class="text-right text-text-secondary text-sm font-medium px-6 py-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($products as $product)
                                <tr class="hover:bg-bg-primary/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-lg overflow-hidden bg-bg-primary">
                                                <img src="{{ $product->image_url }}" 
                                                     alt="{{ $product->name }}"
                                                     class="w-full h-full object-cover"
                                                     onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                                            </div>
                                            <div>
                                                <p class="text-white font-medium text-sm line-clamp-1">{{ $product->name }}</p>
                                                <p class="text-text-secondary text-xs">ID: {{ $product->id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <x-status-pill tone="blue">
                                            {{ $product->category->name ?? 'N/A' }}
                                        </x-status-pill>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="price-display text-sm">{{ number_format($product->price, 0, ',', '.') }} VNĐ</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-white text-sm {{ $product->quantity <= 0 ? 'text-accent-red' : '' }}">
                                            {{ $product->quantity }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($product->quantity > 0)
                                            <x-status-pill tone="green">Còn hàng</x-status-pill>
                                        @else
                                            <x-status-pill tone="red">Hết hàng</x-status-pill>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.products.edit', $product) }}" class="p-2 text-text-secondary hover:text-accent-blue transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-text-secondary hover:text-accent-red transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <p class="text-text-secondary">Không tìm thấy sản phẩm nào</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-border">
                    {{ $products->withQueryString()->links() }}
                </div>
            </div>
        </main>
    </div>
</div>

@endsection

