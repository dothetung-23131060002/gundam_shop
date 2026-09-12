@extends('layouts.app')

@section('title', 'Đợt gom hàng - Gundam Shop')

@section('content')

@include('components.hero.option-c', ['compact' => true])

<section class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm text-center">{{ session('success') }}</div>
    @endif

    @if($batches->count() > 0)
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($batches as $batch)
                @php
                    $reserved = $batch->reservedSlotCount();
                    $percent = $batch->progressPercent();
                    $remaining = $batch->deadline->diffForHumans();
                @endphp
                <a href="{{ route('batches.show', $batch) }}" class="group bg-bg-secondary border border-border rounded-xl overflow-hidden hover:border-accent-gold/50 transition-all hover:shadow-lg hover:shadow-accent-gold/5">
                    <div class="relative h-48 overflow-hidden">
                        <img src="{{ $batch->product->image_url }}" alt="{{ $batch->product->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-green-500/90 text-white font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                Đang mở
                            </span>
                        </div>
                        <div class="absolute bottom-3 left-3 right-3">
                            <h3 class="text-white font-semibold text-lg leading-tight">{{ $batch->product->name }}</h3>
                            <p class="text-text-secondary text-xs mt-1">{{ $batch->product->category->name ?? '' }} {{ $batch->product->brand ? '• ' . $batch->product->brand->name : '' }}</p>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="flex justify-between items-end mb-3">
                            <div>
                                <span class="text-text-secondary text-xs">Tiền cọc/slot</span>
                                <p class="price-display text-xl font-bold">{{ number_format($batch->deposit_amount, 0, ',', '.') }}đ</p>
                            </div>
                            <div class="text-right">
                                <span class="text-text-secondary text-xs">Còn {{ $remaining }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between mb-1">
                                <span class="text-text-secondary text-xs">Tiến độ</span>
                                <span class="text-white text-xs font-mono tabular-nums">{{ $reserved }}/{{ $batch->threshold }} slot</span>
                            </div>
                            <div class="w-full h-2 bg-bg-primary rounded-full overflow-hidden" role="progressbar" aria-valuenow="{{ $reserved }}" aria-valuemin="0" aria-valuemax="{{ $batch->threshold }}" aria-label="Tiến độ đợt gom: {{ $reserved }} trên {{ $batch->threshold }} slot">
                                <div class="h-full rounded-full {{ $reserved >= $batch->threshold ? 'bg-green-500' : 'bg-accent-gold' }}" style="width: {{ min($percent, 100) }}%"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="price-display text-sm">{{ number_format($batch->product->price, 0, ',', '.') }}đ</span>
                            <span class="btn-primary px-4 py-2 text-xs font-medium">GIỮ SLOT NGAY</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        @if($batches->hasPages())
            <div class="mt-10">
                {{ $batches->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-20">
            <svg class="w-16 h-16 mx-auto text-text-secondary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <p class="text-text-secondary text-lg">Chưa có đợt gom hàng nào đang mở.</p>
            <a href="{{ route('products.index') }}" class="inline-block mt-4 btn-primary px-6 py-2 text-sm">Xem sản phẩm</a>
        </div>
    @endif
</section>

@endsection
