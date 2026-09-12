@extends('layouts.app')

@section('title', 'Đăng ký - Gundam Shop')

@section('content')

<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto">
            
            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-accent-blue rounded-lg flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white tracking-wider font-display">GUNDAM SHOP</span>
                </a>
                <h1 class="text-2xl font-bold text-white mb-2">Tạo tài khoản mới</h1>
                <p class="text-text-secondary">Tham gia cộng đồng Gundam</p>
            </div>

            <!-- Register Form -->
            <div class="bg-bg-secondary border border-border rounded-xl p-6 lg:p-8">
                @if(session('error'))
                    <div class="mb-4 p-4 bg-accent-red/10 border border-accent-red/30 rounded-lg">
                        <p class="text-accent-red text-sm">{{ session('error') }}</p>
                    </div>
                @endif

                <form action="{{ route('register') }}" method="POST">
                    @csrf
                    
                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="reg-name" class="block text-text-secondary text-sm mb-2">Họ và tên <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <div class="relative">
                                <input type="text" 
                                       id="reg-name"
                                       name="name" 
                                       value="{{ old('name') }}"
                                       required
                                       autocomplete="name"
                                       class="form-input pl-10"
                                       placeholder="Nguyễn Văn A">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            @error('name')
                                <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="reg-email" class="block text-text-secondary text-sm mb-2">Email <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <div class="relative">
                                <input type="email" 
                                       id="reg-email"
                                       name="email" 
                                       value="{{ old('email') }}"
                                       required
                                       autocomplete="email"
                                       class="form-input pl-10"
                                       placeholder="email@example.com">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </div>
                            @error('email')
                                <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="reg-phone" class="block text-text-secondary text-sm mb-2">Số điện thoại</label>
                            <div class="relative">
                                <input type="tel" 
                                       id="reg-phone"
                                       name="phone" 
                                       value="{{ old('phone') }}"
                                       autocomplete="tel"
                                       class="form-input pl-10"
                                       placeholder="0912345678">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            @error('phone')
                                <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="reg-password" class="block text-text-secondary text-sm mb-2">Mật khẩu <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <div class="relative">
                                <input type="password" 
                                       id="reg-password"
                                       name="password" 
                                       required
                                       autocomplete="new-password"
                                       class="form-input pl-10"
                                       placeholder="Tối thiểu 6 ký tự">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            @error('password')
                                <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="reg-password-confirm" class="block text-text-secondary text-sm mb-2">Xác nhận mật khẩu <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <div class="relative">
                                <input type="password" 
                                       id="reg-password-confirm"
                                       name="password_confirmation" 
                                       required
                                       autocomplete="new-password"
                                       class="form-input pl-10"
                                       placeholder="Nhập lại mật khẩu">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="flex items-start gap-3">
                            <input type="checkbox" id="terms" name="terms" required aria-label="Đồng ý điều khoản sử dụng và chính sách bảo mật" class="w-4 h-4 mt-0.5 accent-accent-blue rounded">
                            <span class="text-text-secondary text-sm">
                                Tôi đồng ý với 
                                <a href="#" class="text-accent-blue hover:underline">Điều khoản sử dụng</a> 
                                và 
                                <a href="#" class="text-accent-blue hover:underline">Chính sách bảo mật</a>
                            </span>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="w-full btn-primary py-4 text-base tracking-wide">
                            TẠO TÀI KHOẢN
                        </button>
                    </div>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-border"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-bg-secondary text-text-secondary">hoặc</span>
                    </div>
                </div>

                <!-- Social Register -->
                <div class="space-y-3">
                    <button class="w-full flex items-center justify-center gap-3 py-3 bg-bg-primary border border-border rounded-xl text-white hover:border-accent-blue/50 transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span>Đăng ký với Google</span>
                    </button>
                </div>
            </div>

            <!-- Login Link -->
            <p class="text-center mt-6 text-text-secondary">
                Đã có tài khoản? 
                <a href="{{ route('login.form') }}" class="text-accent-blue hover:underline font-medium">Đăng nhập</a>
            </p>
        </div>
    </div>
</section>

@endsection
