@extends('layouts.app')

@section('title', 'Quản lý người dùng - Gundam Shop')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h2 class="text-white font-semibold text-lg">Quản lý người dùng</h2>
            </div>
        </header>

        <main class="p-6">
            <!-- Filters -->
            <div class="bg-bg-secondary border border-border rounded-xl p-4 mb-6">
                <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tìm tên, email..." aria-label="Tìm theo tên hoặc email" class="form-input text-sm">
                    </div>
                    <select name="role" aria-label="Lọc theo vai trò" class="form-input text-sm w-auto">
                        <option value="">Tất cả vai trò</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="customer" {{ request('role') == 'customer' ? 'selected' : '' }}>Customer</option>
                    </select>
                    <button type="submit" class="btn-secondary py-2 px-4 text-sm">Tìm kiếm</button>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Người dùng</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Email</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Số điện thoại</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Vai trò</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Đơn hàng</th>
                                <th class="text-left text-text-secondary text-sm font-medium px-6 py-4">Ngày tạo</th>
                                <th class="text-right text-text-secondary text-sm font-medium px-6 py-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($users as $user)
                                <tr class="hover:bg-bg-primary/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-accent-blue/20 flex items-center justify-center">
                                                <span class="text-accent-blue font-medium text-sm">{{ substr($user->name, 0, 1) }}</span>
                                            </div>
                                            <span class="text-white font-medium text-sm">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-text-secondary text-sm">{{ $user->email }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-text-secondary text-sm">{{ $user->phone ?? 'N/A' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($user->role == 'admin')
                                            <x-status-pill tone="red">Admin</x-status-pill>
                                        @else
                                            <x-status-pill tone="blue">Customer</x-status-pill>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-text-secondary text-sm">{{ $user->orders->count() ?? 0 }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-text-secondary text-sm">{{ $user->created_at->format('d/m/Y') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.users.show', $user) }}" class="text-accent-blue hover:text-white text-sm transition-colors">
                                            Chi tiết →
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <p class="text-text-secondary">Không tìm thấy người dùng nào</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-border">
                    {{ $users->withQueryString()->links() }}
                </div>
            </div>
        </main>
    </div>
</div>

@endsection

