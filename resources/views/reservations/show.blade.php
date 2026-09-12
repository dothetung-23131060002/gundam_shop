@extends('layouts.app')

@section('title', 'Chi tiết giữ slot #' . $reservation->id . ' - Gundam Shop')

@section('content')

<section class="py-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('reservations.index') }}" class="text-text-secondary hover:text-white text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Quay lại danh sách
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Product -->
            <div class="bg-bg-secondary border border-border rounded-xl p-6">
                <div class="flex items-start gap-4">
                    <img src="{{ $reservation->batch->product->image_url }}" alt="{{ $reservation->batch->product->name }}" class="w-20 h-20 rounded-xl object-cover">
                    <div>
                        <h2 class="text-white font-bold text-lg">{{ $reservation->batch->product->name }}</h2>
                        <p class="text-text-secondary text-sm">{{ $reservation->batch->product->category->name ?? '' }} {{ $reservation->batch->product->brand ? '• ' . $reservation->batch->product->brand->name : '' }}</p>
                        <p class="price-display text-lg mt-1">{{ number_format($reservation->batch->product->price, 0, ',', '.') }}đ</p>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-bg-secondary border border-border rounded-xl p-6">
                <h3 class="text-white font-semibold mb-4">Lịch sử giao dịch</h3>
                @if($reservation->payments->count() > 0)
                    <div class="space-y-3">
                        @foreach($reservation->payments as $payment)
                            <div class="flex items-center justify-between p-3 bg-bg-primary rounded-lg">
                                <div class="flex items-center gap-3">
                                    @if($payment->type === 'deposit')
                                        <div class="w-8 h-8 rounded-full bg-accent-gold/20 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-accent-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-white text-sm">Đặt cọc</p>
                                            <p class="text-text-secondary text-xs">{{ $payment->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-white text-sm">Hoàn cọc</p>
                                            <p class="text-text-secondary text-xs">{{ $payment->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    @endif
                                </div>
                                <span class="{{ $payment->type === 'refund' ? 'text-green-400' : 'text-accent-gold' }} font-mono font-bold">
                                    {{ $payment->type === 'refund' ? '+' : '-' }}{{ number_format($payment->amount, 0, ',', '.') }}đ
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-text-secondary text-center py-4">Chưa có giao dịch.</p>
                @endif
            </div>
        </div>

        <!-- Right: Summary -->
        <div class="lg:col-span-1">
            <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                <h3 class="text-white font-semibold mb-4">Chi tiết giữ slot</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Mã giữ slot:</span>
                        <span class="text-white font-mono">#{{ $reservation->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Đợt gom:</span>
                        <span class="text-white">#{{ $reservation->batch_id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Số slot:</span>
                        <span class="text-white font-mono">{{ $reservation->quantity }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Cọc/slot:</span>
                        <span class="text-accent-gold font-mono">{{ number_format($reservation->batch->deposit_amount, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Giá sản phẩm:</span>
                        <span class="text-white font-mono">{{ number_format($reservation->batch->product->price, 0, ',', '.') }}đ × {{ $reservation->quantity }}</span>
                    </div>
                    <div class="border-t border-border pt-3 flex justify-between">
                        <span class="text-white font-medium">Tổng đã cọc:</span>
                        <span class="text-accent-gold font-bold font-mono">{{ number_format($reservation->deposit_paid, 0, ',', '.') }}đ</span>
                    </div>
                </div>

                @if($reservation->isCancellable())
                    <div class="mt-4 pt-4 border-t border-border">
                        <form action="{{ route('reservations.destroy', $reservation) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn hủy giữ slot? Số tiền cọc sẽ được hoàn về tài khoản.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full text-center py-3 text-sm font-medium bg-accent-red/10 border border-accent-red/30 rounded-xl text-accent-red hover:bg-accent-red/20 transition-colors">HỦY GIỮ SLOT</button>
                        </form>
                    </div>
                @endif

                @if($reservation->needsPayment())
                    <div class="mt-4 pt-4 border-t border-border">
                        <div class="bg-accent-gold/10 border border-accent-gold/30 rounded-lg p-4 mb-4">
                            <p class="text-accent-gold font-medium text-sm mb-1">Đợt gom đã thành công!</p>
                            <p class="text-text-secondary text-xs">Vui lòng thanh toán phần còn lại để nhận hàng.</p>
                        </div>
                        <div class="flex justify-between text-sm mb-3">
                            <span class="text-text-secondary">Còn phải thanh toán:</span>
                            <span class="text-accent-gold font-bold font-mono text-lg">{{ number_format($reservation->balanceAmount(), 0, ',', '.') }}đ</span>
                        </div>
                        <a href="{{ route('reservations.pay-balance', $reservation) }}" class="block w-full text-center btn-primary py-3 text-sm font-medium">THANH TOÁN PHẦN CÒN LẠI</a>
                    </div>
                @endif

                @if($reservation->isConverted() && $reservation->order)
                    <div class="mt-4 pt-4 border-t border-border">
                        <div class="bg-accent-blue/10 border border-accent-blue/30 rounded-lg p-4 mb-4">
                            <p class="text-accent-blue font-medium text-sm">Đã tạo đơn hàng thành công!</p>
                        </div>
                        <a href="{{ route('orders.show', $reservation->order) }}" class="block w-full text-center bg-bg-primary border border-border py-3 text-sm text-white hover:border-accent-blue/50 transition-colors">Xem đơn hàng #{{ $reservation->order->id }}</a>
                    </div>
                @endif

                <div class="mt-4 pt-4 border-t border-border">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-text-secondary">Trạng thái:</span>
                        @if($reservation->status === 'reserved')
                            <x-status-pill tone="green">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                Đang giữ
                            </x-status-pill>
                        @elseif($reservation->status === 'refunded')
                            <x-status-pill tone="red">Đã hoàn cọc</x-status-pill>
                        @elseif($reservation->status === 'cancelled')
                            <x-status-pill tone="red">Đã hủy</x-status-pill>
                        @else
                            <x-status-pill tone="gold">Đã chuyển đơn</x-status-pill>
                        @endif
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-text-secondary">Ngày đặt:</span>
                        <span class="text-white">{{ $reservation->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>

                @if($reservation->refundTransactions->count() > 0)
                    <div class="mt-4 pt-4 border-t border-border">
                        <h4 class="text-white text-sm font-medium mb-2">Hoàn cọc</h4>
                        @foreach($reservation->refundTransactions as $refund)
                            <div class="bg-bg-primary rounded-lg p-3 text-sm">
                                <p class="text-green-400 font-mono">+{{ number_format($refund->amount, 0, ',', '.') }}đ</p>
                                <p class="text-text-secondary text-xs mt-1">{{ $refund->reason }}</p>
                                <p class="text-text-secondary text-xs">{{ $refund->refunded_at->format('d/m/Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection
