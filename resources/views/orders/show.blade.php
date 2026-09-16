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

        @if($order->order_status === 'completed' && is_null($order->delivered_at))
            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4 mb-6 text-yellow-400 text-sm flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <span>Đơn hàng này chưa có thời điểm giao hàng được ghi nhận nên chưa thể xác định thời hạn đổi trả/bảo hành.</span>
            </div>
        @endif

        @php
            $hasDeliveredAt = !is_null($order->delivered_at);
            $returnDeadline = $hasDeliveredAt ? $order->delivered_at->copy()->addDays(3) : null;
            $warrantyDeadline = $hasDeliveredAt ? $order->delivered_at->copy()->addDays(7) : null;
            $now = now();
            $isCompleted = $order->order_status === 'completed';
            $isPaid = $order->payment_status === 'paid';
        @endphp

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

                        @php
                            $state = $detailStates[$detail->id] ?? ['remaining' => (int) $detail->quantity, 'refunded_qty' => 0, 'pending_return_qty' => 0, 'pending_warranty_qty' => 0, 'active_return' => null, 'active_warranty' => null];
                            $canReturn = $isCompleted && $isPaid && $hasDeliveredAt && $returnDeadline && !$now->gt($returnDeadline) && $state['remaining'] > 0;
                            $canWarranty = $isCompleted && $isPaid && $hasDeliveredAt && $warrantyDeadline && !$now->gt($warrantyDeadline) && $state['remaining'] > 0;
                            $returnExpired = $hasDeliveredAt && $returnDeadline && $now->gt($returnDeadline);
                            $warrantyExpired = $hasDeliveredAt && $warrantyDeadline && $now->gt($warrantyDeadline);
                            $activeReturn = $state['active_return'];
                            $activeWarranty = $state['active_warranty'];
                            $returnRemainingDays = $hasDeliveredAt && $returnDeadline ? max(0, (int) $now->diffInDays($returnDeadline, false)) : null;
                            $warrantyRemainingDays = $hasDeliveredAt && $warrantyDeadline ? max(0, (int) $now->diffInDays($warrantyDeadline, false)) : null;
                        @endphp

                        <div class="flex flex-wrap items-center gap-3 mt-3 pt-3 border-t border-border">
                            @if($activeReturn)
                                <a href="{{ route('returns.show', $activeReturn) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 hover:bg-yellow-500/20 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Xem yêu cầu trả hàng #{{ $activeReturn->id }}
                                </a>
                            @elseif($canReturn)
                                <a href="{{ route('returns.create', ['order_id' => $order->id, 'order_detail_id' => $detail->id]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-accent-blue/10 border border-accent-blue/30 text-accent-blue hover:bg-accent-blue/20 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                                    Đổi / Trả hàng
                                </a>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-text-secondary/10 border border-text-secondary/30 text-text-secondary cursor-not-allowed">
                                    @if(!$hasDeliveredAt)
                                        Chưa có ngày giao hàng
                                    @elseif($returnExpired)
                                        Đã hết hạn đổi trả
                                    @elseif($state['remaining'] <= 0)
                                        Đã hết số lượng
                                    @else
                                        Không thể đổi/trả
                                    @endif
                                </span>
                            @endif

                            @if($activeWarranty)
                                <a href="{{ route('warranties.show', $activeWarranty) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-purple-500/10 border border-purple-500/30 text-purple-400 hover:bg-purple-500/20 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Xem yêu cầu bảo hành #{{ $activeWarranty->id }}
                                </a>
                            @elseif($canWarranty)
                                <a href="{{ route('warranties.create', ['order_id' => $order->id, 'order_detail_id' => $detail->id]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-green-500/10 border border-green-500/30 text-green-400 hover:bg-green-500/20 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Yêu cầu bảo hành
                                </a>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-text-secondary/10 border border-text-secondary/30 text-text-secondary cursor-not-allowed">
                                    @if(!$hasDeliveredAt)
                                        Chưa có ngày giao hàng
                                    @elseif($warrantyExpired)
                                        Đã hết hạn bảo hành
                                    @elseif($state['remaining'] <= 0)
                                        Đã hết số lượng
                                    @else
                                        Không thể bảo hành
                                    @endif
                                </span>
                            @endif
                        </div>

                        @if($state['refunded_qty'] > 0 || $state['pending_return_qty'] > 0 || $state['pending_warranty_qty'] > 0)
                            <div class="mt-2 text-xs text-text-secondary">
                                Đã hoàn: {{ $state['refunded_qty'] }}/{{ $detail->quantity }}
                                @if($state['pending_return_qty'] > 0 || $state['pending_warranty_qty'] > 0)
                                    · Đang xử lý: {{ $state['pending_return_qty'] + $state['pending_warranty_qty'] }}
                                @endif
                                · Còn có thể yêu cầu: {{ $state['remaining'] }}
                            </div>
                        @endif

                        @if($hasDeliveredAt && $isCompleted && $isPaid)
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-text-secondary">
                                @if($returnDeadline)
                                    @if($returnExpired)
                                        <span class="text-red-400">Hạn đổi trả: {{ $returnDeadline->format('d/m/Y') }} (đã hết hạn)</span>
                                    @else
                                        <span>Hạn đổi trả: {{ $returnDeadline->format('d/m/Y') }} (còn {{ $returnRemainingDays }} ngày)</span>
                                    @endif
                                @endif
                                @if($warrantyDeadline)
                                    @if($warrantyExpired)
                                        <span class="text-red-400">Hạn bảo hành: {{ $warrantyDeadline->format('d/m/Y') }} (đã hết hạn)</span>
                                    @else
                                        <span>Hạn bảo hành: {{ $warrantyDeadline->format('d/m/Y') }} (còn {{ $warrantyRemainingDays }} ngày)</span>
                                    @endif
                                @endif
                            </div>
                        @endif
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
                            @php $payStatus = $order->payment_status; @endphp
                            @if($payStatus == 'paid')
                                <span class="px-3 py-1 bg-green-500/10 border border-green-500/30 rounded-full text-green-400 text-xs">Thanh toán thành công</span>
                            @elseif($payStatus == 'awaiting_confirmation')
                                <span class="px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 rounded-full text-yellow-400 text-xs animate-pulse">Đang chờ shop xác nhận thanh toán</span>
                            @elseif($payStatus == 'payment_rejected')
                                <span class="px-3 py-1 bg-red-500/10 border border-red-500/30 rounded-full text-red-400 text-xs">Thanh toán chưa được xác nhận</span>
                            @elseif($payStatus == 'pending_payment')
                                <span class="px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 rounded-full text-yellow-400 text-xs">Chờ thanh toán</span>
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
                @if(in_array($order->payment_status, ['pending_payment', 'unpaid', 'payment_rejected']) && $order->payment_method === 'qr')
                    <a href="{{ route('payment.qr', $order) }}" class="w-full btn-primary py-3 text-sm flex items-center justify-center gap-2 mt-3">
                        THANH TOÁN NGAY
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection
