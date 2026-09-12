@extends('layouts.app')

@section('title', 'Quản lý đơn hàng - Gundam Shop')

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
                    <h2 class="text-white font-semibold text-lg">Quản lý đơn hàng</h2>
                </div>
                <a href="{{ route('admin.orders.export') }}" aria-label="Xuất CSV toàn bộ đơn hàng" class="btn-secondary px-4 py-2 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Xuất CSV
                </a>
            </div>
        </header>

        <main class="p-6">
            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Mã đơn</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Khách hàng</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Tổng tiền</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Thanh toán</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Trạng thái</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Ngày tạo</th>
                                <th class="text-right text-text-secondary text-sm font-medium px-6 py-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($orders as $order)
                                <tr class="hover:bg-bg-primary/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="text-accent-blue font-medium">#{{ $order->id }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div>
                                            <p class="text-white text-sm font-medium">{{ $order->customer_name }}</p>
                                            <p class="text-text-secondary text-xs">{{ $order->customer_phone }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="price-display text-sm">{{ number_format($order->total_amount, 0, ',', '.') }} VNĐ</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($order->payment_status == 'paid')
                                            <x-status-pill tone="green">Đã thanh toán</x-status-pill>
                                        @else
                                            <x-status-pill tone="gold">Chờ thanh toán</x-status-pill>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <select name="order_status" aria-label="Đổi trạng thái đơn hàng #{{ $order->id }}" onchange="if(confirm('Bạn có chắc muốn thay đổi trạng thái đơn hàng này?')){this.form.submit()}" class="text-xs px-3 py-1 rounded-full border cursor-pointer bg-bg-primary
                                                @if($order->order_status == 'pending') text-yellow-400 border-yellow-500/30
                                                @elseif($order->order_status == 'confirmed') text-blue-400 border-blue-500/30
                                                @elseif($order->order_status == 'shipping') text-purple-400 border-purple-500/30
                                                @elseif($order->order_status == 'completed') text-green-400 border-green-500/30
                                                @elseif($order->order_status == 'cancelled') text-red-400 border-red-500/30
                                                @endif">
                                                <option value="pending" {{ $order->order_status == 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                                                <option value="confirmed" {{ $order->order_status == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                                                <option value="shipping" {{ $order->order_status == 'shipping' ? 'selected' : '' }}>Đang giao</option>
                                                <option value="completed" {{ $order->order_status == 'completed' ? 'selected' : '' }}>Đã giao</option>
                                                <option value="cancelled" {{ $order->order_status == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-text-secondary text-sm">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-accent-blue hover:text-white text-sm transition-colors">Chi tiết →</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <p class="text-text-secondary">Không có đơn hàng nào</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-border">
                    {{ $orders->links() }}
                </div>
            </div>
        </main>
    </div>
</div>

@endsection

