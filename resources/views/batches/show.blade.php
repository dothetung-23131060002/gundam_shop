@extends('layouts.app')

@section('title', $batch->product->name . ' - Đợt gom hàng')

@section('content')

<section class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-5 gap-8">
        <!-- Left: Product + Description -->
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="relative h-72 sm:h-96">
                    <img src="{{ $batch->product->image_url }}" alt="{{ $batch->product->name }}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    @if($batch->isFull())
                        <div class="absolute top-4 left-4">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-green-500/90 text-white font-medium">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Đã đủ ngưỡng
                            </span>
                        </div>
                    @endif
                </div>

                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ $batch->product->name }}</h1>
                            <p class="text-text-secondary text-sm mt-1">{{ $batch->product->category->name ?? '' }} {{ $batch->product->brand ? '• ' . $batch->product->brand->name : '' }}</p>
                        </div>
                        <span class="price-display text-2xl">{{ number_format($batch->product->price, 0, ',', '.') }}đ</span>
                    </div>

                    @if($batch->product->description)
                        <div class="border-t border-border pt-4 mt-4">
                            <h3 class="text-white font-semibold mb-2">Mô tả sản phẩm</h3>
                            <p class="text-text-secondary text-sm leading-relaxed">{!! nl2br(e($batch->product->description)) !!}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Batch Info + Reserve Form -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-bg-secondary border border-border rounded-xl p-6 sticky top-24">
                <h2 class="text-white font-bold text-lg mb-4">Thông tin đợt gom</h2>

                <div class="space-y-4 mb-6">
                    <div class="flex justify-between items-center">
                        <span class="text-text-secondary text-sm">Tiền cọc mỗi slot</span>
                        <span class="text-accent-gold font-bold text-lg font-mono">{{ number_format($batch->deposit_amount, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-text-secondary text-sm">Hạn chót</span>
                        <span class="text-white text-sm">{{ $batch->deadline->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-text-secondary text-sm">Còn lại</span>
                        <span class="text-white text-sm font-mono" id="time-remaining">{{ $timeRemaining }}</span>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex justify-between mb-2">
                        <span class="text-text-secondary text-sm">Tiến độ</span>
                        <span class="text-white font-mono tabular-nums" id="progress-count">{{ $reservedCount }}/{{ $batch->threshold }} slot</span>
                    </div>
                    <div class="w-full h-3 bg-bg-primary rounded-full overflow-hidden" role="progressbar" id="progress-wrap" aria-valuenow="{{ $reservedCount }}" aria-valuemin="0" aria-valuemax="{{ $batch->threshold }}" aria-label="Tiến độ đợt gom: {{ $reservedCount }} trên {{ $batch->threshold }} slot">
                        <div class="h-full rounded-full transition-all duration-500 {{ $batch->isFull() ? 'bg-green-500' : 'bg-accent-gold' }}" id="progress-bar" style="width: {{ min($progressPercent, 100) }}%"></div>
                    </div>
                    <p class="text-text-secondary text-xs mt-1 text-right font-mono tabular-nums" id="progress-percent">{{ $progressPercent }}%</p>
                </div>

                @if($userReservation)
                    <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4 text-center">
                        <svg class="w-8 h-8 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-green-400 font-medium">Bạn đang giữ {{ $userReservation->quantity }} slot</p>
                        <p class="text-text-secondary text-xs mt-1">Đã cọc: {{ number_format($userReservation->deposit_paid, 0, ',', '.') }}đ</p>
                        <a href="{{ route('reservations.show', $userReservation) }}" class="inline-block mt-2 text-accent-blue text-sm hover:underline">Xem chi tiết →</a>
                    </div>
                @elseif($batch->isFull())
                    <div class="bg-accent-gold/10 border border-accent-gold/30 rounded-xl p-4 text-center">
                        <p class="text-accent-gold font-medium">Đã đủ ngưỡng!</p>
                        <p class="text-text-secondary text-xs mt-1">Đợt gom này đã đạt ngưỡng tối thiểu.</p>
                    </div>
                @else
                    @auth
                        <form action="{{ route('reservations.store') }}" method="POST" id="reservation-form">
                            @csrf
                            <input type="hidden" name="batch_id" value="{{ $batch->id }}">

                            <div class="mb-4">
                                <label for="quantity" class="block text-text-secondary text-sm mb-1">Số slot muốn giữ (tối đa 5)</label>
                                <select name="quantity" id="quantity" class="w-full bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-gold font-mono" onchange="updateTotal()">
                                    @for($i = 1; $i <= min(5, $batch->threshold - $reservedCount); $i++)
                                        <option value="{{ $i }}">{{ $i }} slot</option>
                                    @endfor
                                </select>
                                @error('quantity') <p class="text-accent-red text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="bg-bg-primary rounded-lg p-4 mb-4">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-text-secondary">Cọc/slot</span>
                                    <span class="text-white font-mono">{{ number_format($batch->deposit_amount, 0, ',', '.') }}đ</span>
                                </div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-text-secondary">Số slot</span>
                                    <span class="text-white font-mono" id="slot-display">1</span>
                                </div>
                                <div class="border-t border-border mt-2 pt-2 flex justify-between">
                                    <span class="text-white font-medium">Tổng cọc</span>
                                    <span class="text-accent-gold font-bold font-mono" id="total-display">{{ number_format($batch->deposit_amount, 0, ',', '.') }}đ</span>
                                </div>
                            </div>

                            @error('batch_id') <p class="text-accent-red text-xs mb-3">{{ $message }}</p> @enderror

                            <button type="submit" class="w-full btn-primary py-3 text-sm font-medium">GIỮ SLOT NGAY</button>
                        </form>
                    @else
                        <a href="{{ route('login.form') }}" class="block w-full text-center btn-primary py-3 text-sm font-medium">ĐĂNG NHẬP ĐỂ GIỮ SLOT</a>
                    @endauth
                @endif

                <div class="mt-6 space-y-2 text-text-secondary text-xs">
                    <p class="flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-accent-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <span>Sau khi đặt cọc, bạn sẽ nhận thông báo khi đạt ngưỡng.</span>
                    </p>
                    <p class="flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-accent-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <span>Nếu không đạt ngưỡng trước deadline, cọc sẽ được hoàn tự động.</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
function updateTotal() {
    var qty = document.getElementById('quantity').value;
    var deposit = {{ $batch->deposit_amount }};
    var total = qty * deposit;
    document.getElementById('slot-display').textContent = qty;
    document.getElementById('total-display').textContent = total.toLocaleString('vi-VN') + 'đ';
}

// Realtime tiến độ: poll JSON mỗi 10s, dừng khi tab ẩn; batch đóng → chuyển trang.
(function () {
    var wrap = document.getElementById('progress-wrap');
    if (!wrap) return;
    var url = "{{ route('batches.progress', $batch) }}";
    var doneUrl = "{{ auth()->check() ? route('reservations.index') : route('batches.index') }}";
    setInterval(function () {
        if (document.hidden) return;
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.is_open) {
                    window.location.href = doneUrl;
                    return;
                }
                document.getElementById('progress-count').textContent = d.reserved + '/' + d.threshold + ' slot';
                document.getElementById('progress-bar').style.width = Math.min(d.percent, 100) + '%';
                document.getElementById('progress-percent').textContent = d.percent + '%';
                document.getElementById('time-remaining').textContent = d.time_remaining;
                wrap.setAttribute('aria-valuenow', d.reserved);
                wrap.setAttribute('aria-label', 'Tiến độ đợt gom: ' + d.reserved + ' trên ' + d.threshold + ' slot');
            })
            .catch(function () { /* giữ số cũ, thử lại kỳ sau */ });
    }, 10000);
})();
</script>
@endpush

@endsection
