@extends('layouts.app')

@section('title', 'Quản lý danh mục - Gundam Shop')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h2 class="text-white font-semibold text-lg">Quản lý danh mục</h2>
                </div>
                <button onclick="openModal('create-modal')" class="btn-primary py-2 px-4 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Thêm danh mục
                </button>
            </div>
        </header>

        <main class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($categories as $category)
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 bg-accent-blue/10 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="openEditModal({{ $category->id }}, @json($category->name), @json($category->description ?? ''))" class="p-2 text-text-secondary hover:text-accent-blue transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-text-secondary hover:text-accent-red transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <h3 class="text-white font-semibold mb-2">{{ $category->name }}</h3>
                        <p class="text-text-secondary text-sm line-clamp-2 mb-4">{{ $category->description ?? 'Không có mô tả' }}</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text-secondary">Sản phẩm: {{ $category->products_count ?? $category->products->count() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <p class="text-text-secondary">Chưa có danh mục nào</p>
                    </div>
                @endforelse
            </div>
        </main>
    </div>
</div>

<!-- Create Modal -->
<div id="create-modal" role="dialog" aria-modal="true" aria-labelledby="create-modal-title" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-bg-secondary border border-border rounded-xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b border-border">
            <h3 id="create-modal-title" class="text-white font-semibold">Thêm danh mục mới</h3>
            <button onclick="closeModal('create-modal')" aria-label="Đóng hộp thoại thêm danh mục" class="text-text-secondary hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form action="{{ route('admin.categories.store') }}" method="POST" class="p-6">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="create-name" class="block text-text-secondary text-sm mb-2">Tên danh mục <span class="text-accent-red" aria-hidden="true">*</span></label>
                    <input type="text" id="create-name" name="name" required class="form-input" placeholder="Nhập tên danh mục">
                </div>
                <div>
                    <label for="create-description" class="block text-text-secondary text-sm mb-2">Mô tả</label>
                    <textarea id="create-description" name="description" rows="3" class="form-input" placeholder="Nhập mô tả"></textarea>
                </div>
            </div>
            <div class="flex gap-4 mt-6">
                <button type="button" onclick="closeModal('create-modal')" class="flex-1 btn-secondary py-3 text-sm">Hủy</button>
                <button type="submit" class="flex-1 btn-primary py-3 text-sm">Thêm</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-bg-secondary border border-border rounded-xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b border-border">
            <h3 id="edit-modal-title" class="text-white font-semibold">Sửa danh mục</h3>
            <button onclick="closeModal('edit-modal')" aria-label="Đóng hộp thoại sửa danh mục" class="text-text-secondary hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="edit-form" method="POST" class="p-6">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label for="edit-name" class="block text-text-secondary text-sm mb-2">Tên danh mục <span class="text-accent-red" aria-hidden="true">*</span></label>
                    <input type="text" name="name" id="edit-name" required class="form-input">
                </div>
                <div>
                    <label for="edit-description" class="block text-text-secondary text-sm mb-2">Mô tả</label>
                    <textarea name="description" id="edit-description" rows="3" class="form-input"></textarea>
                </div>
            </div>
            <div class="flex gap-4 mt-6">
                <button type="button" onclick="closeModal('edit-modal')" class="flex-1 btn-secondary py-3 text-sm">Hủy</button>
                <button type="submit" class="flex-1 btn-primary py-3 text-sm">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function openEditModal(id, name, description) {
        document.getElementById('edit-form').action = '{{ route("admin.categories.index") }}/' + id;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-description').value = description;
        openModal('edit-modal');
    }
</script>
@endpush
