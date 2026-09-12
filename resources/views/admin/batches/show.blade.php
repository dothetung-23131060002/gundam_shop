@extends('layouts.app')

@section('title', 'Chi tiết đợt gom #' . $batch->id . ' - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.batches.index') }}" class="text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-white font-semibold text-lg">Đợt gom #{{ $batch->id }}</h2>
                @if($batch->status === 'open')
                    <x-status-pill tone="green">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                        Đang mở
                    </x-status-pill>
                @elseif($batch->status === 'success')
                    <x-status-pill tone="gold">Thành công</x-status-pill>
                @elseif($batch->status === 'failed')
                    <x-status-pill tone="red">Thất bại</x-status-pill>
                @else
                    <x-status-pill tone="blue">Đang xử lý</x-status-pill>
                @endif
            </div>
        </header>

        <main class="p-6">
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Left: Product + Progress -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Product Info -->
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <div class="flex items-start gap-4">
                            <img src="{{ $batch->product->image_url }}" alt="{{ $batch->product->name }}" class="w-20 h-20 rounded-xl object-cover">
                            <div>
                                <h3 class="text-white font-semibold text-lg">{{ $batch->product->name }}</h3>
                                <p class="text-text-secondary text-sm">{{ $batch->product->category->name ?? '' }} {{ $batch->product->brand ? '• ' . $batch->product->brand->name : '' }}</p>
                                <p class="price-display text-lg mt-1">{{ number_format($batch->product->price, 0, ',', '.') }} VNĐ</p>
                            </div>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Tiến độ đợt gom</h3>

                        <div class="mb-6">
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-text-secondary text-sm">Slot đã đặt cọc</span>
                                <span class="text-white font-mono text-lg">{{ $reservedCount }} <span class="text-text-secondary">/ {{ $batch->threshold }}</span></span>
                            </div>
                            <div class="w-full h-3 bg-bg-primary rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $batch->isFull() ? 'bg-green-500' : 'bg-accent-gold' }}" style="width: {{ min($progressPercent, 100) }}%"></div>
                            </div>
                            <div class="flex justify-between mt-1">
                                <span class="text-text-secondary text-xs font-mono">{{ $progressPercent }}%</span>
                                <span class="text-text-secondary text-xs">Ngưỡng: {{ $batch->threshold }} slot</span>
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-3 gap-4">
                            <div class="bg-bg-primary rounded-lg p-4 text-center">
                                <p class="text-text-secondary text-xs mb-1">Tiền cọc/slot</p>
                                <p class="text-accent-gold font-mono font-bold">{{ number_format($batch->deposit_amount, 0, ',', '.') }}đ</p>
                            </div>
                            <div class="bg-bg-primary rounded-lg p-4 text-center">
                                <p class="text-text-secondary text-xs mb-1">Deadline</p>
                                <p class="text-white font-mono text-sm">{{ $batch->deadline->format('d/m/Y H:i') }}</p>
                                <p class="text-text-secondary text-xs mt-1">{{ $timeRemaining }}</p>
                            </div>
                            <div class="bg-bg-primary rounded-lg p-4 text-center">
                                <p class="text-text-secondary text-xs mb-1">Tổng cọc đã thu</p>
                                <p class="text-white font-mono font-bold">{{ number_format($batch->deposit_amount * $reservedCount, 0, ',', '.') }}đ</p>
                            </div>
                        </div>
                    </div>

                    <!-- Reservations List -->
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Danh sách khách giữ slot ({{ $batch->activeReservations->count() }})</h3>

                        @if($batch->activeReservations->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b border-border">
                                            <th class="px-4 py-3 text-text-secondary text-sm font-medium">Khách hàng</th>
                                            <th class="px-4 py-3 text-text-secondary text-sm font-medium">Slot</th>
                                            <th class="px-4 py-3 text-text-secondary text-sm font-medium">Đã cọc</th>
                                            <th class="px-4 py-3 text-text-secondary text-sm font-medium">Trạng thái</th>
                                            <th class="px-4 py-3 text-text-secondary text-sm font-medium">Ngày đặt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($batch->activeReservations as $reservation)
                                            <tr class="border-b border-border/50">
                                                <td class="px-4 py-3">
                                                    <div>
                                                        <p class="text-white text-sm">{{ $reservation->user->name }}</p>
                                                        <p class="text-text-secondary text-xs">{{ $reservation->user->email }}</p>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-white text-sm font-mono">{{ $reservation->quantity }}</td>
                                                <td class="px-4 py-3 price-display text-sm">{{ number_format($reservation->deposit_paid, 0, ',', '.') }}</td>
                                                <td class="px-4 py-3">
                                                    @if($reservation->status === 'reserved')
                                                        <x-status-pill tone="green">Đang giữ</x-status-pill>
                                                    @elseif($reservation->status === 'refunded')
                                                        <x-status-pill tone="red">Đã hoàn cọc</x-status-pill>
                                                    @else
                                                        <x-status-pill tone="gold">Đã chuyển đơn</x-status-pill>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-text-secondary text-xs">{{ $reservation->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-text-secondary text-center py-8">Chưa có khách nào giữ slot.</p>
                        @endif
                    </div>
                </div>

                <!-- Right: Summary + Actions -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                        <h3 class="text-white font-semibold mb-4">Thông tin đợt gom</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Mã đợt gom:</span>
                                <span class="text-white font-mono">#{{ $batch->id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Sản phẩm:</span>
                                <span class="text-white text-right max-w-[180px]">{{ $batch->product->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Ngưỡng:</span>
                                <span class="text-white font-mono">{{ $batch->threshold }} slot</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Cọc/slot:</span>
                                <span class="text-accent-gold font-mono">{{ number_format($batch->deposit_amount, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Hết hạn:</span>
                                <span class="text-white">{{ $batch->deadline->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-secondary">Tạo lúc:</span>
                                <span class="text-white">{{ $batch->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>

                        @if($batch->status === 'open')
                            <div class="mt-6 space-y-3">
                                <a href="{{ route('admin.batches.edit', $batch) }}" class="block w-full text-center btn-primary py-3 text-sm font-medium">Chỉnh sửa</a>
                                @if($batch->isFull())
                                    <form action="{{ route('admin.batches.close-early', $batch) }}" method="POST" onsubmit="var r=prompt('Lý do đóng sớm (không bắt buộc):','');if(r===null)return false;this.reason.value=r;return confirm('Đóng sớm đợt gom này? Đợt đã ĐỦ ngưỡng, tất cả khách sẽ nhận thông báo thanh toán.')">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="reason">
                                        <button type="submit" class="block w-full text-center py-3 text-sm font-medium bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 hover:bg-green-500/20 transition-colors">ĐÓNG BATCH SỚM (ĐỦ NGƯỠNG)</button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.batches.force-success', $batch) }}" method="POST" onsubmit="var r=prompt('Lý do buộc demo (không bắt buộc):','');if(r===null)return false;this.reason.value=r;return confirm('DEMO: Buộc thành công dù CHƯA đủ ngưỡng? Chỉ dùng để demo/test.')">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="reason">
                                        <button type="submit" class="block w-full text-center py-3 text-sm font-medium bg-accent-gold/10 border border-accent-gold/30 rounded-xl text-accent-gold hover:bg-accent-gold/20 transition-colors">BUỘC THÀNH CÔNG (DEMO)</button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.batches.force-fail', $batch) }}" method="POST" onsubmit="var r=prompt('Lý do chốt thất bại (không bắt buộc):','');if(r===null)return false;this.reason.value=r;return confirm('Chốt thất bại đợt gom này? Tất cả khách giữ slot sẽ được hoàn cọc.')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="reason">
                                    <button type="submit" class="block w-full text-center py-3 text-sm font-medium bg-accent-red/10 border border-accent-red/30 rounded-xl text-accent-red hover:bg-accent-red/20 transition-colors">CHỐT THẤT BẠI</button>
                                </form>
                                <a href="{{ route('admin.batches.index') }}" class="block w-full text-center py-3 text-text-secondary hover:text-white text-sm transition-colors">Quay lại</a>
                            </div>
                        @else
                            <div class="mt-6">
                                <a href="{{ route('admin.batches.index') }}" class="block w-full text-center py-3 text-text-secondary hover:text-white text-sm transition-colors">Quay lại</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection
