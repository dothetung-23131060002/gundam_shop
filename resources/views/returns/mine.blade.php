@extends('layouts.app')

@section('title', 'Yêu cầu trả hàng - Gundam Shop')

@section('content')

<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">YÊU CẦU TRẢ HÀNG</h1>
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

        <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="px-6 py-4 text-text-secondary text-sm font-medium">Mã</th>
                            <th class="px-6 py-4 text-text-secondary text-sm font-medium">Đơn hàng</th>
                            <th class="px-6 py-4 text-text-secondary text-sm font-medium">Sản phẩm</th>
                            <th class="px-6 py-4 text-text-secondary text-sm font-medium">SL</th>
                            <th class="px-6 py-4 text-text-secondary text-sm font-medium">Lý do</th>
                            <th class="px-6 py-4 text-text-secondary text-sm font-medium">Trạng thái</th>
                            <th class="px-6 py-4 text-text-secondary text-sm font-medium">Ngày tạo</th>
                            <th class="px-6 py-4 text-right text-text-secondary text-sm font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                            <tr class="border-b border-border/50 hover:bg-bg-primary/50 transition-colors">
                                <td class="px-6 py-4 text-white text-sm font-mono">#{{ $return->id }}</td>
                                <td class="px-6 py-4 text-white text-sm">#{{ $return->order_id }}</td>
                                <td class="px-6 py-4 text-white text-sm">{{ $return->orderDetail->product_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-white text-sm">{{ $return->quantity }}</td>
                                <td class="px-6 py-4 text-text-secondary text-sm max-w-[200px]">{{ Str::limit($return->reason, 40) }}</td>
                                <td class="px-6 py-4">
                                    @if($return->status === 'requested')
                                        <span class="px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 rounded-full text-yellow-400 text-xs">Chờ xử lý</span>
                                    @elseif($return->status === 'approved')
                                        <span class="px-3 py-1 bg-blue-500/10 border border-blue-500/30 rounded-full text-blue-400 text-xs">Đã duyệt</span>
                                    @elseif($return->status === 'received')
                                        <span class="px-3 py-1 bg-purple-500/10 border border-purple-500/30 rounded-full text-purple-400 text-xs">Đã nhận hàng</span>
                                    @elseif($return->status === 'completed')
                                        <span class="px-3 py-1 bg-green-500/10 border border-green-500/30 rounded-full text-green-400 text-xs">Hoàn thành</span>
                                    @elseif($return->status === 'rejected')
                                        <span class="px-3 py-1 bg-red-500/10 border border-red-500/30 rounded-full text-red-400 text-xs">Từ chối</span>
                                    @elseif($return->status === 'cancelled')
                                        <span class="px-3 py-1 bg-gray-500/10 border border-gray-500/30 rounded-full text-gray-400 text-xs">Đã hủy</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-text-secondary text-xs">{{ $return->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('returns.show', $return) }}" class="text-accent-blue hover:text-white text-sm transition-colors">Chi tiết →</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-text-secondary text-center">Chưa có yêu cầu trả hàng nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="px-6 py-4 border-t border-border">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    </div>
</section>

@endsection
