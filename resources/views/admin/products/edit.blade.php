@extends('layouts.app')

@section('title', 'Sửa sản phẩm - Gundam Shop')

@section('content')

<div class="flex min-h-screen">
    <!-- Sidebar -->
    @include('admin.partials.sidebar')

    <!-- Main Content -->
    <div class="flex-1 ml-0 lg:ml-64">
        <!-- Top Bar -->
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <a href="{{ route('admin.products.index') }}" class="text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="text-white font-semibold text-lg">Sửa sản phẩm: {{ $product->name }}</h2>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6">
            <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="grid lg:grid-cols-3 gap-6">
                    
                    <!-- Main Info -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-bg-secondary border border-border rounded-xl p-6">
                            <h3 class="text-white font-semibold mb-4">Thông tin cơ bản</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="name" class="block text-text-secondary text-sm mb-2">Tên sản phẩm <span class="text-accent-red" aria-hidden="true">*</span></label>
                                    <input type="text" 
                                           id="name"
                                           name="name" 
                                           value="{{ old('name', $product->name) }}"
                                           required
                                           class="form-input"
                                           placeholder="Nhập tên sản phẩm">
                                    @error('name')
                                        <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="description" class="block text-text-secondary text-sm mb-2">Mô tả <span class="text-accent-red" aria-hidden="true">*</span></label>
                                    <textarea id="description" name="description" 
                                              rows="6" 
                                              required
                                              class="form-input"
                                              placeholder="Nhập mô tả sản phẩm">{{ old('description', $product->description) }}</textarea>
                                    @error('description')
                                        <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="bg-bg-secondary border border-border rounded-xl p-6">
                            <h3 class="text-white font-semibold mb-4">Giá & Kho hàng</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="price" class="block text-text-secondary text-sm mb-2">Giá bán (VNĐ) <span class="text-accent-red" aria-hidden="true">*</span></label>
                                    <input type="number" 
                                           id="price"
                                           name="price" 
                                           value="{{ old('price', $product->price) }}"
                                           required
                                           min="0"
                                           class="form-input"
                                           placeholder="0">
                                    @error('price')
                                        <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="quantity" class="block text-text-secondary text-sm mb-2">Số lượng tồn <span class="text-accent-red" aria-hidden="true">*</span></label>
                                    <input type="number" 
                                           id="quantity"
                                           name="quantity" 
                                           value="{{ old('quantity', $product->quantity) }}"
                                           required
                                           min="0"
                                           class="form-input"
                                           placeholder="0">
                                    @error('quantity')
                                        <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Category & Brand -->
                        <div class="bg-bg-secondary border border-border rounded-xl p-6">
                            <h3 class="text-white font-semibold mb-4">Danh mục & Thương hiệu</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="category_id" class="block text-text-secondary text-sm mb-2">Danh mục <span class="text-accent-red" aria-hidden="true">*</span></label>
                                    <select id="category_id" name="category_id" required class="form-input">
                                        <option value="">Chọn danh mục</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="brand_id" class="block text-text-secondary text-sm mb-2">Thương hiệu</label>
                                    <select id="brand_id" name="brand_id" class="form-input">
                                        <option value="">Chọn thương hiệu</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('brand_id')
                                        <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Image -->
                        <div class="bg-bg-secondary border border-border rounded-xl p-6">
                            <h3 class="text-white font-semibold mb-4">Hình ảnh</h3>
                            
                            <div>
                                <label for="image-upload" class="block text-text-secondary text-sm mb-2">Ảnh sản phẩm</label>
                                <div class="border-2 border-dashed border-border rounded-xl p-4 text-center hover:border-accent-blue/50 transition-colors">
                                    <input type="file" 
                                           name="image" 
                                           accept="image/*"
                                           class="hidden"
                                           id="image-upload"
                                           onchange="previewImage(this)">
                                    <label for="image-upload" class="cursor-pointer">
                                        <svg class="w-10 h-10 mx-auto text-text-secondary mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="text-text-secondary text-sm">Chọn ảnh mới (để trống nếu giữ ảnh cũ)</p>
                                    </label>
                                </div>
                                <div id="image-preview" class="mt-4">
                                    <p class="text-text-secondary text-xs mb-2">Ảnh hiện tại:</p>
                                    <img src="{{ $product->image_url }}"
                                         alt="{{ $product->name }}"
                                         class="w-full h-48 object-cover rounded-lg"
                                         onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                                </div>
                                @error('image')
                                    <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="bg-bg-secondary border border-border rounded-xl p-6 space-y-3">
                            <button type="submit" class="w-full btn-primary py-3 text-sm">
                                CẬP NHẬT SẢN PHẨM
                            </button>
                            <a href="{{ route('admin.products.index') }}" class="w-full btn-secondary py-3 text-sm flex items-center justify-center">
                                Hủy
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function previewImage(input) {
        const preview = document.getElementById('image-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="w-full h-48 object-cover rounded-lg">';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
