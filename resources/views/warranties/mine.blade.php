@extends('layouts.app')

@section('title', 'Yêu cầu bảo hành - Gundam Shop')

@section('content')
<section class="py-12 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-2xl lg:text-3xl font-bold text-white tracking-wider font-display">YÊU CẦU BẢO HÀNH</h1>
        </div>

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4 mb-6 text-green-400">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-accent-red/10 border border-accent-red/30 rounded-xl p-4 mb-6 text-accent-red">{{ session('error') }}</div>
        @endif

        @if($warranties->count() > 0)
            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Mã</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Đơn hàng</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Sản phẩm</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">SL</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Lý do</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Trạng thái</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Ngày tạo</th>
                                <th class="text-right text-text-secondary text-sm font-medium px-6 py-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($warranties as $warranty)
                                <tr class="hover:bg-bg-primary/50 transition-colors">
                                    <td class="px-6 py-4 text-white text-sm">#{{ $warranty->id }}</td>
                                    <td class="px-6 py-4 text-text-secondary text-sm">#{{ $warranty->order_id }}</td>
                                    <td class="px-6 py-4 text-white text-sm">{{ $warranty->orderDetail->product_name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-text-secondary text-sm">{{ $warranty->quantity }}</td>
                                    <td class="px-6 py-4 text-text-secondary text-sm line-clamp-1 max-w-[200px]">{{ $warranty->reason }}</td>
                                    <td class="px-6 py-4">
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
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusColors[$warranty->status] ?? '' }}">
                                            {{ $statusLabels[$warranty->status] ?? $warranty->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-text-secondary text-sm">{{ $warranty->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('warranties.show', $warranty) }}" class="text-accent-blue hover:text-accent-blue/80 text-sm">Chi tiết →</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <p class="text-text-secondary">Chưa có yêu cầu bảo hành nào.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-border">
                    {{ $warranties->links() }}
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-text-secondary text-lg">Chưa có yêu cầu bảo hành nào.</p>
            </div>
        @endif
    </div>
</section>
@endsection
