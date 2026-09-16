@extends('layouts.app')

@section('title', 'Chi tiết trả hàng #' . $returnRequest->id . ' - Gundam Shop')

@section('content')

<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-2 text-sm mb-4">
            <a href="{{ route('home') }}" class="text-text-secondary hover:text-white transition-colors">Trang chủ</a>
            <span class="text-border">/</span>
            <a href="{{ route('returns.mine') }}" class="text-text-secondary hover:text-white transition-colors">Trả hàng</a>
            <span class="text-border">/</span>
            <span class="text-white">#{{ $returnRequest->id }}</span>
        </nav>
        <div class="flex items-center gap-4">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">TRẢ HÀNG #{{ $returnRequest->id }}</h1>
        </div>
    </div>
</section>

<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4 mb-6 text-green-400 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6 text-red-400 text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-white font-semibold">Thông tin yêu cầu</h3>
                        @if($returnRequest->status === 'requested')
                            <span class="px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 rounded-full text-yellow-400 text-xs">Chờ xử lý</span>
                        @elseif($returnRequest->status === 'approved')
                            <span class="px-3 py-1 bg-blue-500/10 border border-blue-500/30 rounded-full text-blue-400 text-xs">Đã duyệt</span>
                        @elseif($returnRequest->status === 'received')
                            <span class="px-3 py-1 bg-purple-500/10 border border-purple-500/30 rounded-full text-purple-400 text-xs">Đã nhận hàng</span>
                        @elseif($returnRequest->status === 'completed')
                            <span class="px-3 py-1 bg-green-500/10 border border-green-500/30 rounded-full text-green-400 text-xs">Hoàn thành</span>
                        @elseif($returnRequest->status === 'rejected')
                            <span class="px-3 py-1 bg-red-500/10 border border-red-500/30 rounded-full text-red-400 text-xs">Từ chối</span>
                        @elseif($returnRequest->status === 'cancelled')
                            <span class="px-3 py-1 bg-gray-500/10 border border-gray-500/30 rounded-full text-gray-400 text-xs">Đã hủy</span>
                        @endif
                    </div>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-text-secondary">Đơn hàng:</p>
                            <a href="{{ route('orders.show', $returnRequest->order) }}" class="text-accent-blue hover:text-white">#{{ $returnRequest->order_id }}</a>
                        </div>
                        <div>
                            <p class="text-text-secondary">Sản phẩm:</p>
                            <p class="text-white">{{ $returnRequest->orderDetail->product_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary">Số lượng:</p>
                            <p class="text-white">{{ $returnRequest->quantity }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary">Lý do:</p>
                            <p class="text-white">{{ $returnRequest->reason }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary">Tình trạng Runner:</p>
                            @if($returnRequest->runner_condition === 'sealed')
                                <p class="text-green-400">Nguyên seal (chưa mở)</p>
                            @elseif($returnRequest->runner_condition === 'opened')
                                <p class="text-red-400">Đã mở seal / Đã lắp ráp</p>
                            @else
                                <p class="text-text-secondary">Không xác định</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-text-secondary">Ngày gửi:</p>
                            <p class="text-white">{{ $returnRequest->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($returnRequest->order && $returnRequest->order->delivered_at)
                            <div>
                                <p class="text-text-secondary">Hạn đổi trả (3 ngày từ ngày giao):</p>
                                <p class="text-white">{{ $returnRequest->order->delivered_at->copy()->addDays(3)->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        @if($returnRequest->handledBy)
                            <div>
                                <p class="text-text-secondary">Xử lý bởi:</p>
                                <p class="text-white">{{ $returnRequest->handledBy->name }}</p>
                            </div>
                        @endif
                        @if($returnRequest->received_at)
                            <div>
                                <p class="text-text-secondary">Ngày nhận hàng:</p>
                                <p class="text-white">{{ $returnRequest->received_at->format('d/m/Y H:i') }}</p>
                            </div>
                        @endif
                        @if($returnRequest->inspection)
                            <div>
                                <p class="text-text-secondary">Kết quả kiểm tra:</p>
                                <p class="text-white">{{ ['resellable' => 'Có thể bán lại (Resellable)', 'defective' => 'Lỗi/hỏng (Defective)', 'dispose' => 'Thanh lý (Dispose)'][$returnRequest->inspection] ?? $returnRequest->inspection }}</p>
                            </div>
                        @endif
                        @if($returnRequest->refund_reason)
                            <div>
                                <p class="text-text-secondary">Lý do hoàn tiền:</p>
                                <p class="text-white">{{ $returnRequest->refund_reason }}</p>
                            </div>
                        @endif
                        @if($returnRequest->refundTransaction)
                            <div>
                                <p class="text-text-secondary">Refund:</p>
                                <p class="text-green-400 font-mono">#{{ $returnRequest->refund_transaction_id }} - {{ number_format($returnRequest->refundTransaction->amount, 0, ',', '.') }} VNĐ</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Trạng thái hoàn tiền:</p>
                                <p class="text-white">{{ $returnRequest->refundTransaction->status }}</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Ngày hoàn tiền:</p>
                                <p class="text-white">{{ $returnRequest->refundTransaction->refunded_at ? $returnRequest->refundTransaction->refunded_at->format('d/m/Y H:i') : '—' }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                @if(in_array($returnRequest->status, ['requested', 'approved']))
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Thao tác</h3>
                        <form method="POST" action="{{ route('returns.cancel', $returnRequest) }}" onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu này?')">
                            @csrf
                            <button type="submit" class="w-full py-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm hover:bg-red-500/20 transition-colors">Hủy yêu cầu</button>
                        </form>
                    </div>
                @endif

                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <a href="{{ route('returns.mine') }}" class="w-full block text-center py-3 text-text-secondary hover:text-white text-sm transition-colors">Quay lại danh sách</a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
