<!-- Dark Futuristic Navbar with Glassmorphism -->
<header class="glass-nav fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            
            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <!-- Mecha Logo Icon -->
                <div class="relative w-10 h-10 lg:w-12 lg:h-12">
                    <div class="absolute inset-0 bg-accent-blue rounded-lg opacity-20 group-hover:opacity-40 transition-opacity"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-6 h-6 lg:w-7 lg:h-7 text-accent-blue" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L19.18 7 12 10.18 4.82 7 12 4.18zM4 8.82l7 3.5v7.36l-7-3.5V8.82zm9 10.86V12.32l7-3.5v7.36l-7 3.5z"/>
                        </svg>
                    </div>
                </div>
                <div class="hidden sm:block">
                    <span class="text-xl lg:text-2xl font-bold tracking-wider text-text-primary font-display">GUNDAM SHOP</span>
                    <span class="block text-[10px] lg:text-xs text-text-secondary tracking-widest">PREMIUM MODEL KITS</span>
                </div>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden lg:flex items-center gap-8">
                <a href="{{ route('home') }}" class="text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                    TRANG CHỦ
                </a>
                <a href="{{ route('products.index') }}" class="text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                    SẢN PHẨM
                </a>
                <a href="{{ route('batches.index') }}" class="text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                    ĐỢT GOM
                </a>
                <a href="{{ route('cart.index') }}" class="relative text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                    GIỎ HÀNG
                    @if(session('cart'))
                        <span class="absolute -top-2 -right-4 bg-accent-red text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center font-bold">
                            {{ count(session('cart')) }}
                        </span>
                    @endif
                </a>
            </nav>

            <!-- Right Section -->
            <div class="flex items-center gap-4">
                
                <!-- Search Toggle (Mobile) -->
                <button aria-label="Tìm kiếm" class="lg:hidden p-2 text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                <!-- Theme Toggle -->
                <button id="theme-toggle" aria-label="Chuyển chế độ sáng/tối" class="p-2 text-text-secondary hover:text-white transition-colors" title="Chế độ sáng/tối">
                    <svg class="w-5 h-5 dark-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg class="w-5 h-5 light-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>

                <!-- Auth Section -->
                @auth
                    <div class="hidden lg:flex items-center gap-4">
                        <span class="text-sm text-text-secondary">
                            Xin chào, <span class="text-text-primary font-medium">{{ auth()->user()->name }}</span>
                        </span>
                        
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="text-xs font-medium text-accent-blue hover:text-accent-blue-dark transition-colors tracking-wide">
                                ADMIN
                            </a>
                        @endif
                        
                        <a href="{{ route('orders.mine') }}" class="text-xs font-medium text-text-secondary hover:text-white transition-colors tracking-wide">
                            ĐƠN HÀNG
                        </a>

                        <a href="{{ route('reservations.index') }}" class="text-xs font-medium text-text-secondary hover:text-white transition-colors tracking-wide">
                            LƯU TRỮ
                        </a>

                        <a href="{{ route('wishlist.index') }}" class="text-xs font-medium text-text-secondary hover:text-white transition-colors tracking-wide">
                            YÊU THÍCH
                        </a>

                        <a href="{{ route('profile.edit') }}" class="text-xs font-medium text-text-secondary hover:text-white transition-colors tracking-wide">
                            TÀI KHOẢN
                        </a>

                        @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                        <a href="{{ route('notifications.index') }}" aria-label="Thông báo{{ $unreadCount > 0 ? ', '.$unreadCount.' chưa đọc' : '' }}" class="relative text-text-secondary hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if($unreadCount > 0)
                                <span class="absolute -top-1.5 -right-1.5 bg-accent-red text-white text-[9px] w-4 h-4 rounded-full flex items-center justify-center font-bold">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                            @endif
                        </a>
                        
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-xs font-medium text-text-secondary hover:text-accent-red transition-colors tracking-wide">
                                ĐĂNG XUẤT
                            </button>
                        </form>
                    </div>
                @else
                    <div class="hidden lg:flex items-center gap-4">
                        <a href="{{ route('login.form') }}" class="text-sm font-medium text-text-secondary hover:text-white transition-colors tracking-wide">
                            ĐĂNG NHẬP
                        </a>
                        <a href="{{ route('register.form') }}" class="btn-primary text-sm px-4 py-2">
                            ĐĂNG KÝ
                        </a>
                    </div>
                @endauth

                <!-- Mobile Menu Toggle -->
                <button id="mobile-menu-toggle" aria-label="Mở menu" aria-expanded="false" aria-controls="mobile-menu" class="lg:hidden p-2 text-text-secondary hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden lg:hidden border-t border-border bg-bg-primary/95 backdrop-blur-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 space-y-3">
            <a href="{{ route('home') }}" class="block py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                TRANG CHỦ
            </a>
            <a href="{{ route('products.index') }}" class="block py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                SẢN PHẨM
            </a>
            <a href="{{ route('batches.index') }}" class="block py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                ĐỢT GOM
            </a>
            <a href="{{ route('cart.index') }}" class="block py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                GIỎ HÀNG
                @if(session('cart'))
                    <span class="ml-2 bg-accent-red text-white text-[10px] px-2 py-0.5 rounded-full">{{ count(session('cart')) }}</span>
                @endif
            </a>
            
            <!-- Theme Toggle (Mobile) -->
            <button onclick="document.getElementById('theme-toggle').click()" class="flex items-center gap-3 py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                CHẾ ĐỘ SÁNG/TỐI
            </button>
            
            <div class="border-t border-border pt-3 mt-3">
                @auth
                    <div class="space-y-3">
                        <p class="text-sm text-text-secondary">
                            Xin chào, <span class="text-text-primary font-medium">{{ auth()->user()->name }}</span>
                        </p>
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="block py-2 text-accent-blue text-sm font-medium tracking-wide">
                                QUẢN TRỊ
                            </a>
                        @endif
                        <a href="{{ route('orders.mine') }}" class="block py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                            ĐƠN HÀNG CỦA TÔI
                        </a>
                        <a href="{{ route('reservations.index') }}" class="block py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                            LƯU TRỮ
                        </a>
                        <a href="{{ route('wishlist.index') }}" class="block py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                            YÊU THÍCH
                        </a>
                        <a href="{{ route('notifications.index') }}" class="block py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                            THÔNG BÁO @if(isset($unreadCount) && $unreadCount > 0)<span class="ml-1 bg-accent-red text-white text-[9px] px-1.5 py-0.5 rounded-full">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
                        </a>
                        <a href="{{ route('profile.edit') }}" class="block py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                            TÀI KHOẢN
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="py-2 text-text-secondary hover:text-accent-red transition-colors text-sm font-medium tracking-wide">
                                ĐĂNG XUẤT
                            </button>
                        </form>
                    </div>
                @else
                    <div class="space-y-3">
                        <a href="{{ route('login.form') }}" class="block py-2 text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide">
                            ĐĂNG NHẬP
                        </a>
                        <a href="{{ route('register.form') }}" class="block btn-primary text-center text-sm">
                            ĐĂNG KÝ
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</header>

<!-- Spacer for fixed navbar -->
<div class="h-16 lg:h-20"></div>

@push('scripts')
<script>
    const toggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('mobile-menu');
    
    toggle.addEventListener('click', () => {
        const open = menu.classList.toggle('hidden');
        toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
        toggle.setAttribute('aria-label', open ? 'Mở menu' : 'Đóng menu');
    });
</script>
@endpush
