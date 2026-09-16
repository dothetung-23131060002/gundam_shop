@extends('layouts.app')

@section('title', 'Chi tiết người dùng - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <a href="{{ route('admin.users.index') }}" class="text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="text-white font-semibold text-lg">Người dùng: {{ $user->name }}</h2>
            </div>
        </header>

        <main class="p-6">
            <div class="grid lg:grid-cols-3 gap-6">
                
                <!-- User Info -->
                <div class="lg:col-span-1">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <div class="text-center mb-6">
                            <div class="w-20 h-20 rounded-full bg-accent-blue/20 flex items-center justify-center mx-auto mb-4">
                                <span class="text-accent-blue font-bold text-2xl">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <h3 class="text-white font-semibold text-lg">{{ $user->name }}</h3>
                            <p class="text-text-secondary text-sm">{{ $user->email }}</p>
                            @if($user->role == 'admin')
                                <x-status-pill tone="red" class="mt-2">Admin</x-status-pill>
                            @else
                                <x-status-pill tone="blue" class="mt-2">User</x-status-pill>
                            @endif
                        </div>

                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Số điện thoại:</span>
                                <span class="text-white">{{ $user->phone ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Địa chỉ:</span>
                                <span class="text-white text-right max-w-[200px]">{{ $user->address ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Ngày tham gia:</span>
                                <span class="text-white">{{ $user->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Tổng đơn hàng:</span>
                                <span class="text-white">{{ $user->orders->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Orders -->
                <div class="lg:col-span-2">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Lịch sử đơn hàng</h3>
                        
                        @if($user->orders->count() > 0)
                            <div class="space-y-4">
                                @foreach($user->orders->sortByDesc('created_at') as $order)
                                    <div class="flex items-center justify-between p-4 bg-bg-primary rounded-xl">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-full bg-accent-blue/20 flex items-center justify-center">
                                                <span class="text-accent-blue font-medium">#{{ $order->id }}</span>
                                            </div>
                                            <div>
                                                <p class="text-white font-medium text-sm">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                                                <p class="text-text-secondary text-xs">{{ $order->details->count() }} sản phẩm</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="price-display text-sm">{{ number_format($order->total_amount, 0, ',', '.') }}</p>
                                            @if($order->order_status == 'pending')
                                                <span class="text-xs text-yellow-400">Chờ xác nhận</span>
                                            @elseif($order->order_status == 'confirmed')
                                                <span class="text-xs text-blue-400">Đã xác nhận</span>
                                            @elseif($order->order_status == 'completed')
                                                <span class="text-xs text-green-400">Đã giao</span>
                                            @elseif($order->order_status == 'cancelled')
                                                <span class="text-xs text-red-400">Đã hủy</span>
                                            @elseif($order->order_status == 'shipping')
                                                <span class="text-xs text-purple-400">Đang giao</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-text-secondary text-center py-8">Chưa có đơn hàng nào</p>
                        @endif
                    </div>

                    <div class="bg-bg-secondary border border-border rounded-xl p-6 mt-6">
                        <h3 class="text-white font-semibold mb-4">Lịch sử giữ slot ({{ $user->reservations->count() }})</h3>

                        @if($user->reservations->count() > 0)
                            <div class="space-y-4">
                                @foreach($user->reservations->sortByDesc('created_at') as $reservation)
                                    <div class="flex items-center justify-between p-4 bg-bg-primary rounded-xl">
                                        <div class="flex items-center gap-4 min-w-0">
                                            <div class="w-12 h-12 rounded-full bg-accent-gold/20 flex items-center justify-center flex-shrink-0">
                                                <span class="text-accent-gold font-medium text-xs">#{{ $reservation->batch_id }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-white font-medium text-sm truncate">{{ $reservation->batch->product->name ?? 'N/A' }}</p>
                                                <p class="text-text-secondary text-xs">{{ $reservation->quantity }} slot • {{ $reservation->created_at->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <p class="text-accent-gold font-mono tabular-nums text-sm">{{ number_format($reservation->deposit_paid, 0, ',', '.') }}đ</p>
                                            @if($reservation->status === 'reserved')
                                                <x-status-pill tone="green">Đang giữ</x-status-pill>
                                            @elseif($reservation->status === 'converted')
                                                <x-status-pill tone="gold">Đã chuyển đơn</x-status-pill>
                                            @elseif($reservation->status === 'refunded')
                                                <x-status-pill tone="red">Đã hoàn cọc</x-status-pill>
                                            @else
                                                <x-status-pill tone="red">Đã hủy</x-status-pill>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-text-secondary text-center py-8">Chưa từng giữ slot nào</p>
                        @endif
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection

