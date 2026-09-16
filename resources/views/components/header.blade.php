<!-- Dark Futuristic Navbar with Glassmorphism + CSS-only workshop backdrop -->
@php
    $navItems = [
        ['route' => 'home', 'pattern' => 'home', 'label' => 'TRANG CHỦ', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'products.index', 'pattern' => 'products.*', 'label' => 'SẢN PHẨM', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
        ['route' => 'batches.index', 'pattern' => 'batches.*', 'label' => 'ĐỢT GOM', 'icon' => 'M21 8l-9-5-9 5v8l9 5 9-5V8zM3.3 8.3L12 13l8.7-4.7M12 13v9'],
        ['route' => 'cart.index', 'pattern' => 'cart.*', 'label' => 'GIỎ HÀNG', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.6.6-.2 1.7.7 1.7H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
    ];
    $reservationCount = auth()->check() ? auth()->user()->reservations()->where('status', 'reserved')->count() : 0;
    $wishlistCount = auth()->check() ? auth()->user()->wishlists()->count() : 0;
@endphp
<header class="glass-nav navbar-workshop fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">

            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 group flex-shrink-0">
                <div class="relative w-10 h-10 lg:w-12 lg:h-12">
                    <div class="absolute inset-0 bg-accent-blue rounded-lg opacity-20 group-hover:opacity-40 transition-opacity"></div>
                    <div class="absolute inset-0 flex items-center justify-center drop-shadow-[0_0_10px_rgba(30,136,229,0.55)]">
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
            <nav class="hidden lg:flex items-center gap-7 xl:gap-8">
                @foreach($navItems as $item)
                    @php $active = request()->routeIs($item['pattern']); @endphp
                    <a href="{{ route($item['route']) }}" @if($active)aria-current="page"@endif
                       class="relative flex items-center gap-1.5 py-2 min-h-[44px] text-sm font-medium tracking-wide transition-colors {{ $active ? 'text-white' : 'text-text-secondary hover:text-white' }}">
                        <svg class="w-[18px] h-[18px] {{ $active ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                        @if($item['route'] === 'cart.index' && session('cart'))
                            <span class="bg-accent-red text-white text-[9px] min-w-4 h-4 px-1 rounded-full inline-flex items-center justify-center font-bold">{{ count(session('cart')) > 9 ? '9+' : count(session('cart')) }}</span>
                        @endif
                        @if($active)
                            <span class="absolute -bottom-1 left-0 right-0 h-0.5 bg-accent-blue rounded-full shadow-[0_0_8px_rgba(30,136,229,0.8)]"></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <!-- Right Section -->
            <div class="flex items-center gap-3 lg:gap-4">

                <!-- Search Toggle (Mobile) -->
                <button aria-label="Tìm kiếm" class="lg:hidden p-2.5 min-h-[44px] min-w-[44px] inline-flex items-center justify-center cursor-pointer text-text-secondary hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                <!-- Theme Toggle -->
                <button id="theme-toggle" aria-label="Chuyển chế độ sáng/tối" class="p-2.5 min-h-[44px] min-w-[44px] inline-flex items-center justify-center cursor-pointer text-text-secondary hover:text-white transition-colors" title="Chế độ sáng/tối">
                    <svg class="w-5 h-5 dark-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg class="w-5 h-5 light-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>

                <!-- Auth Section -->
                @auth
                    <div class="hidden lg:flex items-center gap-3 xl:gap-4">
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="text-xs font-medium text-accent-blue hover:text-accent-blue-dark transition-colors tracking-wide">
                                ADMIN
                            </a>
                        @endif

                        <!-- Notifications -->
                        @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                        <a href="{{ route('notifications.index') }}" @if(request()->routeIs('notifications.*'))aria-current="page"@endif aria-label="Thông báo{{ $unreadCount > 0 ? ', '.$unreadCount.' chưa đọc' : '' }}" class="relative p-1.5 min-h-[44px] min-w-[44px] inline-flex items-center justify-center text-text-secondary hover:text-white transition-colors {{ request()->routeIs('notifications.*') ? 'text-white' : '' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('notifications.*') ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if($unreadCount > 0)
                                <span class="absolute -top-1 -right-1 bg-accent-red text-white text-[9px] min-w-4 h-4 px-1 rounded-full flex items-center justify-center font-bold">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                            @endif
                        </a>

                        <!-- Avatar Dropdown -->
                        <div class="relative" id="avatar-dropdown-wrapper">
                            <button id="avatar-dropdown-toggle" aria-label="Menu tài khoản" aria-expanded="false" aria-haspopup="true"
                                    class="flex items-center gap-2 py-1.5 min-h-[44px] cursor-pointer rounded-lg transition-colors hover:bg-white/5">
                                <span class="w-8 h-8 rounded-full bg-accent-blue/20 border border-accent-blue/40 flex items-center justify-center text-accent-blue text-sm font-bold flex-shrink-0" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                <span class="text-sm text-text-secondary whitespace-nowrap truncate max-w-[120px] hidden xl:inline">
                                    {{ auth()->user()->name }}
                                </span>
                                <svg class="w-4 h-4 text-text-secondary transition-transform" id="avatar-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="avatar-dropdown-menu" class="hidden absolute right-0 top-full mt-2 w-56 bg-bg-primary/95 backdrop-blur-lg border border-white/10 rounded-xl shadow-2xl shadow-black/40 py-2 z-50" role="menu" aria-orientation="vertical">
                                <div class="px-4 py-2.5 border-b border-white/10">
                                    <p class="text-sm text-text-secondary">Xin chào,</p>
                                    <p class="text-sm font-medium text-text-primary truncate">{{ auth()->user()->name }}</p>
                                </div>

                                <a href="{{ route('profile.edit') }}" role="menuitem"
                                   class="flex items-center gap-3 px-4 py-2.5 min-h-[40px] text-sm text-text-secondary hover:text-white hover:bg-white/5 transition-colors {{ request()->routeIs('profile.*') ? 'text-white bg-white/5' : '' }}">
                                    <svg class="w-4 h-4 {{ request()->routeIs('profile.*') ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Tài khoản
                                </a>

                                <a href="{{ route('orders.mine') }}" role="menuitem"
                                   class="flex items-center gap-3 px-4 py-2.5 min-h-[40px] text-sm text-text-secondary hover:text-white hover:bg-white/5 transition-colors {{ request()->routeIs('orders.*') ? 'text-white bg-white/5' : '' }}">
                                    <svg class="w-4 h-4 {{ request()->routeIs('orders.*') ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Đơn hàng của tôi
                                </a>

                                <a href="{{ route('reservations.index') }}" role="menuitem"
                                   class="flex items-center gap-3 px-4 py-2.5 min-h-[40px] text-sm text-text-secondary hover:text-white hover:bg-white/5 transition-colors {{ request()->routeIs('reservations.*') ? 'text-white bg-white/5' : '' }}">
                                    <svg class="w-4 h-4 {{ request()->routeIs('reservations.*') ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                    </svg>
                                    Slot đang giữ
                                    @if($reservationCount > 0)
                                        <span class="ml-auto bg-accent-blue/20 text-accent-blue text-[9px] min-w-4 h-4 px-1 rounded-full inline-flex items-center justify-center font-bold">{{ $reservationCount > 9 ? '9+' : $reservationCount }}</span>
                                    @endif
                                </a>

                                <a href="{{ route('wishlist.index') }}" role="menuitem"
                                   class="flex items-center gap-3 px-4 py-2.5 min-h-[40px] text-sm text-text-secondary hover:text-white hover:bg-white/5 transition-colors {{ request()->routeIs('wishlist.*') ? 'text-white bg-white/5' : '' }}">
                                    <svg class="w-4 h-4 {{ request()->routeIs('wishlist.*') ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.3 6.3a4.5 4.5 0 000 6.4L12 20.4l7.7-7.7a4.5 4.5 0 00-6.4-6.4L12 7.6l-1.3-1.3a4.5 4.5 0 00-6.4 0z"/>
                                    </svg>
                                    Yêu thích
                                    @if($wishlistCount > 0)
                                        <span class="ml-auto bg-accent-red text-white text-[9px] min-w-4 h-4 px-1 rounded-full inline-flex items-center justify-center font-bold">{{ $wishlistCount > 9 ? '9+' : $wishlistCount }}</span>
                                    @endif
                                </a>

                                <div class="border-t border-white/10 mt-1 pt-1">
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" role="menuitem"
                                                class="flex items-center gap-3 px-4 py-2.5 min-h-[40px] w-full text-sm text-text-secondary hover:text-accent-red hover:bg-white/5 transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                            Đăng xuất
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="hidden lg:flex items-center gap-4">
                        <a href="{{ route('login.form') }}" class="inline-flex items-center min-h-[44px] text-sm font-medium text-text-secondary hover:text-white transition-colors tracking-wide">
                            ĐĂNG NHẬP
                        </a>
                        <a href="{{ route('register.form') }}" class="btn-primary text-sm px-4 py-2">
                            ĐĂNG KÝ
                        </a>
                    </div>
                @endauth

                <!-- Mobile Menu Toggle -->
                <button id="mobile-menu-toggle" aria-label="Mở menu" aria-expanded="false" aria-controls="mobile-menu" class="lg:hidden p-2.5 min-h-[44px] min-w-[44px] inline-flex items-center justify-center cursor-pointer text-text-secondary hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden lg:hidden border-t border-white/10 bg-bg-primary/95 backdrop-blur-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 space-y-1">
            @foreach($navItems as $item)
                @php $active = request()->routeIs($item['pattern']); @endphp
                <a href="{{ route($item['route']) }}" @if($active)aria-current="page"@endif class="flex items-center gap-3 py-3 min-h-[44px] text-sm font-medium tracking-wide transition-colors {{ $active ? 'text-white' : 'text-text-secondary hover:text-white' }}">
                    <svg class="w-5 h-5 {{ $active ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                    </svg>
                    {{ $item['label'] }}
                    @if($item['route'] === 'cart.index' && session('cart'))
                        <span class="ml-auto bg-accent-red text-white text-[10px] px-2 py-0.5 rounded-full">{{ count(session('cart')) }}</span>
                    @endif
                    @if($active)
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-accent-blue shadow-[0_0_8px_rgba(30,136,229,0.8)]"></span>
                    @endif
                </a>
            @endforeach

            <!-- Theme Toggle (Mobile) -->
            <button onclick="document.getElementById('theme-toggle').click()" class="flex items-center gap-3 py-3 min-h-[44px] cursor-pointer text-text-secondary hover:text-white transition-colors text-sm font-medium tracking-wide w-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                CHẾ ĐỘ SÁNG/TỐI
            </button>

            <div class="border-t border-white/10 pt-3 mt-3">
                @auth
                    <div class="space-y-1">
                        <div class="flex items-center gap-3 py-2">
                            <span class="w-9 h-9 rounded-full bg-accent-blue/20 border border-accent-blue/40 flex items-center justify-center text-accent-blue font-bold flex-shrink-0" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <p class="text-sm text-text-secondary truncate">
                                Xin chào, <span class="text-text-primary font-medium">{{ auth()->user()->name }}</span>
                            </p>
                        </div>
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center py-3 min-h-[44px] text-accent-blue text-sm font-medium tracking-wide">
                                QUẢN TRỊ
                            </a>
                        @endif

                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 py-3 min-h-[44px] text-sm font-medium tracking-wide transition-colors {{ request()->routeIs('profile.*') ? 'text-white' : 'text-text-secondary hover:text-white' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('profile.*') ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            TÀI KHOẢN
                        </a>

                        <a href="{{ route('orders.mine') }}" class="flex items-center gap-3 py-3 min-h-[44px] text-sm font-medium tracking-wide transition-colors {{ request()->routeIs('orders.*') ? 'text-white' : 'text-text-secondary hover:text-white' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('orders.*') ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            ĐƠN HÀNG CỦA TÔI
                        </a>

                        <a href="{{ route('reservations.index') }}" class="flex items-center gap-3 py-3 min-h-[44px] text-sm font-medium tracking-wide transition-colors {{ request()->routeIs('reservations.*') ? 'text-white' : 'text-text-secondary hover:text-white' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('reservations.*') ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                            SLOT ĐANG GIỮ
                            @if($reservationCount > 0)
                                <span class="ml-auto bg-accent-blue/20 text-accent-blue text-[10px] px-1.5 py-0.5 rounded-full">{{ $reservationCount > 9 ? '9+' : $reservationCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('wishlist.index') }}" class="flex items-center gap-3 py-3 min-h-[44px] text-sm font-medium tracking-wide transition-colors {{ request()->routeIs('wishlist.*') ? 'text-white' : 'text-text-secondary hover:text-white' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('wishlist.*') ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.3 6.3a4.5 4.5 0 000 6.4L12 20.4l7.7-7.7a4.5 4.5 0 00-6.4-6.4L12 7.6l-1.3-1.3a4.5 4.5 0 00-6.4 0z"/>
                            </svg>
                            YÊU THÍCH
                            @if($wishlistCount > 0)
                                <span class="ml-auto bg-accent-red text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $wishlistCount > 9 ? '9+' : $wishlistCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 py-3 min-h-[44px] text-sm font-medium tracking-wide transition-colors {{ request()->routeIs('notifications.*') ? 'text-white' : 'text-text-secondary hover:text-white' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('notifications.*') ? 'text-accent-blue' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            THÔNG BÁO
                            @if($unreadCount > 0)<span class="ml-auto bg-accent-red text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
                        </a>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="py-3 min-h-[44px] cursor-pointer text-text-secondary hover:text-accent-red transition-colors text-sm font-medium tracking-wide">
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
    // Mobile menu toggle
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileToggle.addEventListener('click', () => {
        const open = mobileMenu.classList.toggle('hidden');
        mobileToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
        mobileToggle.setAttribute('aria-label', open ? 'Mở menu' : 'Đóng menu');
    });

    // Avatar dropdown toggle (desktop)
    const avatarToggle = document.getElementById('avatar-dropdown-toggle');
    const avatarMenu = document.getElementById('avatar-dropdown-menu');
    const avatarChevron = document.getElementById('avatar-chevron');

    if (avatarToggle && avatarMenu) {
        avatarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !avatarMenu.classList.contains('hidden');
            avatarMenu.classList.toggle('hidden');
            avatarToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            if (avatarChevron) avatarChevron.classList.toggle('rotate-180', !isOpen);
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById('avatar-dropdown-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                avatarMenu.classList.add('hidden');
                avatarToggle.setAttribute('aria-expanded', 'false');
                if (avatarChevron) avatarChevron.classList.remove('rotate-180');
            }
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !avatarMenu.classList.contains('hidden')) {
                avatarMenu.classList.add('hidden');
                avatarToggle.setAttribute('aria-expanded', 'false');
                if (avatarChevron) avatarChevron.classList.remove('rotate-180');
                avatarToggle.focus();
            }
        });
    }
</script>
@endpush
