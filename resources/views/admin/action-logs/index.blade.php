@extends('layouts.app')

@section('title', 'Nhật ký thao tác admin - Admin')

@section('content')

<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <header class="bg-bg-secondary border-b border-border px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" aria-label="Mở menu quản trị" class="lg:hidden p-2 text-text-secondary hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h2 class="text-white font-semibold text-lg">Nhật ký thao tác</h2>
                </div>
                <form method="GET" class="flex items-center gap-2">
                    <select name="action" aria-label="Lọc theo hành động" class="bg-bg-primary border border-border rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-accent-blue">
                        <option value="">Mọi hành động</option>
                        @foreach(\App\Models\AdminActionLog::ACTIONS as $key => $label)
                            <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary px-4 py-2 text-sm">Lọc</button>
                </form>
            </div>
        </header>

        <main class="p-6">
            <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Thời gian</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Admin</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Hành động</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Đối tượng</th>
                                <th class="px-6 py-4 text-text-secondary text-sm font-medium">Lý do</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr class="border-b border-border/50 hover:bg-bg-primary/50 transition-colors">
                                    <td class="px-6 py-4 text-text-secondary text-xs whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="px-6 py-4">
                                        <p class="text-white text-sm">{{ $log->admin->name }}</p>
                                        <p class="text-text-secondary text-xs">{{ $log->admin->email }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <x-status-pill tone="blue">{{ $log->actionLabel() }}</x-status-pill>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if($log->batch_id)
                                            <a href="{{ route('admin.batches.show', $log->batch_id) }}" class="text-accent-blue hover:text-white">Đợt #{{ $log->batch_id }}</a>
                                        @endif
                                        @if($log->reservation_id)
                                            <a href="{{ route('admin.reservations.show', $log->reservation_id) }}" class="text-accent-blue hover:text-white block">Slot #{{ $log->reservation_id }}</a>
                                        @endif
                                        @if(! $log->batch_id && ! $log->reservation_id)
                                            <span class="text-text-secondary">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-text-secondary text-sm max-w-[280px]">{{ $log->reason ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-text-secondary text-center">Chưa có thao tác nào được ghi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="px-6 py-4 border-t border-border">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>

@endsection
