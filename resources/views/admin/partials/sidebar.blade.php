<!-- Admin Sidebar -->
<aside id="admin-sidebar" class="fixed inset-y-0 left-0 w-64 bg-bg-secondary border-r border-border z-50 transform -translate-x-full lg:translate-x-0 transition-transform">
    <div class="h-16 flex items-center justify-center border-b border-border">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-accent-blue rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            <span class="text-lg font-bold text-white tracking-wider font-display">GUNDAM</span>
        </a>
    </div>

    <nav class="p-4 space-y-2" aria-label="Menu quản trị">
        <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="font-medium">Dashboard</span>
        </a>

        <div class="pt-4">
            <p class="px-4 mb-2 text-xs font-semibold text-text-secondary uppercase tracking-wider">Quản lý</p>
            
            <a href="{{ route('admin.products.index') }}" @if(request()->routeIs('admin.products.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.products.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span class="font-medium">Sản phẩm</span>
            </a>

            <a href="{{ route('admin.batches.index') }}" @if(request()->routeIs('admin.batches.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.batches.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span class="font-medium">Đợt gom</span>
            </a>

            <a href="{{ route('admin.reservations.index') }}" @if(request()->routeIs('admin.reservations.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.reservations.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                <span class="font-medium">Giữ slot</span>
            </a>

            <a href="{{ route('admin.refunds.index') }}" @if(request()->routeIs('admin.refunds.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.refunds.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span class="font-medium">Hoàn cọc</span>
            </a>

            <a href="{{ route('admin.returns.index') }}" @if(request()->routeIs('admin.returns.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.returns.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                <span class="font-medium">Trả hàng</span>
            </a>

            <a href="{{ route('admin.action-logs.index') }}" @if(request()->routeIs('admin.action-logs.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.action-logs.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span class="font-medium">Nhật ký</span>
            </a>

            <a href="{{ route('admin.settings.edit') }}" @if(request()->routeIs('admin.settings.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="font-medium">Cài đặt</span>
            </a>

            <a href="{{ route('admin.categories.index') }}" @if(request()->routeIs('admin.categories.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.categories.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span class="font-medium">Danh mục</span>
            </a>

            <a href="{{ route('admin.orders.index') }}" @if(request()->routeIs('admin.orders.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.orders.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span class="font-medium">Đơn hàng</span>
            </a>

            <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span class="font-medium">Người dùng</span>
            </a>

            <a href="{{ route('admin.reviews.index') }}" @if(request()->routeIs('admin.reviews.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.reviews.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                <span class="font-medium">Đánh giá</span>
            </a>

            <a href="{{ route('admin.warranties.index') }}" @if(request()->routeIs('admin.warranties.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ request()->routeIs('admin.warranties.*') ? 'bg-accent-blue/10 text-accent-blue border border-accent-blue/30' : 'text-text-secondary hover:text-white hover:bg-bg-primary' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span class="font-medium">Bảo hành</span>
            </a>
        </div>
    </nav>
</aside>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

<script>
    function toggleSidebar() {
        var sidebar = document.getElementById('admin-sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        if (sidebar.classList.contains('-translate-x-full')) {
            overlay.classList.add('hidden');
        } else {
            overlay.classList.remove('hidden');
        }
    }
</script>
