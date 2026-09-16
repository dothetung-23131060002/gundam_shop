@extends('layouts.app')

@section('title', 'Bảo hành #' . $warrantyRequest->id . ' - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.warranties.index') }}" class="p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h2 class="text-white font-semibold text-lg">Bảo hành #{{ $warrantyRequest->id }}</h2>
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
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusColors[$warrantyRequest->status] ?? '' }}">
                    {{ $statusLabels[$warrantyRequest->status] ?? $warrantyRequest->status }}
                </span>
            </div>
        </header>

        <main class="p-6">
            @if(session('success'))
                <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4 mb-6 text-green-400">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-accent-red/10 border border-accent-red/30 rounded-xl p-4 mb-6 text-accent-red">{{ session('error') }}</div>
            @endif

            <div class="grid lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Thông tin khách hàng</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between"><span class="text-text-secondary">Khách hàng:</span><span class="text-white">{{ $warrantyRequest->user->name ?? 'N/A' }}</span></div>
                            <div class="flex justify-between"><span class="text-text-secondary">Email:</span><span class="text-white">{{ $warrantyRequest->user->email ?? 'N/A' }}</span></div>
                            <div class="flex justify-between"><span class="text-text-secondary">Đơn hàng:</span><a href="{{ route('admin.orders.show', $warrantyRequest->order_id) }}" class="text-accent-blue">#{{ $warrantyRequest->order_id }}</a></div>
                        </div>
                    </div>

                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Thông tin bảo hành</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between"><span class="text-text-secondary">Sản phẩm:</span><span class="text-white">{{ $warrantyRequest->orderDetail->product_name ?? 'N/A' }}</span></div>
                            <div class="flex justify-between"><span class="text-text-secondary">Số lượng:</span><span class="text-white">{{ $warrantyRequest->quantity }}</span></div>
                            <div class="flex justify-between"><span class="text-text-secondary">Lý do:</span><span class="text-white">{{ $warrantyRequest->reason }}</span></div>
                            @if($warrantyRequest->description)
                                <div><span class="text-text-secondary">Mô tả:</span><p class="text-white text-sm mt-1">{{ $warrantyRequest->description }}</p></div>
                            @endif
                            <div class="flex justify-between"><span class="text-text-secondary">Ngày gửi:</span><span class="text-white">{{ $warrantyRequest->created_at->format('d/m/Y H:i') }}</span></div>
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
                    <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24 space-y-4">
                        @if($warrantyRequest->status === 'requested')
                            <form action="{{ route('admin.warranties.approve', $warrantyRequest) }}" method="POST" onsubmit="return confirm('Duyệt yêu cầu bảo hành này?')">
                                @csrf
                                <button type="submit" class="w-full py-3 bg-accent-blue text-white rounded-xl hover:bg-accent-blue/80 transition-colors">Duyệt</button>
                            </form>
                            <form action="{{ route('admin.warranties.reject', $warrantyRequest) }}" method="POST" onsubmit="return confirm('Từ chối yêu cầu bảo hành này?')">
                                @csrf
                                <button type="submit" class="w-full py-3 border border-accent-red/50 text-accent-red rounded-xl hover:bg-accent-red/10 transition-colors">Từ chối</button>
                            </form>
                        @endif

                        @if($warrantyRequest->status === 'approved')
                            <form action="{{ route('admin.warranties.processing', $warrantyRequest) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-3 bg-purple-500 text-white rounded-xl hover:bg-purple-500/80 transition-colors">Bắt đầu xử lý</button>
                            </form>
                            <form action="{{ route('admin.warranties.reject', $warrantyRequest) }}" method="POST" onsubmit="return confirm('Từ chối yêu cầu bảo hành này?')">
                                @csrf
                                <button type="submit" class="w-full py-3 border border-accent-red/50 text-accent-red rounded-xl hover:bg-accent-red/10 transition-colors">Từ chối</button>
                            </form>
                        @endif

                        @if($warrantyRequest->status === 'processing')
                            <form action="{{ route('admin.warranties.complete', $warrantyRequest) }}" method="POST" onsubmit="return confirm('Hoàn thành xử lý bảo hành?')">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-text-secondary text-sm mb-2">Kết quả xử lý</label>
                                    <select name="resolution" class="form-input text-sm w-full" required>
                                        <option value="part_replaced">Thay thế part</option>
                                        <option value="runner_replaced">Thay thế runner</option>
                                        <option value="repaired">Sửa chữa</option>
                                        <option value="replaced_product">Thay thế sản phẩm</option>
                                    </select>
                                    @error('resolution') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <button type="submit" class="w-full py-3 bg-green-500 text-white rounded-xl hover:bg-green-500/80 transition-colors">Hoàn thành</button>
                            </form>
                        @endif

                        @if(in_array($warrantyRequest->status, ['completed', 'rejected', 'cancelled']))
                            <p class="text-text-secondary text-sm text-center">Yêu cầu đã kết thúc.</p>
                        @endif

                        <a href="{{ route('admin.warranties.index') }}" class="block text-center text-text-secondary hover:text-white text-sm">← Quay lại danh sách</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection
