@extends('layouts.app')

@section('title', 'Thông báo - Gundam Shop')

@section('content')

<section class="py-12 lg:py-16 bg-bg-secondary/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-1 h-8 bg-accent-blue"></div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white tracking-wider font-display">THÔNG BÁO</h1>
        </div>

        @if(auth()->user()->unreadNotifications()->count() > 0)
            <form action="{{ route('notifications.read-all') }}" method="POST" class="mb-6">
                @csrf
                <button type="submit" class="text-sm text-accent-blue hover:text-white transition-colors">Đánh dấu đã đọc tất cả</button>
            </form>
        @endif

        @if($notifications->count() > 0)
            <div class="space-y-3">
                @foreach($notifications as $notification)
                    @php $data = $notification->data; @endphp
                    <div class="bg-bg-secondary border rounded-xl p-4 {{ $notification->read_at ? 'border-border' : 'border-accent-blue/30 bg-accent-blue/5' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <p class="{{ $notification->read_at ? 'text-text-secondary' : 'text-white font-medium' }} text-sm">
                                    {{ $data['message'] ?? 'Thông báo mới' }}
                                </p>
                                <p class="text-text-secondary text-xs mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if(! $notification->read_at)
                                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs text-text-secondary hover:text-white transition-colors">Đã đọc</button>
                                    </form>
                                @endif
                                @if(! empty($data['link']))
                                    <a href="{{ $data['link'] }}" class="text-xs text-accent-blue hover:text-white transition-colors">Xem →</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">{{ $notifications->links() }}</div>
        @else
            <div class="text-center py-16">
                <svg class="w-24 h-24 mx-auto text-text-secondary mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <h2 class="text-2xl font-bold text-white mb-4">Chưa có thông báo</h2>
                <p class="text-text-secondary mb-8">Các thông báo về đợt gom và đơn hàng sẽ hiển thị ở đây.</p>
                <a href="{{ route('batches.index') }}" class="btn-primary py-3 px-8 text-sm tracking-wide">XEM ĐỢT GOM</a>
            </div>
        @endif
    </div>
</section>

@endsection
