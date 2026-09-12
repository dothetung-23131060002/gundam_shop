@extends('layouts.app')

@section('title', 'Nhật ký hoàn cọc - Admin')

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
                    <h2 class="text-white font-semibold text-lg">Nhật ký hoàn cọc</h2>
                </div>
                <form method="GET" class="flex items-center gap-2">
                    <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tên, email..." aria-label="Tìm theo tên hoặc email khách" class="bg-bg-primary border border-border rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-accent-blue w-44">
                    <button type="submit" class="btn-primary px-4 py-2 text-sm">Lọc</button>
                </form>
            </div>
        </header>

        <main class="p-6">
            <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6">
                <p class="text-text-secondary text-sm">Tổng đã hoàn</p>
                <p class="text-2xl font-bold text-green-400 font-mono tabular-nums mt-1">+{{ number_format($totalRefunded, 0, ',', '.') }}đ</p>
            </div>

            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Mã</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Khách hàng</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Đợt / Slot</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Số tiền</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Lý do</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Thời gian</th>
                                <th class="px-6 py-4 text-right text-text-secondary text-sm font-medium">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($refunds as $refund)
                                <tr class="border-b border-border/50 hover:bg-bg-primary/50 transition-colors">
                                    <td class="px-6 py-4 text-white text-sm font-mono">#{{ $refund->id }}</td>
                                    <td class="px-6 py-4">
                                        <p class="text-white text-sm">{{ $refund->reservation->user->name }}</p>
                                        <p class="text-text-secondary text-xs">{{ $refund->reservation->user->email }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-white text-sm">Đợt #{{ $refund->reservation->batch_id }}</p>
                                        <p class="text-text-secondary text-xs">Slot #{{ $refund->reservation_id }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-green-400 font-mono tabular-nums">+{{ number_format($refund->amount, 0, ',', '.') }}đ</td>
                                    <td class="px-6 py-4 text-text-secondary text-sm max-w-[240px]">{{ Str::limit($refund->reason, 60) }}</td>
                                    <td class="px-6 py-4 text-text-secondary text-xs">{{ $refund->refunded_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.refunds.show', $refund) }}" aria-label="Xem chi tiết hoàn cọc #{{ $refund->id }}" class="text-accent-blue hover:text-white text-sm transition-colors">Chi tiết →</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-text-secondary text-center">Chưa có giao dịch hoàn cọc nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($refunds->hasPages())
                    <div class="px-6 py-4 border-t border-border">
                        {{ $refunds->links() }}
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>

@endsection
