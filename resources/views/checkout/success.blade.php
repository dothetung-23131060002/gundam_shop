@extends('layouts.app')

@section('title', 'Đặt hàng thành công - Gundam Shop')

@section('content')

<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto text-center">
            <div class="w-24 h-24 mx-auto mb-8 bg-green-500/10 rounded-full flex items-center justify-center border border-green-500/30">
                <svg class="w-12 h-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h1 class="text-3xl lg:text-4xl font-bold text-white mb-4 font-display">ĐẶT HÀNG THÀNH CÔNG!</h1>
            <p class="text-text-secondary text-lg mb-8">Cảm ơn bạn đã mua hàng tại Gundam Shop</p>

            <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-8 text-left">
                <h3 class="text-white font-semibold mb-4">Thông tin đơn hàng</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Mã đơn hàng:</span>
                        <span class="text-accent-blue font-medium">#{{ $order->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Khách hàng:</span>
                        <span class="text-white">{{ $order->customer_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Tổng tiền:</span>
                        <span class="price-display">{{ number_format($order->total_amount, 0, ',', '.') }} VNĐ</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Phương thức thanh toán:</span>
                        <span class="text-white">{{ $order->payment_method == 'cod' ? 'COD' : 'Chuyển khoản QR' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Trạng thái:</span>
                        <span class="px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 rounded-full text-yellow-400 text-sm">Chờ xác nhận</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('orders.show', $order) }}" class="btn-primary py-4 px-8 text-base tracking-wide flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Xem đơn hàng
                </a>
                <a href="{{ route('home') }}" class="btn-secondary py-4 px-8 text-base flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Về trang chủ
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
