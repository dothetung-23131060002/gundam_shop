@extends('layouts.app')

@section('title', 'Quản lý bảo hành - Gundam Shop')

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
                <h2 class="text-white font-semibold text-lg">Quản lý bảo hành</h2>
            </div>
        </header>

        <main class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-bg-secondary border border-border rounded-xl p-4">
                    <p class="text-text-secondary text-sm">Chờ xử lý</p>
                    <p class="text-2xl font-bold text-yellow-400">{{ $counts['requested'] }}</p>
                </div>
                <div class="bg-bg-secondary border border-border rounded-xl p-4">
                    <p class="text-text-secondary text-sm">Đã duyệt</p>
                    <p class="text-2xl font-bold text-accent-blue">{{ $counts['approved'] }}</p>
                </div>
                <div class="bg-bg-secondary border border-border rounded-xl p-4">
                    <p class="text-text-secondary text-sm">Đang xử lý</p>
                    <p class="text-2xl font-bold text-purple-400">{{ $counts['processing'] }}</p>
                </div>
            </div>

            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Mã</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Khách hàng</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Đơn hàng</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Sản phẩm</th>
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
                                    <td class="px-6 py-4">
                                        <span class="text-white text-sm">{{ $warranty->user->name ?? 'N/A' }}</span>
                                        <br><span class="text-text-secondary text-xs">{{ $warranty->user->email ?? '' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-text-secondary text-sm">#{{ $warranty->order_id }}</td>
                                    <td class="px-6 py-4 text-white text-sm">{{ $warranty->orderDetail->product_name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-text-secondary text-sm line-clamp-1 max-w-[150px]">{{ $warranty->reason }}</td>
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
                                    <td class="px-6 py-4 text-text-secondary text-sm">{{ $warranty->created_at->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.warranties.show', $warranty) }}" class="text-accent-blue hover:text-accent-blue/80 text-sm">Chi tiết →</a>
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
        </main>
    </div>
</div>

@endsection
