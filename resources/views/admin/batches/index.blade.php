@extends('layouts.app')

@section('title', 'Quản lý đợt gom hàng - Admin')

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
                    <h2 class="text-white font-semibold text-lg">Đợt gom hàng</h2>
                </div>
                <div class="flex items-center gap-3">
                    <form method="GET" class="flex items-center gap-2">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tìm sản phẩm..." aria-label="Tìm theo tên sản phẩm" class="bg-bg-primary border border-border rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-accent-blue w-48">
                        <select name="status" aria-label="Lọc theo trạng thái" class="bg-bg-primary border border-border rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-accent-blue">
                            <option value="">Tất cả trạng thái</option>
                            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Đang mở</option>
                            <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Thành công</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Thất bại</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                        </select>
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">Lọc</button>
                    </form>
                    <a href="{{ route('admin.batches.create') }}" class="btn-primary px-4 py-2 text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tạo đợt gom
                    </a>
                    <a href="{{ route('admin.batches.export', request()->only(['status', 'keyword'])) }}" aria-label="Xuất CSV danh sách đợt gom (theo bộ lọc hiện tại)" class="btn-secondary px-4 py-2 text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Xuất CSV
                    </a>
                </div>
            </div>
        </header>

        <main class="p-6">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-accent-red/10 border border-accent-red/30 rounded-xl text-accent-red text-sm">{{ session('error') }}</div>
            @endif

            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">ID</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Sản phẩm</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Ngưỡng</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Đã cọc</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Tiến độ</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Tiền cọc/slot</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Deadline</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Trạng thái</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                                @php
                                    $reserved = $batch->reservedSlotCount();
                                    $percent = $batch->progressPercent();
                                @endphp
                                <tr class="border-b border-border/50 hover:bg-bg-primary/50 transition-colors">
                                    <td class="px-6 py-4 text-white text-sm">#{{ $batch->id }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $batch->product->image_url }}" alt="{{ $batch->product->name }}" class="w-10 h-10 rounded-lg object-cover">
                                            <span class="text-white text-sm">{{ Str::limit($batch->product->name, 30) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-white text-sm font-mono">{{ $batch->threshold }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-white text-sm font-mono tabular-nums">{{ $reserved }}</span>
                                            <span class="text-text-secondary text-xs">/</span>
                                            <span class="text-text-secondary text-sm font-mono tabular-nums">{{ $batch->threshold }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 min-w-[120px]">
                                            <div class="flex-1 h-2 bg-bg-primary rounded-full overflow-hidden" role="progressbar" aria-valuenow="{{ $reserved }}" aria-valuemin="0" aria-valuemax="{{ $batch->threshold }}" aria-label="Tiến độ đợt #{{ $batch->id }}: {{ $percent }}%">
                                                <div class="h-full rounded-full {{ $batch->isFull() ? 'bg-green-500' : 'bg-accent-gold' }}" style="width: {{ min($percent, 100) }}%"></div>
                                            </div>
                                            <span class="text-white text-xs font-mono tabular-nums">{{ $percent }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 price-display text-sm">{{ number_format($batch->deposit_amount, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4">
                                        <span class="text-white text-sm">{{ $batch->deadline->format('d/m/Y') }}</span>
                                        <span class="text-text-secondary text-xs block">{{ $batch->deadline->format('H:i') }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($batch->status === 'open')
                                            <x-status-pill tone="green">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                                                Đang mở
                                            </x-status-pill>
                                        @elseif($batch->status === 'success')
                                            <x-status-pill tone="gold">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                Thành công
                                            </x-status-pill>
                                        @elseif($batch->status === 'failed')
                                            <x-status-pill tone="red">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                                Thất bại
                                            </x-status-pill>
                                        @else
                                            <x-status-pill tone="blue">Đang xử lý</x-status-pill>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.batches.show', $batch) }}" aria-label="Xem chi tiết đợt gom #{{ $batch->id }}" class="text-accent-blue hover:text-accent-blue/80 text-sm" title="Xem chi tiết">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            @if($batch->status === 'open')
                                                <a href="{{ route('admin.batches.edit', $batch) }}" aria-label="Chỉnh sửa đợt gom #{{ $batch->id }}" class="text-accent-gold hover:text-accent-gold/80 text-sm" title="Chỉnh sửa">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </a>
                                                @if($batch->total_reservations === 0)
                                                    <form action="{{ route('admin.batches.destroy', $batch) }}" method="POST" onsubmit="return confirm('Xóa đợt gom này? Chỉ xóa được đợt chưa từng có khách giữ slot.')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" aria-label="Xóa đợt gom #{{ $batch->id }}" class="text-accent-red hover:text-accent-red/80 text-sm" title="Xóa">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-text-secondary text-center">Chưa có đợt gom hàng nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($batches->hasPages())
                    <div class="px-6 py-4 border-t border-border">
                        {{ $batches->links() }}
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>

@endsection
