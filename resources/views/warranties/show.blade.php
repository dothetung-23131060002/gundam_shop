@extends('layouts.app')

@section('title', 'Bảo hành #' . $warrantyRequest->id . ' - Gundam Shop')

@section('content')
<section class="py-12 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-2xl lg:text-3xl font-bold text-white tracking-wider font-display">BẢO HÀNH #{{ $warrantyRequest->id }}</h1>
        </div>

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4 mb-6 text-green-400">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-accent-red/10 border border-accent-red/30 rounded-xl p-4 mb-6 text-accent-red">{{ session('error') }}</div>
        @endif

        @php
            $statusColors = [
                'requested' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30',
                'approved' => 'bg-accent-blue/10 text-accent-blue border-accent-blue/30',
                'processing' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                'completed' => 'bg-green-500/10 text-green-400 border-green-500/30',
                'rejected' => 'bg-accent-red/10 text-accent-red border-accent-red/30',
                'cancelled' => 'bg-text-secondary/10 text-text-secondary border-text-secondary/30',
            ];
            $statusLabels = [
                'requested' => 'Chờ xử lý',
                'approved' => 'Đã duyệt',
                'processing' => 'Đang xử lý',
                'completed' => 'Hoàn thành',
                'rejected' => 'Từ chối',
                'cancelled' => 'Đã hủy',
            ];
            $resolutionLabels = [
                'part_replaced' => 'Thay thế part',
                'runner_replaced' => 'Thay thế runner',
                'repaired' => 'Sửa chữa',
                'replaced_product' => 'Thay thế sản phẩm',
                'rejected' => 'Từ chối',
            ];
        @endphp

        <div class="grid lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <h3 class="text-white font-semibold mb-4">Thông tin yêu cầu</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-text-secondary">Trạng thái:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusColors[$warrantyRequest->status] ?? '' }}">
                                {{ $statusLabels[$warrantyRequest->status] ?? $warrantyRequest->status }}
                            </span>
                        </div>
                        <div class="flex justify-between"><span class="text-text-secondary">Đơn hàng:</span><a href="{{ route('orders.show', $warrantyRequest->order_id) }}" class="text-accent-blue">#{{ $warrantyRequest->order_id }}</a></div>
                        <div class="flex justify-between"><span class="text-text-secondary">Sản phẩm:</span><span class="text-white">{{ $warrantyRequest->orderDetail->product_name ?? 'N/A' }}</span></div>
                        <div class="flex justify-between"><span class="text-text-secondary">Số lượng:</span><span class="text-white">{{ $warrantyRequest->quantity }}</span></div>
                        <div class="flex justify-between"><span class="text-text-secondary">Lý do:</span><span class="text-white">{{ $warrantyRequest->reason }}</span></div>
                        @if($warrantyRequest->description)
                            <div><span class="text-text-secondary">Mô tả:</span><p class="text-white text-sm mt-1">{{ $warrantyRequest->description }}</p></div>
                        @endif
                        <div class="flex justify-between"><span class="text-text-secondary">Ngày gửi:</span><span class="text-white">{{ $warrantyRequest->created_at->format('d/m/Y H:i') }}</span></div>
                        @if($warrantyRequest->order && $warrantyRequest->order->delivered_at)
                            <div class="flex justify-between"><span class="text-text-secondary">Hạn bảo hành (7 ngày từ ngày giao):</span><span class="text-white">{{ $warrantyRequest->order->delivered_at->copy()->addDays(7)->format('d/m/Y') }}</span></div>
                        @endif
                        @if($warrantyRequest->handledBy)
                            <div class="flex justify-between"><span class="text-text-secondary">Xử lý bởi:</span><span class="text-white">{{ $warrantyRequest->handledBy->name }}</span></div>
                        @endif
                        @if($warrantyRequest->resolution)
                            <div class="flex justify-between"><span class="text-text-secondary">Kết quả:</span><span class="text-white">{{ $resolutionLabels[$warrantyRequest->resolution] ?? $warrantyRequest->resolution }}</span></div>
                        @endif
                        @if($warrantyRequest->received_at)
                            <div class="flex justify-between"><span class="text-text-secondary">Ngày nhận xử lý:</span><span class="text-white">{{ $warrantyRequest->received_at->format('d/m/Y H:i') }}</span></div>
                        @endif
                        @if($warrantyRequest->completed_at)
                            <div class="flex justify-between"><span class="text-text-secondary">Ngày hoàn thành:</span><span class="text-white">{{ $warrantyRequest->completed_at->format('d/m/Y H:i') }}</span></div>
                        @endif
                    </div>
                </div>

                @if($warrantyRequest->evidence && count($warrantyRequest->evidence) > 0)
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Ảnh bằng chứng</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            @foreach($warrantyRequest->evidence as $path)
                                <div class="rounded-lg overflow-hidden border border-border">
                                    <img src="{{ asset('storage/' . $path) }}" alt="Evidence" class="w-full h-32 object-cover" onerror="this.src='{{ asset('assets/images/no-image.jpg') }}'">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-1">
                <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                    @if(!in_array($warrantyRequest->status, ['completed', 'rejected', 'cancelled']))
                        <form action="{{ route('warranties.cancel', $warrantyRequest) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu bảo hành này?')">
                            @csrf
                            <button type="submit" class="w-full py-3 border border-accent-red/50 text-accent-red rounded-xl hover:bg-accent-red/10 transition-colors">Hủy yêu cầu</button>
                        </form>
                    @else
                        <p class="text-text-secondary text-sm text-center">Yêu cầu đã kết thúc.</p>
                    @endif

                    <a href="{{ route('warranties.mine') }}" class="block mt-4 text-center text-text-secondary hover:text-white text-sm">← Quay lại danh sách</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
