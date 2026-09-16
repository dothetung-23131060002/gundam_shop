@php
    $options = $paymentOptions ?? ['amount' => 0, 'addInfo' => '', 'amount_valid' => false, 'has_methods' => false, 'default' => null, 'methods' => []];
    $canPay = ($options['has_methods'] ?? false) && ($options['amount_valid'] ?? false);
    $defaultKey = $options['default'] ?? null;
@endphp

<div data-payment-methods class="text-left">
    @if(! $canPay)
        <div class="bg-accent-red/10 border border-accent-red/30 rounded-xl p-4 text-sm" role="alert">
            @if(! ($options['amount_valid'] ?? false))
                <p class="text-accent-red font-medium">Số tiền thanh toán không hợp lệ.</p>
                <p class="text-text-secondary text-xs mt-1">Vui lòng kiểm tra lại đơn hàng/giữ slot.</p>
            @else
                <p class="text-accent-red font-medium">Chưa cấu hình phương thức thanh toán.</p>
                <p class="text-text-secondary text-xs mt-1">Vui lòng liên hệ shop để được hỗ trợ.</p>
            @endif
        </div>
    @else
        <div class="flex gap-2 overflow-x-auto pb-1 mb-4" role="tablist" aria-label="Phương thức thanh toán">
            @foreach($options['methods'] as $key => $method)
                <button
                    type="button"
                    role="tab"
                    data-method-tab="{{ $key }}"
                    aria-selected="{{ $key === $defaultKey ? 'true' : 'false' }}"
                    aria-controls="pm-panel-{{ $key }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors {{ $key === $defaultKey ? 'bg-accent-blue text-white' : 'bg-bg-primary text-text-secondary border border-border hover:text-white' }}"
                >{{ $method['label'] }}</button>
            @endforeach
        </div>

        @foreach($options['methods'] as $key => $method)
            <div
                id="pm-panel-{{ $key }}"
                role="tabpanel"
                data-method-panel="{{ $key }}"
                @if($key !== $defaultKey) hidden @endif
                class="bg-bg-primary border border-border rounded-xl p-5"
            >
                @if($method['type'] === 'bank')
                    <div class="w-48 h-48 mx-auto bg-white rounded-xl flex items-center justify-center mb-4 overflow-hidden">
                        <img
                            src="{{ $method['qr_url'] }}"
                            alt="QR {{ $method['label'] }} - {{ $options['addInfo'] }}"
                            loading="lazy"
                            class="w-48 h-48 object-contain"
                            onerror="this.style.display='none';var f=this.parentElement.querySelector('[data-qr-fallback]');if(f)f.style.display='block';"
                        >
                        <div data-qr-fallback style="display:none" class="p-4 text-center">
                            <p class="text-bg-primary text-sm font-medium">Không tải được QR.</p>
                            <a href="{{ $method['qr_url'] }}" target="_blank" rel="noopener" class="text-accent-blue text-sm underline">Mở QR trực tiếp</a>
                        </div>
                    </div>
                    <p class="text-text-secondary text-xs text-center mb-4">Quét mã QR bằng ứng dụng ngân hàng để thanh toán</p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-secondary">Ngân hàng:</dt>
                            <dd class="text-white font-medium">{{ $method['label'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-secondary">Số tài khoản:</dt>
                            <dd class="text-white font-mono">{{ $method['account_no'] }}</dd>
                        </div>
                        @if(! empty($method['account_name']))
                            <div class="flex justify-between gap-4">
                                <dt class="text-text-secondary">Chủ tài khoản:</dt>
                                <dd class="text-white font-medium">{{ $method['account_name'] }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-secondary">Số tiền:</dt>
                            <dd class="text-white font-mono font-bold">{{ number_format($options['amount'], 0, ',', '.') }}đ</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-secondary">Nội dung:</dt>
                            <dd class="text-accent-gold font-mono">{{ $options['addInfo'] }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-white font-semibold mb-3">MoMo</p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-secondary">Chuyển MoMo tới:</dt>
                            <dd class="text-white font-mono font-bold">{{ $method['account_no'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-secondary">Số tiền:</dt>
                            <dd class="text-white font-mono font-bold">{{ number_format($options['amount'], 0, ',', '.') }}đ</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-secondary">Nội dung:</dt>
                            <dd class="text-accent-gold font-mono">{{ $options['addInfo'] }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 bg-bg-secondary border border-border rounded-lg p-3 text-sm text-text-secondary">
                        <p>Vui lòng mở ứng dụng MoMo và thực hiện chuyển tiền theo thông tin trên.</p>
                    </div>
                @endif
            </div>
        @endforeach

        <script>
            (function () {
                var root = document.currentScript ? document.currentScript.parentElement : document;
                if (!root || !root.hasAttribute || !root.hasAttribute('data-payment-methods')) {
                    root = document.querySelector('[data-payment-methods]');
                }
                if (!root) return;
                var tabs = root.querySelectorAll('[data-method-tab]');
                var panels = root.querySelectorAll('[data-method-panel]');
                function activate(key) {
                    tabs.forEach(function (tab) {
                        var active = tab.getAttribute('data-method-tab') === key;
                        tab.setAttribute('aria-selected', active ? 'true' : 'false');
                        tab.className = 'px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ' + (active
                            ? 'bg-accent-blue text-white'
                            : 'bg-bg-primary text-text-secondary border border-border hover:text-white');
                    });
                    panels.forEach(function (panel) {
                        panel.hidden = panel.getAttribute('data-method-panel') !== key;
                    });
                }
                tabs.forEach(function (tab) {
                    tab.addEventListener('click', function () {
                        activate(tab.getAttribute('data-method-tab'));
                    });
                });
            })();
        </script>
    @endif
</div>
