@extends('layouts.app')

@section('title', 'Tạo yêu cầu trả hàng - Gundam Shop')

@section('content')

<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-2 text-sm mb-4">
            <a href="{{ route('home') }}" class="text-text-secondary hover:text-white transition-colors">Trang chủ</a>
            <span class="text-border">/</span>
            <a href="{{ route('returns.mine') }}" class="text-text-secondary hover:text-white transition-colors">Trả hàng</a>
            <span class="text-border">/</span>
            <span class="text-white">Tạo mới</span>
        </nav>
        <div class="flex items-center gap-4">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">TẠO YÊU CẦU TRẢ HÀNG</h1>
        </div>
    </div>
</section>

<section class="py-8 lg:py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6 text-red-400 text-sm">{{ session('error') }}</div>
        @endif

        <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6">
            <h3 class="text-white font-semibold mb-4">Đơn hàng #{{ $order->id }}</h3>
            <div class="space-y-4">
                @foreach($returnableDetails as $item)
                    <div class="bg-bg-primary border border-border rounded-lg p-4">
                        <div class="flex gap-4">
                            @if($item['detail']->product)
                                <div class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-bg-secondary">
                                    <img src="{{ $item['detail']->product->image_url }}" alt="{{ $item['detail']->product_name }}" class="w-full h-full object-cover">
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-white font-medium text-sm">{{ $item['detail']->product_name }}</p>
                                <p class="text-text-secondary text-xs mt-1">Đã đặt: {{ $item['detail']->quantity }} | Có thể trả: {{ $item['remaining'] }}</p>
                                <p class="price-display text-sm mt-1">{{ number_format($item['detail']->price, 0, ',', '.') }} VNĐ/sp</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($returnableDetails->count() > 0)
            <form method="POST" action="{{ route('returns.store') }}" class="bg-bg-secondary border border-border rounded-xl p-6">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">

                <div class="space-y-4">
                    <div>
                        <label for="order_detail_id" class="block text-text-secondary text-sm mb-2">Sản phẩm cần trả *</label>
                        <select name="order_detail_id" id="order_detail_id" required class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                            <option value="">-- Chọn sản phẩm --</option>
                            @foreach($returnableDetails as $item)
                                <option value="{{ $item['detail']->id }}" data-max="{{ $item['remaining'] }}" {{ (int) old('order_detail_id', $selectedDetailId ?? 0) === (int) $item['detail']->id ? 'selected' : '' }}>
                                    {{ $item['detail']->product_name }} (tối đa {{ $item['remaining'] }})
                                </option>
                            @endforeach
                        </select>
                        @error('order_detail_id')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="quantity" class="block text-text-secondary text-sm mb-2">Số lượng trả *</label>
                        <input type="number" name="quantity" id="quantity" min="1" required class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue" placeholder="Nhập số lượng">
                        @error('quantity')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reason" class="block text-text-secondary text-sm mb-2">Lý do trả hàng *</label>
                        <textarea name="reason" id="reason" rows="3" required maxlength="500" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue resize-none" placeholder="Mô tả lý do trả hàng..."></textarea>
                        @error('reason')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="runner_condition" class="block text-text-secondary text-sm mb-2">Tình trạng Runner *</label>
                        <select name="runner_condition" id="runner_condition" required class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-blue">
                            <option value="sealed" {{ old('runner_condition') === 'sealed' ? 'selected' : '' }}>Nguyên seal (chưa mở)</option>
                            <option value="opened" {{ old('runner_condition') === 'opened' ? 'selected' : '' }}>Đã mở seal / Đã lắp ráp</option>
                            <option value="unknown" {{ old('runner_condition') === 'unknown' ? 'selected' : '' }}>Không chắc chắn</option>
                        </select>
                        <p class="text-text-secondary text-xs mt-1">Nếu runner đã mở, yêu cầu trả hàng sẽ bị từ chối và được gợi ý chuyển sang bảo hành (nÃ³ trong 7 ngày).</p>
                        @error('runner_condition')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex gap-4 mt-6">
                    <a href="{{ route('returns.mine') }}" class="flex-1 text-center py-3 text-text-secondary hover:text-white text-sm transition-colors">Hủy</a>
                    <button type="submit" class="flex-1 btn-primary py-3 text-sm">Gửi yêu cầu</button>
                </div>
            </form>
        @else
            <div class="bg-bg-secondary border border-border rounded-xl p-6 text-center text-text-secondary">
                Không có sản phẩm nào có thể trả trong đơn hàng này.
            </div>
        @endif
    </div>
</section>

@endsection
