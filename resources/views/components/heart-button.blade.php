@props(['product', 'isWishlisted' => false, 'showCount' => true, 'wishlistCount' => null])

@php
    $count = $wishlistCount ?? $product->wishlists_count ?? null;
@endphp

<span class="inline-flex items-center gap-2" data-wishlist-wrap>
    <button type="button"
        data-wishlist-toggle
        data-product-id="{{ $product->id }}"
        data-toggle-url="{{ route('wishlist.toggle', $product) }}"
        data-authed="{{ auth()->check() ? '1' : '0' }}"
        data-login-url="{{ route('login.form') }}"
        data-wishlisted="{{ $isWishlisted ? '1' : '0' }}"
        aria-pressed="{{ $isWishlisted ? 'true' : 'false' }}"
        aria-label="{{ $isWishlisted ? 'Xóa khỏi danh sách yêu thích' : 'Thêm vào danh sách yêu thích' }}"
        class="p-3 rounded-lg border transition-colors {{ $isWishlisted ? 'border-accent-red/50 text-accent-red bg-accent-red/10' : 'border-border text-text-secondary hover:text-accent-red hover:border-accent-red/50' }}">
        <svg data-wishlist-icon class="w-5 h-5" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
    </button>
    @if($showCount)
        <span data-wishlist-count class="text-text-secondary text-sm">{{ $count !== null ? $count : '' }}</span>
    @endif
</span>

@once
@push('scripts')
<script>
(function() {
    if (window.__wishlistBound) return;
    window.__wishlistBound = true;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function showToast(message, loginUrl) {
        var old = document.getElementById('wishlist-toast');
        if (old) old.remove();
        var toast = document.createElement('div');
        toast.id = 'wishlist-toast';
        toast.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-[100] bg-bg-secondary border border-border rounded-xl px-5 py-4 text-sm text-white shadow-xl flex items-center gap-4 max-w-[90vw]';
        var span = document.createElement('span');
        span.textContent = message;
        toast.appendChild(span);
        if (loginUrl) {
            var link = document.createElement('a');
            link.href = loginUrl;
            link.textContent = 'Đăng nhập';
            link.className = 'text-accent-blue hover:text-white whitespace-nowrap font-medium';
            toast.appendChild(link);
        }
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 4000);
    }

    function paint(btn, wishlisted) {
        var icon = btn.querySelector('[data-wishlist-icon]');
        btn.dataset.wishlisted = wishlisted ? '1' : '0';
        btn.setAttribute('aria-pressed', wishlisted ? 'true' : 'false');
        btn.setAttribute('aria-label', wishlisted ? 'Xóa khỏi danh sách yêu thích' : 'Thêm vào danh sách yêu thích');
        if (icon) icon.setAttribute('fill', wishlisted ? 'currentColor' : 'none');
        btn.classList.toggle('border-accent-red/50', wishlisted);
        btn.classList.toggle('text-accent-red', wishlisted);
        btn.classList.toggle('bg-accent-red/10', wishlisted);
        btn.classList.toggle('border-border', !wishlisted);
        btn.classList.toggle('text-text-secondary', !wishlisted);
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-wishlist-toggle]');
        if (!btn) return;
        e.preventDefault();

        if (btn.dataset.authed !== '1') {
            showToast('Vui lòng đăng nhập để thêm sản phẩm vào danh sách yêu thích.', btn.dataset.loginUrl);
            return;
        }

        var wasWishlisted = btn.dataset.wishlisted === '1';
        paint(btn, !wasWishlisted); // optimistic update

        fetch(btn.dataset.toggleUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
        }).then(function(res) {
            if (res.status === 401) {
                paint(btn, wasWishlisted);
                showToast('Vui lòng đăng nhập để thêm sản phẩm vào danh sách yêu thích.', btn.dataset.loginUrl);
                return null;
            }
            if (!res.ok) {
                paint(btn, wasWishlisted);
                showToast('Không thể cập nhật danh sách yêu thích. Vui lòng thử lại.');
                return null;
            }
            return res.json();
        }).then(function(data) {
            if (!data) return;
            var nowWishlisted = data.status === 'added';
            paint(btn, nowWishlisted);
            var wrap = btn.closest('[data-wishlist-wrap]');
            var countEl = wrap ? wrap.querySelector('[data-wishlist-count]') : null;
            if (countEl && typeof data.wishlist_count !== 'undefined') {
                countEl.textContent = data.wishlist_count;
            }
        }).catch(function() {
            paint(btn, wasWishlisted); // rollback on network error
            showToast('Mất kết nối. Vui lòng thử lại.');
        });
    });
})();
</script>
@endpush
@endonce
