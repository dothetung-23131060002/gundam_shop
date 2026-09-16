@extends('layouts.app')

@section('title', 'Lịch sử giữ slot - Gundam Shop')

@section('content')

<section class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white">LỊCH SỬ GIỮ SLOT</h1>
            <p class="text-text-secondary text-sm mt-1">Quản lý các slot bạn đã đặt cọc</p>
        </div>
        <a href="{{ route('batches.index') }}" class="btn-primary px-4 py-2 text-sm">Xem đợt gom</a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm">{{ session('success') }}</div>
    @endif

    @if($reservations->count() > 0)
        <div class="space-y-4">
            @foreach($reservations as $reservation)
                @php
                    $batch = $reservation->batch;
                @endphp
                <a href="{{ route('reservations.show', $reservation) }}" class="block bg-bg-secondary border border-border rounded-xl p-5 hover:border-accent-gold/50 transition-all">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <img src="{{ $batch->product->image_url }}" alt="{{ $batch->product->name }}" class="w-16 h-16 rounded-lg object-cover flex-shrink-0">

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-white font-semibold">{{ $batch->product->name }}</h3>
                                    <p class="text-text-secondary text-xs mt-0.5">Đợt gom #{{ $batch->id }} • {{ $batch->deadline->format('d/m/Y') }}</p>
                                </div>
                                @if($reservation->status === 'reserved' && $reservation->batch->status === 'success')
                                    <x-status-pill tone="gold" pulse>Cần thanh toán</x-status-pill>
                                @elseif($reservation->status === 'reserved')
                                    <x-status-pill tone="green">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                        Đang giữ
                                    </x-status-pill>
                                @elseif($reservation->status === 'refunded')
                                    <x-status-pill tone="red">Đã hoàn cọc</x-status-pill>
                                @elseif($reservation->status === 'cancelled')
                                    <x-status-pill tone="red">Đã hủy</x-status-pill>
                                @elseif($reservation->status === 'converted')
                                    <x-status-pill tone="gold">Đã chuyển đơn</x-status-pill>
                                @else
                                    <x-status-pill tone="gray">{{ $reservation->status }}</x-status-pill>
                                @endif
                            </div>

                            <div class="flex items-center gap-6 mt-3 text-sm">
                                <div>
                                    <span class="text-text-secondary text-xs">Slot</span>
                                    <p class="text-white font-mono">{{ $reservation->quantity }}</p>
                                </div>
                                <div>
                                    <span class="text-text-secondary text-xs">Đã cọc</span>
                                    <p class="text-accent-gold font-mono font-bold">{{ number_format($reservation->deposit_paid, 0, ',', '.') }}đ</p>
                                </div>
                                <div>
                                    <span class="text-text-secondary text-xs">Ngày đặt</span>
                                    <p class="text-white text-xs">{{ $reservation->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        <svg class="w-5 h-5 text-text-secondary flex-shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            @endforeach
        </div>

        @if($reservations->hasPages())
            <div class="mt-8">
                {{ $reservations->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-20">
            <svg class="w-16 h-16 mx-auto text-text-secondary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            <p class="text-text-secondary text-lg">Bạn chưa giữ slot nào.</p>
            <a href="{{ route('batches.index') }}" class="inline-block mt-4 btn-primary px-6 py-2 text-sm">Xem đợt gom</a>
        </div>
    @endif
</section>

@endsection
