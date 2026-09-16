@extends('layouts.app')

@section('title', 'Tạo yêu cầu bảo hành - Gundam Shop')

@section('content')
<section class="py-12 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-2xl lg:text-3xl font-bold text-white tracking-wider font-display">TẠO YÊU CẦU BẢO HÀNH</h1>
        </div>

        @if(session('error'))
            <div class="bg-accent-red/10 border border-accent-red/30 rounded-xl p-4 mb-6 text-accent-red">{{ session('error') }}</div>
        @endif

        <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6">
            <h3 class="text-white font-semibold mb-2">Đơn hàng #{{ $order->id }}</h3>
            <p class="text-text-secondary text-sm">Giao ngày: {{ $order->delivered_at ? $order->delivered_at->format('d/m/Y') : 'N/A' }}</p>
            <p class="text-text-secondary text-sm">Bảo hành trong 7 ngày kể từ ngày giao hàng.</p>
        </div>

        @if($warrantableDetails->count() > 0)
        <form action="{{ route('warranties.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">

            <div class="bg-bg-secondary border border-border rounded-xl p-6 space-y-6">
                <div>
                    <label for="order_detail_id" class="block text-text-secondary text-sm mb-2">Sản phẩm bảo hành <span class="text-accent-red">*</span></label>
                    <select id="order_detail_id" name="order_detail_id" class="form-input text-sm w-full" required>
                        <option value="">-- Chọn sản phẩm --</option>
                        @foreach($warrantableDetails as $item)
                            <option value="{{ $item['detail']->id }}" data-max="{{ $item['remaining'] }}" {{ (int) old('order_detail_id', $selectedDetailId ?? 0) === (int) $item['detail']->id ? 'selected' : '' }}>
                                {{ $item['detail']->product_name }} (còn bảo hành {{ $item['remaining'] }}/{{ $item['detail']->quantity }})
                            </option>
                        @endforeach
                    </select>
                    @error('order_detail_id') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="quantity" class="block text-text-secondary text-sm mb-2">Số lượng <span class="text-accent-red">*</span></label>
                    <input type="number" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" class="form-input text-sm w-32" required>
                    @error('quantity') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="reason" class="block text-text-secondary text-sm mb-2">Lý do bảo hành <span class="text-accent-red">*</span></label>
                    <input type="text" id="reason" name="reason" value="{{ old('reason') }}" class="form-input text-sm w-full" placeholder="VD: Thiếu runner, biến dạng nhựa, lỗi sản xuất..." required maxlength="500">
                    @error('reason') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="block text-text-secondary text-sm mb-2">Mô tả chi tiết</label>
                    <textarea id="description" name="description" rows="4" class="form-input text-sm w-full" placeholder="Mô tả thêm về vấn đề..." maxlength="2000">{{ old('description') }}</textarea>
                    @error('description') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-text-secondary text-sm mb-2">Ảnh bằng chứng <span class="text-accent-red">*</span> (1-5 ảnh, jpg/png/webp, max 5MB)</label>
                    <input type="file" id="evidence-input" name="evidence[]" multiple accept="image/jpeg,image/png,image/webp" class="form-input text-sm w-full max-w-full overflow-hidden" required>
                    <div id="evidence-preview" class="grid grid-cols-3 sm:grid-cols-5 gap-2 mt-2"></div>
                    <p id="evidence-client-error" class="text-accent-red text-xs mt-1 hidden"></p>
                    @error('evidence') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                    @error('evidence.*') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <script>
                (function() {
                    var input = document.getElementById('evidence-input');
                    var preview = document.getElementById('evidence-preview');
                    var err = document.getElementById('evidence-client-error');
                    if (!input || !preview || !err) return;
                    input.addEventListener('change', function() {
                        preview.innerHTML = '';
                        err.classList.add('hidden');
                        var files = Array.prototype.slice.call(input.files || []);
                        var allowed = ['image/jpeg', 'image/png', 'image/webp'];
                        if (files.length < 1 || files.length > 5) {
                            err.textContent = 'Vui lòng chọn từ 1 đến 5 ảnh.';
                            err.classList.remove('hidden');
                        }
                        files.slice(0, 5).forEach(function(f) {
                            if (allowed.indexOf(f.type) === -1 || f.size > 5 * 1024 * 1024) {
                                err.textContent = 'Chỉ chấp nhận ảnh jpg/png/webp, tối đa 5MB/ảnh.';
                                err.classList.remove('hidden');
                                return;
                            }
                            var img = document.createElement('img');
                            img.src = URL.createObjectURL(f);
                            img.alt = 'Preview';
                            img.className = 'w-full h-24 object-cover rounded-lg border border-border';
                            img.onload = function() { URL.revokeObjectURL(img.src); };
                            preview.appendChild(img);
                        });
                    });
                })();
                </script>

                <div class="flex gap-4">
                    <a href="{{ route('warranties.mine') }}" class="px-6 py-3 border border-border rounded-xl text-text-secondary hover:text-white transition-colors">Hủy</a>
                    <button type="submit" class="px-6 py-3 btn-primary">Gửi yêu cầu bảo hành</button>
                </div>
            </div>
        </form>
        @else
        <div class="bg-bg-secondary border border-border rounded-xl p-6 text-center text-text-secondary">
            Không có sản phẩm nào còn đủ số lượng bảo hành trong đơn hàng này.
        </div>
        @endif
    </div>
</section>
@endsection
