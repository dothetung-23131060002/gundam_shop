@extends('layouts.app')

@section('title', 'Đơn hàng của tôi - Gundam Shop')

@section('content')

<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">ĐƠN HÀNG CỦA TÔI</h1>
        </div>
    </div>
</section>

<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($orders->count() > 0)
            <div class="hidden lg:block bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Mã đơn</th>
                            <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Ngày đặt</th>
                            <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Sản phẩm</th>
                            <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Tổng tiền</th>
                            <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Trạng thái</th>
                            <th class="text-right text-text-secondary text-sm font-medium px-6 py-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($orders as $order)
                            <tr class="hover:bg-bg-primary/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-accent-blue font-medium">#{{ $order->id }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-text-secondary text-sm">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-text-secondary text-sm">{{ $order->details->count() }} sản phẩm</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="price-display text-sm">{{ number_format($order->total_amount, 0, ',', '.') }} VNĐ</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($order->order_status == 'pending')
                                        <x-status-pill tone="gold">Chờ xác nhận</x-status-pill>
                                    @elseif($order->order_status == 'confirmed')
                                        <x-status-pill tone="blue">Đã xác nhận</x-status-pill>
                                    @elseif($order->order_status == 'shipping')
                                        <x-status-pill tone="blue">Đang giao</x-status-pill>
                                    @elseif($order->order_status == 'completed')
                                        <x-status-pill tone="green">Đã giao</x-status-pill>
                                    @elseif($order->order_status == 'cancelled')
                                        <x-status-pill tone="red">Đã hủy</x-status-pill>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('orders.show', $order) }}" class="text-accent-blue hover:text-white text-sm transition-colors">Chi tiết →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="lg:hidden space-y-4">
                @foreach($orders as $order)
                    <div class="bg-bg-secondary border border-border rounded-xl p-4">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-accent-blue font-medium">#{{ $order->id }}</span>
                            @if($order->order_status == 'pending')
                                <x-status-pill tone="gold">Chờ xác nhận</x-status-pill>
                            @elseif($order->order_status == 'confirmed')
                                <x-status-pill tone="blue">Đã xác nhận</x-status-pill>
                            @elseif($order->order_status == 'shipping')
                                <x-status-pill tone="blue">Đang giao</x-status-pill>
                            @elseif($order->order_status == 'completed')
                                <x-status-pill tone="green">Đã giao</x-status-pill>
                            @elseif($order->order_status == 'cancelled')
                                <x-status-pill tone="red">Đã hủy</x-status-pill>
                            @endif
                        </div>
                        <div class="text-text-secondary text-sm mb-2">{{ $order->details->count() }} sản phẩm</div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-text-secondary text-xs">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                                <p class="price-display text-lg mt-1">{{ number_format($order->total_amount, 0, ',', '.') }} VNĐ</p>
                            </div>
                            <a href="{{ route('orders.show', $order) }}" class="btn-secondary px-4 py-2 text-sm">Chi tiết</a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">{{ $orders->links() }}</div>
        @else
            <div class="text-center py-16">
                <svg class="w-24 h-24 mx-auto text-text-secondary mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h2 class="text-2xl font-bold text-white mb-4">Chưa có đơn hàng nào</h2>
                <p class="text-text-secondary mb-8">Hãy bắt đầu mua sắm và tạo đơn hàng đầu tiên</p>
                <a href="{{ route('products.index') }}" class="btn-primary py-4 px-8 text-base tracking-wide">MUA SẮM NGAY</a>
            </div>
        @endif
    </div>
</section>

@endsection
