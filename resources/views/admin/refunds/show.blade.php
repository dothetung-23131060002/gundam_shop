@extends('layouts.app')

@section('title', 'Chi tiết hoàn cọc #' . $refund->id . ' - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.refunds.index') }}" aria-label="Quay lại nhật ký hoàn cọc" class="text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-white font-semibold text-lg">Hoàn cọc #{{ $refund->id }}</h2>
                <x-status-pill tone="green">Đã hoàn</x-status-pill>
            </div>
        </header>

        <main class="p-6">
            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <div class="text-center py-4">
                            <p class="text-text-secondary text-sm mb-1">Số tiền đã hoàn</p>
                            <p class="text-green-400 font-bold font-mono tabular-nums text-3xl">+{{ number_format($refund->amount, 0, ',', '.') }}đ</p>
                        </div>
                        <div class="border-t border-border mt-4 pt-4 space-y-3 text-sm">
                            <div>
                                <p class="text-text-secondary">Lý do:</p>
                                <p class="text-white">{{ $refund->reason }}</p>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Thời gian hoàn:</span>
                                <span class="text-white">{{ $refund->refunded_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Ghi nhận lúc:</span>
                                <span class="text-white">{{ $refund->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Giữ slot liên quan</h3>
                        <div class="grid sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-text-secondary">Khách hàng:</p>
                                <p class="text-white font-medium">{{ $refund->reservation->user->name }}</p>
                                <p class="text-text-secondary text-xs">{{ $refund->reservation->user->email }}</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Đợt gom:</p>
                                <a href="{{ route('admin.batches.show', $refund->reservation->batch) }}" class="text-accent-blue hover:text-white">#{{ $refund->reservation->batch_id }} — {{ $refund->reservation->batch->product->name ?? '' }}</a>
                            </div>
                            <div>
                                <p class="text-text-secondary">Slot / trạng thái slot:</p>
                                <p class="text-white font-mono tabular-nums">{{ $refund->reservation->quantity }} slot</p>
                                <a href="{{ route('admin.reservations.show', $refund->reservation) }}" class="text-accent-blue hover:text-white text-sm">Xem giữ slot #{{ $refund->reservation_id }} →</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                        <h3 class="text-white font-semibold mb-4">Đối soát</h3>
                        <p class="text-text-secondary text-sm leading-relaxed">Mỗi hoàn cọc đi kèm 1 <span class="text-white">Payment type=refund</span> cùng số tiền — dùng để đối chiếu sổ.</p>
                        <div class="mt-6">
                            <a href="{{ route('admin.refunds.index') }}" class="block w-full text-center py-3 text-text-secondary hover:text-white text-sm transition-colors">Quay lại</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection
