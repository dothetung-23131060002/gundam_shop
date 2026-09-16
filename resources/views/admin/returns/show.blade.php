@extends('layouts.app')

@section('title', 'Chi tiết trả hàng #' . $returnRequest->id . ' - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.returns.index') }}" aria-label="Quay lại danh sách trả hàng" class="text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-white font-semibold text-lg">Trả hàng #{{ $returnRequest->id }}</h2>
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
        </header>

        <main class="p-6">
            @if(session('success'))
                <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4 mb-6 text-green-400 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6 text-red-400 text-sm">{{ session('error') }}</div>
            @endif

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Thông tin yêu cầu</h3>
                        <div class="grid sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-text-secondary">Khách hàng:</p>
                                <p class="text-white font-medium">{{ $returnRequest->requestedBy->name ?? 'N/A' }}</p>
                                <p class="text-text-secondary text-xs">{{ $returnRequest->requestedBy->email ?? '' }}</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Đơn hàng:</p>
                                <a href="{{ route('admin.orders.show', $returnRequest->order_id) }}" class="text-accent-blue hover:text-white">#{{ $returnRequest->order_id }}</a>
                            </div>
                            <div>
                                <p class="text-text-secondary">Sản phẩm:</p>
                                <p class="text-white">{{ $returnRequest->orderDetail->product_name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Số lượng:</p>
                                <p class="text-white font-medium">{{ $returnRequest->quantity }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-text-secondary">Lý do:</p>
                                <p class="text-white">{{ $returnRequest->reason }}</p>
                            </div>
                        </div>
                    </div>

                    @if($returnRequest->handledBy || $returnRequest->received_at || $returnRequest->inspection)
                        <div class="bg-bg-secondary border border-border rounded-xl p-6">
                            <h3 class="text-white font-semibold mb-4">Xử lý</h3>
                            <div class="grid sm:grid-cols-2 gap-4 text-sm">
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
                                @if($returnRequest->restocked_qty > 0)
                                    <div>
                                        <p class="text-text-secondary">Số lượng restock:</p>
                                        <p class="text-white">{{ $returnRequest->restocked_qty }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($returnRequest->refundTransaction)
                        <div class="bg-bg-secondary border border-border rounded-xl p-6">
                            <h3 class="text-white font-semibold mb-4">Refund</h3>
                            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-text-secondary">Mã refund:</p>
                                    <a href="{{ route('admin.refunds.show', $returnRequest->refund_transaction_id) }}" class="text-accent-blue hover:text-white font-mono">#{{ $returnRequest->refund_transaction_id }}</a>
                                </div>
                                <div>
                                    <p class="text-text-secondary">Số tiền:</p>
                                    <p class="text-green-400 font-mono tabular-nums">{{ number_format($returnRequest->refundTransaction->amount, 0, ',', '.') }} VNĐ</p>
                                </div>
                                <div>
                                    <p class="text-text-secondary">Trạng thái:</p>
                                    <p class="text-white">{{ $returnRequest->refundTransaction->status }}</p>
                                </div>
                                <div>
                                    <p class="text-text-secondary">Ngày hoàn tiền:</p>
                                    <p class="text-white">{{ $returnRequest->refundTransaction->refunded_at ? $returnRequest->refundTransaction->refunded_at->format('d/m/Y H:i') : '—' }}</p>
                                </div>
                                @if($returnRequest->refund_reason)
                                    <div>
                                        <p class="text-text-secondary">Lý do hoàn tiền:</p>
                                        <p class="text-white">{{ $returnRequest->refund_reason }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                        <h3 class="text-white font-semibold mb-4">Thao tác</h3>
                        @php
                            $refundReasonLabels = [
                                'BATCH_FAILED' => 'Đợt gom thất bại',
                                'SUPPLIER_SHORTAGE' => 'Nhà cung cấp thiếu hàng',
                                'LOST_IN_TRANSIT' => 'Thất lạc khi vận chuyển',
                                'PAYMENT_ERROR' => 'Lỗi thanh toán',
                                'MANUFACTURER_DEFECT' => 'Lỗi nhà sản xuất',
                                'WRONG_ITEM' => 'Giao sai sản phẩm',
                                'DAMAGED_TRANSIT' => 'Hư hỏng khi vận chuyển',
                                'DELIVERY_FAILED' => 'Giao hàng thất bại',
                                'ADMIN_CANCEL' => 'Admin hủy đơn',
                            ];
                        @endphp
                        <div class="space-y-3">
                            @if($returnRequest->status === 'requested')
                                <form method="POST" action="{{ route('admin.returns.approve', $returnRequest) }}" onsubmit="return confirm('Approve yêu cầu trả hàng này?')">
                                    @csrf
                                    <button type="submit" class="w-full py-3 bg-blue-500/10 border border-blue-500/30 rounded-lg text-blue-400 text-sm hover:bg-blue-500/20 transition-colors">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.returns.reject', $returnRequest) }}" onsubmit="return confirm('Reject yêu cầu trả hàng này?')">
                                    @csrf
                                    <button type="submit" class="w-full py-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm hover:bg-red-500/20 transition-colors">Reject</button>
                                </form>
                            @endif

                            @if($returnRequest->status === 'approved')
                                <form method="POST" action="{{ route('admin.returns.receive', $returnRequest) }}" onsubmit="return confirm('Đánh dấu đã nhận hàng?')">
                                    @csrf
                                    <button type="submit" class="w-full py-3 bg-purple-500/10 border border-purple-500/30 rounded-lg text-purple-400 text-sm hover:bg-purple-500/20 transition-colors">Nhận hàng</button>
                                </form>
                                <form method="POST" action="{{ route('admin.returns.complete-no-return', $returnRequest) }}" onsubmit="return confirm('Hoàn thành KHÔNG cần thu hồi hàng?')" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label for="refund_reason_no_return" class="block text-text-secondary text-sm mb-2">Lý do hoàn tiền *</label>
                                        <select name="refund_reason" id="refund_reason_no_return" required class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                                            <option value="">-- Chọn lý do hoàn tiền --</option>
                                            @foreach($refundReasonLabels as $value => $label)
                                                <option value="{{ $value }}" {{ old('refund_reason', $returnRequest->refund_reason) === $value ? 'selected' : '' }}>{{ $label }} ({{ $value }})</option>
                                            @endforeach
                                        </select>
                                        @error('refund_reason') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <button type="submit" class="w-full py-3 bg-green-500/10 border border-green-500/30 rounded-lg text-green-400 text-sm hover:bg-green-500/20 transition-colors">Hoàn thành (không thu hồi)</button>
                                </form>
                                <form method="POST" action="{{ route('admin.returns.reject', $returnRequest) }}" onsubmit="return confirm('Reject yêu cầu trả hàng này?')">
                                    @csrf
                                    <button type="submit" class="w-full py-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm hover:bg-red-500/20 transition-colors">Reject</button>
                                </form>
                            @endif

                            @if($returnRequest->status === 'received')
                                <form method="POST" action="{{ route('admin.returns.inspect', $returnRequest) }}" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label for="inspection" class="block text-text-secondary text-sm mb-2">Kết quả kiểm tra</label>
                                        <select name="inspection" id="inspection" required class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                                            <option value="resellable">Resellable - Có thể bán lại</option>
                                            <option value="defective">Defective - Lỗi/hỏng</option>
                                            <option value="dispose">Dispose - Thanh lý</option>
                                        </select>
                                        @error('inspection') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <button type="submit" class="w-full py-3 bg-yellow-500/10 border border-yellow-500/30 rounded-lg text-yellow-400 text-sm hover:bg-yellow-500/20 transition-colors">Inspect</button>
                                </form>
                                @if($returnRequest->inspection)
                                    <form method="POST" action="{{ route('admin.returns.complete', $returnRequest) }}" class="space-y-3">
                                        @csrf
                                        <div>
                                            <label for="refund_reason" class="block text-text-secondary text-sm mb-2">Lý do hoàn tiền *</label>
                                            <select name="refund_reason" id="refund_reason" required class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                                                <option value="">-- Chọn lý do hoàn tiền --</option>
                                                @foreach($refundReasonLabels as $value => $label)
                                                    <option value="{{ $value }}" {{ old('refund_reason', $returnRequest->refund_reason) === $value ? 'selected' : '' }}>{{ $label }} ({{ $value }})</option>
                                                @endforeach
                                            </select>
                                            @error('refund_reason') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="restocked_qty" class="block text-text-secondary text-sm mb-2">Số lượng restock (0 = không restock)</label>
                                            <input type="number" name="restocked_qty" id="restocked_qty" min="0" max="{{ $returnRequest->quantity }}" value="{{ $returnRequest->inspection === 'resellable' ? $returnRequest->quantity : 0 }}" required class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                                            @error('restocked_qty') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <button type="submit" class="w-full py-3 bg-green-500/10 border border-green-500/30 rounded-lg text-green-400 text-sm hover:bg-green-500/20 transition-colors">Hoàn thành</button>
                                    </form>
                                @endif
                            @endif
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('admin.returns.index') }}" class="block w-full text-center py-3 text-text-secondary hover:text-white text-sm transition-colors">Quay lại</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection
