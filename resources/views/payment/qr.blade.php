@extends('layouts.app')

@section('title', 'Thanh toán QR - Gundam Shop')

@section('content')

<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg mx-auto text-center">
            <div class="w-20 h-20 mx-auto mb-8 bg-accent-blue/10 rounded-full flex items-center justify-center border border-accent-blue/30">
                <svg class="w-10 h-10 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
            </div>

            <h1 class="text-2xl lg:text-3xl font-bold text-white mb-4 font-display">THANH TOÁN QR CODE</h1>
            
            <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Mã đơn hàng:</span>
                        <span class="text-accent-blue font-medium">#{{ $order->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Số tiền:</span>
                        <span class="price-display text-xl">{{ number_format($order->total_amount, 0, ',', '.') }} VNĐ</span>
                    </div>
                </div>
            </div>

            <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6">
                <div class="w-48 h-48 mx-auto bg-white rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-32 h-32 text-bg-primary" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zM3 21h8v-8H3v8zm2-6h4v4H5v-4zM13 3v8h8V3h-8zm6 6h-4V5h4v4zM13 18h2v2h-2zM15 14h2v2h-2zM13 14h2v2h-2zM17 18h2v2h-2zM19 14h2v4h-2zM17 14h2v2h-2zM15 16h2v2h-2z"/>
                    </svg>
                </div>
                <p class="text-text-secondary text-sm">Quét mã QR bằng ứng dụng ngân hàng để thanh toán</p>
            </div>

            <p class="text-text-secondary text-sm mb-6">Sau khi thanh toán thành công, nhấn nút xác nhận bên dưới.</p>

            <form method="POST" action="{{ route('payment.qr.confirm', $order) }}">
                @csrf
                <button type="submit" class="w-full btn-primary py-4 text-base tracking-wide flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    XÁC NHẬN ĐÃ THANH TOÁN
                </button>
            </form>

            <a href="{{ route('orders.show', $order) }}" class="mt-4 w-full btn-secondary py-3 text-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Xem đơn hàng
            </a>
        </div>
    </div>
</section>

@endsection
