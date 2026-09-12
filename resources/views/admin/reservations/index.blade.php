@extends('layouts.app')

@section('title', 'Quản lý giữ slot - Admin')

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
                    <h2 class="text-white font-semibold text-lg">Giữ slot</h2>
                </div>
                <form method="GET" class="flex items-center gap-2">
                    <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tên, email..." aria-label="Tìm theo tên hoặc email khách" class="bg-bg-primary border border-border rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-accent-blue w-44">
                    <select name="status" aria-label="Lọc theo trạng thái" class="bg-bg-primary border border-border rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-accent-blue">
                        <option value="">Tất cả trạng thái</option>
                        <option value="reserved" {{ request('status') === 'reserved' ? 'selected' : '' }}>Đang giữ</option>
                        <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Đã chuyển đơn</option>
                        <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Đã hoàn cọc</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                    <button type="submit" class="btn-primary px-4 py-2 text-sm">Lọc</button>
                </form>
            </div>
        </header>

        <main class="p-6">
            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Mã</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Khách hàng</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Đợt / Sản phẩm</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Slot</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Đã cọc</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Trạng thái</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Ngày đặt</th>
                                <th class="px-6 py-4 text-right text-text-secondary text-sm font-medium">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reservations as $reservation)
                                <tr class="border-b border-border/50 hover:bg-bg-primary/50 transition-colors">
                                    <td class="px-6 py-4 text-white text-sm font-mono">#{{ $reservation->id }}</td>
                                    <td class="px-6 py-4">
                                        <p class="text-white text-sm">{{ $reservation->user->name }}</p>
                                        <p class="text-text-secondary text-xs">{{ $reservation->user->email }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-white text-sm">{{ Str::limit($reservation->batch->product->name ?? 'N/A', 30) }}</p>
                                        <p class="text-text-secondary text-xs">Đợt #{{ $reservation->batch_id }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-white text-sm font-mono tabular-nums">{{ $reservation->quantity }}</td>
                                    <td class="px-6 py-4 price-display text-sm tabular-nums">{{ number_format($reservation->deposit_paid, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4">
                                        @if($reservation->status === 'reserved')
                                            <x-status-pill tone="green">Đang giữ</x-status-pill>
                                        @elseif($reservation->status === 'converted')
                                            <x-status-pill tone="gold">Đã chuyển đơn</x-status-pill>
                                        @elseif($reservation->status === 'refunded')
                                            <x-status-pill tone="red">Đã hoàn cọc</x-status-pill>
                                        @else
                                            <x-status-pill tone="red">Đã hủy</x-status-pill>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-text-secondary text-xs">{{ $reservation->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.reservations.show', $reservation) }}" aria-label="Xem chi tiết giữ slot #{{ $reservation->id }}" class="text-accent-blue hover:text-white text-sm transition-colors">Chi tiết →</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-text-secondary text-center">Chưa có giữ slot nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($reservations->hasPages())
                    <div class="px-6 py-4 border-t border-border">
                        {{ $reservations->links() }}
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>

@endsection
