@extends('layouts.app')

@section('title', 'Quản lý trả hàng - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h2 class="text-white font-semibold text-lg">Quản lý trả hàng</h2>
                </div>
                <form method="GET" class="flex items-center gap-2">
                    <select name="status" class="bg-bg-primary border border-border rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-accent-blue">
                        <option value="">Tất cả</option>
                        <option value="requested" @if(request('status') === 'requested') selected @endif>Chờ xử lý</option>
                        <option value="approved" @if(request('status') === 'approved') selected @endif>Đã duyệt</option>
                        <option value="received" @if(request('status') === 'received') selected @endif>Đã nhận hàng</option>
                        <option value="completed" @if(request('status') === 'completed') selected @endif>Hoàn thành</option>
                        <option value="rejected" @if(request('status') === 'rejected') selected @endif>Từ chối</option>
                        <option value="cancelled" @if(request('status') === 'cancelled') selected @endif>Đã hủy</option>
                    </select>
                    <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tên, email..." aria-label="Tìm theo tên hoặc email khách" class="bg-bg-primary border border-border rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-accent-blue w-44">
                    <button type="submit" class="btn-primary px-4 py-2 text-sm">Lọc</button>
                </form>
            </div>
        </header>

        <main class="p-6">
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-bg-secondary border border-border rounded-xl p-4">
                    <p class="text-text-secondary text-sm">Chờ xử lý</p>
                    <p class="text-2xl font-bold text-yellow-400 font-mono tabular-nums mt-1">{{ $counts['requested'] }}</p>
                </div>
                <div class="bg-bg-secondary border border-border rounded-xl p-4">
                    <p class="text-text-secondary text-sm">Đã duyệt</p>
                    <p class="text-2xl font-bold text-blue-400 font-mono tabular-nums mt-1">{{ $counts['approved'] }}</p>
                </div>
                <div class="bg-bg-secondary border border-border rounded-xl p-4">
                    <p class="text-text-secondary text-sm">Đã nhận hàng</p>
                    <p class="text-2xl font-bold text-purple-400 font-mono tabular-nums mt-1">{{ $counts['received'] }}</p>
                </div>
            </div>

            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Mã</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Khách hàng</th>
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
                                    <td class="px-6 py-4">
                                        <p class="text-white text-sm">{{ $return->requestedBy->name ?? 'N/A' }}</p>
                                        <p class="text-text-secondary text-xs">{{ $return->requestedBy->email ?? '' }}</p>
                                    </td>
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
                                        <a href="{{ route('admin.returns.show', $return) }}" class="text-accent-blue hover:text-white text-sm transition-colors">Chi tiết →</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-text-secondary text-center">Chưa có yêu cầu trả hàng nào.</td>
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
        </main>
    </div>
</div>

@endsection
