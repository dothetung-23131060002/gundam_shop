@extends('layouts.app')

@section('title', 'Chi tiết giữ slot #' . $reservation->id . ' - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.reservations.index') }}" aria-label="Quay lại danh sách giữ slot" class="text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-white font-semibold text-lg">Giữ slot #{{ $reservation->id }}</h2>
                @if($reservation->status === 'reserved')
                    <x-status-pill tone="green">Đang giữ</x-status-pill>
                @elseif($reservation->status === 'converted')
                    <x-status-pill tone="gold">Đã chuyển đơn</x-status-pill>
                @elseif($reservation->status === 'refunded')
                    <x-status-pill tone="red">Đã hoàn cọc</x-status-pill>
                @else
                    <x-status-pill tone="red">Đã hủy</x-status-pill>
                @endif
            </div>
        </header>

        <main class="p-6">
            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Khách hàng & đợt gom</h3>
                        <div class="grid sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-text-secondary">Khách hàng:</p>
                                <p class="text-white font-medium">{{ $reservation->user->name }}</p>
                                <p class="text-text-secondary text-xs">{{ $reservation->user->email }}</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Đợt gom:</p>
                                <a href="{{ route('admin.batches.show', $reservation->batch) }}" class="text-accent-blue hover:text-white">#{{ $reservation->batch_id }} — {{ $reservation->batch->product->name ?? '' }}</a>
                            </div>
                            <div>
                                <p class="text-text-secondary">Số slot:</p>
                                <p class="text-white font-mono tabular-nums">{{ $reservation->quantity }}</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Đã cọc:</p>
                                <p class="text-accent-gold font-mono tabular-nums">{{ number_format($reservation->deposit_paid, 0, ',', '.') }}đ</p>
                            </div>
                        </div>
                        @if($reservation->order)
                            <div class="mt-4 pt-4 border-t border-border">
                                <p class="text-text-secondary text-sm">Đơn hàng:</p>
                                <a href="{{ route('admin.orders.show', $reservation->order) }}" class="text-accent-blue hover:text-white text-sm">Xem đơn hàng #{{ $reservation->order->id }} →</a>
                            </div>
                        @endif
                    </div>

                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Lịch sử giao dịch ({{ $reservation->payments->count() }})</h3>
                        @if($reservation->payments->count() > 0)
                            <div class="space-y-3">
                                @foreach($reservation->payments as $payment)
                                    <div class="flex items-center justify-between p-3 bg-bg-primary rounded-lg text-sm">
                                        <div>
                                            <p class="text-white">{{ match($payment->type) { 'deposit' => 'Đặt cọc', 'balance' => 'Trả nốt', 'refund' => 'Hoàn cọc', default => $payment->type } }}</p>
                                            <p class="text-text-secondary text-xs">{{ $payment->created_at->format('d/m/Y H:i') }} • {{ $payment->note }}</p>
                                        </div>
                                        <span class="font-mono tabular-nums {{ $payment->type === 'refund' ? 'text-green-400' : 'text-accent-gold' }}">
                                            {{ $payment->type === 'refund' ? '+' : '-' }}{{ number_format($payment->amount, 0, ',', '.') }}đ
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-text-secondary text-center py-4">Chưa có giao dịch.</p>
                        @endif
                    </div>

                    @if($reservation->refundTransactions->count() > 0)
                        <div class="bg-bg-secondary border border-border rounded-xl p-6">
                            <h3 class="text-white font-semibold mb-4">Hoàn cọc</h3>
                            @foreach($reservation->refundTransactions as $refund)
                                <div class="bg-bg-primary rounded-lg p-3 text-sm">
                                    <p class="text-green-400 font-mono tabular-nums">+{{ number_format($refund->amount, 0, ',', '.') }}đ</p>
                                    <p class="text-text-secondary text-xs mt-1">{{ $refund->reason }}</p>
                                    <p class="text-text-secondary text-xs">{{ $refund->refunded_at->format('d/m/Y H:i') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                        <h3 class="text-white font-semibold mb-4">Tổng quan</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Ngày đặt:</span>
                                <span class="text-white">{{ $reservation->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Cập nhật:</span>
                                <span class="text-white">{{ $reservation->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                        <div class="mt-6 space-y-3">
                            @if($reservation->needsPayment())
                                <a href="{{ route('admin.reservations.collect-form', $reservation) }}" class="block w-full text-center btn-primary py-3 text-sm font-medium">THU HỘ & TẠO ĐƠN</a>
                            @endif
                            <a href="{{ route('admin.reservations.index') }}" class="block w-full text-center py-3 text-text-secondary hover:text-white text-sm transition-colors">Quay lại</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection
