@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng #' . $order->id . ' - Gundam Shop')

@section('content')

<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-2 text-sm mb-4">
            <a href="{{ route('home') }}" class="text-text-secondary hover:text-white transition-colors">Trang chủ</a>
            <span class="text-border">/</span>
            <a href="{{ route('orders.mine') }}" class="text-text-secondary hover:text-white transition-colors">Đơn hàng</a>
            <span class="text-border">/</span>
            <span class="text-white">#{{ $order->id }}</span>
        </nav>
        <div class="flex items-center gap-4">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">ĐƠN HÀNG #{{ $order->id }}</h1>
        </div>
    </div>
</section>

<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <p class="text-text-secondary text-sm mb-1">Trạng thái đơn hàng</p>
                    <div class="flex items-center gap-3">
                        @if($order->order_status == 'pending')
                            <span class="px-4 py-2 bg-yellow-500/10 border border-yellow-500/30 rounded-lg text-yellow-400 font-medium">Chờ xác nhận</span>
                        @elseif($order->order_status == 'confirmed')
                            <span class="px-4 py-2 bg-blue-500/10 border border-blue-500/30 rounded-lg text-blue-400 font-medium">Đã xác nhận</span>
                        @elseif($order->order_status == 'shipping')
                            <span class="px-4 py-2 bg-purple-500/10 border border-purple-500/30 rounded-lg text-purple-400 font-medium">Đang giao hàng</span>
                        @elseif($order->order_status == 'completed')
                            <span class="px-4 py-2 bg-green-500/10 border border-green-500/30 rounded-lg text-green-400 font-medium">Đã giao hàng</span>
                        @elseif($order->order_status == 'cancelled')
                            <span class="px-4 py-2 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 font-medium">Đã hủy</span>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-text-secondary text-sm">Ngày đặt hàng</p>
                    <p class="text-white font-medium">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-4">
                <h3 class="text-white font-semibold text-lg mb-4">Sản phẩm trong đơn hàng</h3>
                @foreach($order->details as $detail)
                    <div class="bg-bg-secondary border border-border rounded-xl p-4 lg:p-6">
                        <div class="flex gap-4">
                            @if($detail->product)
                                <a href="{{ route('products.show', $detail->product) }}" class="flex-shrink-0 w-20 h-20 lg:w-24 lg:h-24 rounded-lg overflow-hidden bg-bg-primary">
                                    <img src="{{ $detail->product->image_url }}" alt="{{ $detail->product_name }}" class="w-full h-full object-cover">
                                </a>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-white font-medium text-sm lg:text-base">{{ $detail->product_name }}</p>
                                <p class="text-text-secondary text-xs mt-1">x{{ $detail->quantity }}</p>
                                <p class="price-display text-sm lg:text-base mt-2">{{ number_format($detail->price, 0, ',', '.') }} VNĐ</p>
                            </div>
                            <div class="text-right">
                                <p class="price-display text-base lg:text-lg">{{ number_format($detail->subtotal, 0, ',', '.') }} VNĐ</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <h3 class="text-white font-semibold mb-4">Thông tin giao hàng</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-text-secondary">Người nhận:</p>
                            <p class="text-white font-medium">{{ $order->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary">Số điện thoại:</p>
                            <p class="text-white">{{ $order->customer_phone }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary">Địa chỉ:</p>
                            <p class="text-white">{{ $order->shipping_address }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <h3 class="text-white font-semibold mb-4">Thanh toán</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-text-secondary">Phương thức:</p>
                            <p class="text-white">{{ match($order->payment_method) { 'cod' => 'COD - Thanh toán khi nhận hàng', 'qr' => 'Chuyển khoản QR', 'balance' => 'Thanh toán qua đợt gom', 'cash' => 'Tiền mặt (admin thu hộ)', default => $order->payment_method } }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary">Trạng thái:</p>
                            @if($order->payment_status == 'paid')
                                <span class="px-3 py-1 bg-green-500/10 border border-green-500/30 rounded-full text-green-400 text-xs">Đã thanh toán</span>
                            @else
                                <span class="px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 rounded-full text-yellow-400 text-xs">Chờ thanh toán</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <h3 class="text-white font-semibold mb-4">Tóm tắt đơn hàng</h3>
                    <div class="space-y-3">
                        <div class="border-t border-border pt-3">
                            <div class="flex justify-between">
                                <span class="text-white font-semibold">Tổng cộng:</span>
                                <span class="price-display text-xl">{{ number_format($order->total_amount, 0, ',', '.') }} VNĐ</span>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('orders.mine') }}" class="w-full btn-secondary py-3 text-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Quay lại danh sách
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
