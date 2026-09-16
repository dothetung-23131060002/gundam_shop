@extends('layouts.app')

@section('title', 'Admin Dashboard - Gundam Shop')

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
                    <h2 class="text-white font-semibold text-lg">Dashboard</h2>
                </div>
                <a href="{{ route('home') }}" class="text-text-secondary hover:text-white transition-colors text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Xem trang web
                </a>
            </div>
        </header>

        <main class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-text-secondary text-sm">Doanh thu thuần (Net)</p>
                            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ number_format($netRevenue, 0, ',', '.') }} VNĐ</p>
                            <p class="text-text-secondary text-xs mt-1 tabular-nums">Gross {{ number_format($grossCollected, 0, ',', '.') }} − Hoàn {{ number_format($successfulRefunds, 0, ',', '.') }}</p>
                            <p class="text-text-secondary text-xs tabular-nums">Tịch thu: {{ number_format($forfeitureIncome, 0, ',', '.') }}đ (không trừ Net)</p>
                        </div>
                        <div class="w-12 h-12 bg-accent-blue/10 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>

                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-text-secondary text-sm">Đợt gom đang mở</p>
                            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $openBatches }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>

                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-text-secondary text-sm">Slot đang giữ</p>
                            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $totalReservations }}</p>
                        </div>
                        <div class="w-12 h-12 bg-accent-gold/10 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-accent-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        </div>
                    </div>
                </div>

                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-text-secondary text-sm">Tổng sản phẩm</p>
                            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $totalProducts }}</p>
                        </div>
                        <div class="w-12 h-12 bg-accent-red/10 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-accent-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-white font-semibold">Thống kê đợt gom</h3>
                    <a href="{{ route('admin.batches.index') }}" class="text-accent-blue text-sm hover:underline">Quản lý đợt gom</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-bg-primary rounded-lg p-4 text-center">
                        <p class="text-text-secondary text-xs mb-1">Tỷ lệ thành công</p>
                        <p class="text-green-400 font-bold font-mono tabular-nums text-2xl">{{ $successRate }}%</p>
                    </div>
                    <div class="bg-bg-primary rounded-lg p-4 text-center">
                        <p class="text-text-secondary text-xs mb-1">Tỷ lệ thất bại</p>
                        <p class="text-accent-red font-bold font-mono tabular-nums text-2xl">{{ $failedRate }}%</p>
                    </div>
                    <div class="bg-bg-primary rounded-lg p-4 text-center">
                        <p class="text-text-secondary text-xs mb-1">Tổng cọc đang giữ</p>
                        <p class="text-accent-gold font-bold font-mono tabular-nums text-2xl">{{ number_format($heldDeposit, 0, ',', '.') }}đ</p>
                    </div>
                </div>
                <h4 class="text-white text-sm font-medium mb-3">Top sản phẩm được cọc nhiều nhất</h4>
                @if($topProducts->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-border">
                                    <th class="py-2 pr-4 text-text-secondary text-xs font-medium">Sản phẩm</th>
                                    <th class="py-2 pr-4 text-text-secondary text-xs font-medium text-right">Slot giữ</th>
                                    <th class="py-2 text-text-secondary text-xs font-medium text-right">Cọc giữ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topProducts as $index => $item)
                                    <tr class="border-b border-border/50">
                                        <td class="py-2 pr-4 text-white text-sm">{{ $index + 1 }}. {{ $item->name }}</td>
                                        <td class="py-2 pr-4 text-white text-sm font-mono tabular-nums text-right">{{ $item->total_slots }}</td>
                                        <td class="py-2 text-accent-gold text-sm font-mono tabular-nums text-right">{{ number_format($item->total_deposit, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-text-secondary text-center text-sm py-4">Chưa có slot nào đang giữ.</p>
                @endif
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-white font-semibold">Đơn hàng chờ xử lý</h3>
                        <a href="{{ route('admin.orders.index') }}" class="text-accent-blue text-sm hover:underline">Xem tất cả</a>
                    </div>
                    <div class="space-y-4">
                        @if($newOrders->count() > 0)
                            @foreach($newOrders as $order)
                                <div class="flex items-center justify-between p-3 bg-bg-primary rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-accent-blue/20 flex items-center justify-center">
                                            <span class="text-accent-blue text-sm font-medium">#{{ $order->id }}</span>
                                        </div>
                                        <div>
                                            <p class="text-white text-sm font-medium">{{ $order->customer_name }}</p>
                                            <p class="text-text-secondary text-xs">{{ $order->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="price-display text-sm tabular-nums">{{ number_format($order->total_amount, 0, ',', '.') }}</p>
                                        <x-status-pill tone="gold">Chờ xác nhận</x-status-pill>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-text-secondary text-center py-8">Không có đơn hàng chờ xử lý</p>
                        @endif
                    </div>
                </div>

                <div class="bg-bg-secondary border border-border rounded-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-white font-semibold">Thao tác nhanh</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="{{ route('admin.batches.create') }}" class="p-4 bg-bg-primary border border-border rounded-xl hover:border-accent-blue/50 transition-colors text-center">
                            <svg class="w-8 h-8 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-white text-sm">Tạo đợt gom</span>
                        </a>
                        <a href="{{ route('admin.batches.index') }}" class="p-4 bg-bg-primary border border-border rounded-xl hover:border-accent-blue/50 transition-colors text-center">
                            <svg class="w-8 h-8 mx-auto text-accent-gold mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span class="text-white text-sm">Đợt gom</span>
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="p-4 bg-bg-primary border border-border rounded-xl hover:border-accent-blue/50 transition-colors text-center">
                            <svg class="w-8 h-8 mx-auto text-accent-red mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span class="text-white text-sm">Sản phẩm</span>
                        </a>
                        <a href="{{ route('admin.orders.index') }}" class="p-4 bg-bg-primary border border-border rounded-xl hover:border-accent-blue/50 transition-colors text-center">
                            <svg class="w-8 h-8 mx-auto text-accent-blue mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span class="text-white text-sm">Đơn hàng</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection

