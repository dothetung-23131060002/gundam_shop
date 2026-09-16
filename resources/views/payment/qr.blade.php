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

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 bg-accent-red/10 border border-accent-red/30 rounded-xl text-accent-red text-sm">{{ session('error') }}</div>
            @endif
            @if(session('info'))
                <div class="mb-6 p-4 bg-accent-blue/10 border border-accent-blue/30 rounded-xl text-accent-blue text-sm">{{ session('info') }}</div>
            @endif

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
                    <div class="flex justify-between items-center">
                        <span class="text-text-secondary">Trạng thái:</span>
                        @php $payStatus = $order->payment_status; @endphp
                        @if($payStatus === 'paid')
                            <x-status-pill tone="green">Thanh toán thành công</x-status-pill>
                        @elseif($payStatus === 'awaiting_confirmation')
                            <x-status-pill tone="gold" pulse>Đang chờ shop xác nhận thanh toán</x-status-pill>
                        @elseif($payStatus === 'payment_rejected')
                            <x-status-pill tone="red">Thanh toán chưa được xác nhận</x-status-pill>
                        @else
                            <x-status-pill tone="gold">Chờ thanh toán</x-status-pill>
                        @endif
                    </div>
                </div>
            </div>

            @if($order->payment_method === 'cod')
                <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6">
                    <p class="text-white font-medium mb-2">Đơn hàng thanh toán khi nhận hàng (COD)</p>
                    <p class="text-text-secondary text-sm">Bạn sẽ thanh toán bằng tiền mặt khi nhận hàng. Không cần chuyển khoản.</p>
                </div>
            @elseif(in_array($order->payment_status, ['pending_payment', 'unpaid', 'payment_rejected']))
                @if($order->payment_status === 'payment_rejected')
                    <div class="mb-6 p-4 bg-accent-red/10 border border-accent-red/30 rounded-xl text-sm">
                        <p class="text-accent-red font-medium">Thanh toán chưa được xác nhận.</p>
                        <p class="text-text-secondary text-xs mt-1">Vui lòng kiểm tra lý do trong Thông báo, chuyển khoản lại rồi bấm xác nhận bên dưới.</p>
                    </div>
                @endif

                <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6">
                    @include('components.payment-methods', ['paymentOptions' => $paymentOptions ?? []])
                </div>

                <p class="text-text-secondary text-sm mb-6">Sau khi chuyển khoản thành công, nhấn nút bên dưới. Shop sẽ kiểm tra và xác nhận.</p>

                @php
                    $canPay = ($paymentOptions['has_methods'] ?? false) && ($paymentOptions['amount_valid'] ?? false);
                @endphp
                <form method="POST" action="{{ route('payment.qr.confirm', $order) }}">
                    @csrf
                    <button type="submit" @if(! $canPay) disabled @endif class="w-full btn-primary py-4 text-base tracking-wide flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        TÔI ĐÃ THANH TOÁN
                    </button>
                </form>
            @elseif($order->payment_status === 'awaiting_confirmation')
                <div class="bg-accent-gold/10 border border-accent-gold/30 rounded-xl p-6 mb-6">
                    <p class="text-accent-gold font-medium">Đã gửi yêu cầu xác nhận thanh toán. Shop đang kiểm tra giao dịch.</p>
                    <p class="text-text-secondary text-xs mt-2">Bạn sẽ nhận được thông báo ngay khi shop xác nhận.</p>
                </div>
            @elseif($order->payment_status === 'paid')
                <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-6 mb-6">
                    <p class="text-green-400 font-medium">Thanh toán thành công. Shop đã xác nhận nhận được tiền.</p>
                </div>
            @endif

            <a href="{{ route('orders.show', $order) }}" class="mt-4 w-full btn-secondary py-3 text-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Xem đơn hàng
            </a>
        </div>
    </div>
</section>

@endsection
