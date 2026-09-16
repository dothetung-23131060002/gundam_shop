@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng #' . $order->id . ' - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.orders.index') }}" aria-label="Quay lại danh sách đơn hàng" class="text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-white font-semibold text-lg">Đơn hàng #{{ $order->id }}</h2>
            </div>
        </header>

        <main class="p-6">
            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Cập nhật trạng thái</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'completed' => 'Đã giao', 'cancelled' => 'Đã hủy'] as $key => $label)
                                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="order_status" value="{{ $key }}">
                                    <button type="submit" onclick="return confirm('Bạn có chắc muốn thay đổi trạng thái đơn hàng?')" class="px-4 py-2 text-sm rounded-lg border transition-colors
                                        {{ $order->order_status === $key ? 'bg-accent-blue text-white border-accent-blue' : 'border-border text-text-secondary hover:text-white hover:border-accent-blue/50' }}">
                                        {{ $label }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Sản phẩm trong đơn</h3>
                        <div class="space-y-4">
                            @foreach($order->details as $detail)
                                <div class="flex gap-4 p-4 bg-bg-primary rounded-xl">
                                    <div class="w-16 h-16 rounded-lg overflow-hidden bg-bg-secondary flex-shrink-0">
                                        @if($detail->product)
                                            <img src="{{ $detail->product->image_url }}" alt="{{ $detail->product_name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-text-secondary text-xs">N/A</div>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-white text-sm font-medium">{{ $detail->product_name }}</p>
                                        <p class="text-text-secondary text-xs">x{{ $detail->quantity }}</p>
                                    </div>
                                    <span class="price-display text-sm">{{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Thông tin khách hàng</h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-text-secondary">Họ tên:</p>
                                <p class="text-white font-medium">{{ $order->customer_name }}</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Số điện thoại:</p>
                                <p class="text-white">{{ $order->customer_phone }}</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Email:</p>
                                <p class="text-white">{{ $order->customer_email ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Địa chỉ:</p>
                                <p class="text-white">{{ $order->shipping_address }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Thanh toán</h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-text-secondary">Phương thức:</p>
                                <p class="text-white">{{ match($order->payment_method) { 'cod' => 'COD', 'qr' => 'Chuyển khoản QR', 'balance' => 'Thanh toán qua đợt gom', 'cash' => 'Tiền mặt (admin thu hộ)', default => $order->payment_method } }}</p>
                            </div>
                            <div>
                                <p class="text-text-secondary">Trạng thái:</p>
                                @php $payStatus = $order->payment_status; @endphp
                                @if($payStatus == 'paid')
                                    <span class="px-3 py-1 bg-green-500/10 border border-green-500/30 rounded-full text-green-400 text-xs">Thanh toán thành công</span>
                                @elseif($payStatus == 'awaiting_confirmation')
                                    <span class="px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 rounded-full text-yellow-400 text-xs animate-pulse">Chờ shop xác nhận</span>
                                @elseif($payStatus == 'payment_rejected')
                                    <span class="px-3 py-1 bg-red-500/10 border border-red-500/30 rounded-full text-red-400 text-xs">Chưa được xác nhận</span>
                                @else
                                    <span class="px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 rounded-full text-yellow-400 text-xs">Chờ thanh toán</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($order->payment_status === 'awaiting_confirmation')
                        <div class="bg-accent-gold/10 border border-accent-gold/30 rounded-xl p-6">
                            <h3 class="text-accent-gold font-semibold mb-2">Chờ xác nhận thanh toán</h3>
                            <p class="text-text-secondary text-sm mb-4">Kiểm tra giao dịch thực tế trên ngân hàng/MoMo (số tiền {{ number_format($order->total_amount, 0, ',', '.') }}đ) rồi xác nhận hoặc từ chối.</p>
                            <div class="flex flex-wrap gap-3">
                                <form action="{{ route('admin.orders.confirm-payment', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Xác nhận đã nhận đủ tiền đơn #{{ $order->id }}?')" class="btn-primary px-6 py-3 text-sm font-medium">XÁC NHẬN ĐÃ NHẬN TIỀN</button>
                                </form>
                                <form action="{{ route('admin.orders.reject-payment', $order) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="text" name="reason" value="{{ old('reason') }}" maxlength="500" placeholder="Lý do từ chối (hiển thị cho khách)" aria-label="Lý do từ chối" class="bg-bg-primary border border-border rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-accent-red min-w-64">
                                    <button type="submit" onclick="return confirm('Từ chối thanh toán đơn #{{ $order->id }}?')" class="px-6 py-3 text-sm font-medium bg-accent-red/10 border border-accent-red/30 rounded-xl text-accent-red hover:bg-accent-red/20 transition-colors">TỪ CHỐI</button>
                                </form>
                            </div>
                            @error('reason') <p class="text-accent-red text-xs mt-2">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if($order->payment_method === 'cod' && $order->payment_status === 'unpaid' && $order->order_status === 'shipping')
                        <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-6">
                            <h3 class="text-green-400 font-semibold mb-2">Xác nhận thu tiền COD</h3>
                            <p class="text-text-secondary text-sm mb-4">Khách đã nhận hàng và thanh toán tiền mặt. Xác nhận để chuyển đơn sang trạng thái "Đã hoàn tất".</p>
                            <form action="{{ route('admin.orders.collect-cod', $order) }}" method="POST">
                                @csrf
                                <button type="submit" onclick="return confirm('Xác nhận đã thu tiền COD đơn #{{ $order->id }}? Đơn sẽ chuyển sang "Đã hoàn tất" và đánh dấu đã thanh toán.')" class="px-6 py-3 text-sm font-medium bg-green-500/20 border border-green-500/30 rounded-xl text-green-400 hover:bg-green-500/30 transition-colors">XÁC NHẬN ĐÃ THU TIỀN COD</button>
                            </form>
                        </div>
                    @endif

                    <div class="bg-bg-secondary border border-border rounded-xl p-6">
                        <h3 class="text-white font-semibold mb-4">Tổng quan</h3>
                        <div class="space-y-3">
                            <div class="border-t border-border pt-3">
                                <div class="flex justify-between">
                                    <span class="text-white font-semibold">Tổng cộng:</span>
                                    <span class="price-display text-xl">{{ number_format($order->total_amount, 0, ',', '.') }} VNĐ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection

