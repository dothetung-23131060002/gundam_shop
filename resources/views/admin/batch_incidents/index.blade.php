@extends('layouts.app')

@section('title', 'Sự cố đợt gom - Admin')

@section('content')
<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <main class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-white font-semibold text-lg">Sự cố đợt gom</h2>
                <a href="{{ route('admin.batch-incidents.create') }}" class="text-accent-blue text-sm hover:underline">+ Ghi nhận sự cố</a>
            </div>

            @if(session('success'))
                <p class="text-green-400 text-sm mb-4">{{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="text-accent-red text-sm mb-4">{{ session('error') }}</p>
            @endif

            @if($incidents->count() > 0)
                <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="py-2 px-4 text-text-secondary text-xs font-medium">Mã</th>
                                <th class="py-2 px-4 text-text-secondary text-xs font-medium">Đợt gom</th>
                                <th class="py-2 px-4 text-text-secondary text-xs font-medium">Loại</th>
                                <th class="py-2 px-4 text-text-secondary text-xs font-medium">Admin</th>
                                <th class="py-2 px-4 text-text-secondary text-xs font-medium">Ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incidents as $incident)
                                <tr class="border-b border-border/50">
                                    <td class="py-2 px-4 text-sm"><a href="{{ route('admin.batch-incidents.show', $incident) }}" class="text-accent-blue hover:underline">#{{ $incident->id }}</a></td>
                                    <td class="py-2 px-4 text-white text-sm">#{{ $incident->batch_id }} {{ $incident->batch->product->name ?? '' }}</td>
                                    <td class="py-2 px-4 text-white text-sm">{{ $incident->type }}</td>
                                    <td class="py-2 px-4 text-text-secondary text-sm">{{ $incident->admin->name ?? 'Hệ thống' }}</td>
                                    <td class="py-2 px-4 text-text-secondary text-sm">{{ $incident->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $incidents->links() }}</div>
            @else
                <p class="text-text-secondary text-center text-sm py-8">Chưa có sự cố nào được ghi nhận.</p>
            @endif
        </main>
    </div>
</div>
@endsection
