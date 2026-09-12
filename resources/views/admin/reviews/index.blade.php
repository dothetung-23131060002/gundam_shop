@extends('layouts.app')

@section('title', 'Quản lý đánh giá - Gundam Shop')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h2 class="text-white font-semibold text-lg">Quản lý đánh giá</h2>
            </div>
        </header>

        <main class="p-6">
            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Sản phẩm</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Người dùng</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Đánh giá</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Nội dung</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Ngày tạo</th>
                                <th class="text-right text-text-secondary text-sm font-medium px-6 py-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($reviews as $review)
                                <tr class="hover:bg-bg-primary/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-bg-primary flex-shrink-0">
                                                <img src="{{ $review->product->image_url }}"
                                                     alt="{{ $review->product->name }}"
                                                     class="w-full h-full object-cover"
                                                     onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                                            </div>
                                            <span class="text-white text-sm line-clamp-1 max-w-[150px]">{{ $review->product->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-text-secondary text-sm">{{ $review->user->name ?? 'Anonymous' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-accent-gold' : 'text-border' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-text-secondary text-sm line-clamp-2 max-w-[200px] block">{{ $review->comment }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-text-secondary text-sm">{{ $review->created_at->format('d/m/Y') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Xóa đánh giá này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" aria-label="Xóa đánh giá này" class="p-2 text-text-secondary hover:text-accent-red transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <p class="text-text-secondary">Chưa có đánh giá nào</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-border">
                    {{ $reviews->links() }}
                </div>
            </div>
        </main>
    </div>
</div>

@endsection

