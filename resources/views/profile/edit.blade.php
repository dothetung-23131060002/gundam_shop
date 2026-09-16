@extends('layouts.app')

@section('title', 'Thông tin cá nhân - Gundam Shop')

@section('content')

<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">THÔNG TIN CÁ NHÂN</h1>
        </div>
    </div>
</section>

<section class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-bg-secondary border border-border rounded-xl p-6 lg:p-8">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-500/10 border border-green-500/30 rounded-lg">
                        <p class="text-green-400 text-sm">{{ session('success') }}</p>
                    </div>
                @endif

                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <label for="profile-name" class="block text-text-secondary text-sm mb-2">Họ và tên <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <input type="text"
                                   id="profile-name"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   required
                                   autocomplete="name"
                                   class="form-input"
                                   placeholder="Nguyễn Văn A">
                            @error('name')
                                <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="profile-email" class="block text-text-secondary text-sm mb-2">Email <span class="text-accent-red" aria-hidden="true">*</span></label>
                            <input type="email"
                                   id="profile-email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   required
                                   autocomplete="email"
                                   class="form-input"
                                   placeholder="email@example.com">
                            @error('email')
                                <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="profile-phone" class="block text-text-secondary text-sm mb-2">Số điện thoại</label>
                            <input type="tel"
                                   id="profile-phone"
                                   name="phone"
                                   value="{{ old('phone', $user->phone) }}"
                                   autocomplete="tel"
                                   class="form-input"
                                   placeholder="0912345678">
                            @error('phone')
                                <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="profile-address" class="block text-text-secondary text-sm mb-2">Địa chỉ</label>
                            <textarea id="profile-address"
                                      name="address"
                                      rows="2"
                                      autocomplete="street-address"
                                      class="form-input"
                                      placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố">{{ old('address', $user->address) }}</textarea>
                            @error('address')
                                <p class="text-accent-red text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="w-full btn-primary py-4 text-base tracking-wide mt-6 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        LƯU THAY ĐỔI
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection
