@extends('layouts.app')

@section('title', 'Chi tiết sự cố - Admin')

@section('content')
<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <main class="p-6 max-w-3xl">
            <h2 class="text-white font-semibold text-lg mb-6">Sự cố #{{ $batchIncident->id }} — {{ $batchIncident->type }}</h2>

            @if(session('success'))
                <p class="text-green-400 text-sm mb-4">{{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="text-accent-red text-sm mb-4">{{ session('error') }}</p>
            @endif

            <div class="bg-bg-secondary border border-border rounded-xl p-6 mb-6 text-sm">
                <p class="text-text-secondary">Đợt gom: <span class="text-white">#{{ $batchIncident->batch_id }} {{ $batchIncident->batch->product->name ?? '' }} ({{ $batchIncident->batch->status ?? '' }})</span></p>
                <p class="text-text-secondary mt-1">Mô tả: <span class="text-white">{{ $batchIncident->description ?? '—' }}</span></p>
                <p class="text-text-secondary mt-1">Ghi nhận bởi: <span class="text-white">{{ $batchIncident->admin->name ?? 'Hệ thống' }}</span> lúc {{ $batchIncident->created_at->format('d/m/Y H:i') }}</p>
            </div>

            @if($batchIncident->type === 'supplier_shortage')
                <form method="POST" action="{{ route('admin.batch-incidents.resolve', $batchIncident) }}" class="mb-6">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-accent-gold text-black text-sm rounded-lg">Resolve cả đợt (reserved theo flow fail + hoàn/cancel các đơn còn lại)</button>
                </form>
            @endif

            <h3 class="text-white font-medium mb-3">Đơn chưa hủy của đợt (resolve từng đơn = hoàn full phần còn lại)</h3>
            @if($orders->count() > 0)
                <div class="bg-bg-secondary border border-border rounded-xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="py-2 px-4 text-text-secondary text-xs font-medium">Đơn</th>
                                <th class="py-2 px-4 text-text-secondary text-xs font-medium">Khách</th>
                                <th class="py-2 px-4 text-text-secondary text-xs font-medium text-right">Tổng</th>
                                <th class="py-2 px-4 text-text-secondary text-xs font-medium">Thanh toán</th>
                                <th class="py-2 px-4 text-text-secondary text-xs font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr class="border-b border-border/50">
                                    <td class="py-2 px-4 text-white text-sm">#{{ $order->id }} ({{ $order->order_status }})</td>
                                    <td class="py-2 px-4 text-text-secondary text-sm">{{ $order->user->name ?? '' }}</td>
                                    <td class="py-2 px-4 text-white text-sm text-right tabular-nums">{{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    <td class="py-2 px-4 text-text-secondary text-sm">{{ $order->payment_method }} / {{ $order->payment_status }}</td>
                                    <td class="py-2 px-4 text-right">
                                        <form method="POST" action="{{ route('admin.batch-incidents.resolve', $batchIncident) }}">
                                            @csrf
                                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                                            <button type="submit" class="text-accent-blue text-sm hover:underline">Resolve</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-text-secondary text-sm">Không còn đơn chưa hủy trong đợt này.</p>
            @endif
        </main>
    </div>
</div>
@endsection
