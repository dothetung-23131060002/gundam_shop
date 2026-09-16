@extends('layouts.app')

@section('title', 'Ghi nhận sự cố - Admin')

@section('content')
<div class="flex min-h-screen">
    @include('admin.partials.sidebar')

    <div class="flex-1 ml-0 lg:ml-64">
        <main class="p-6 max-w-xl">
            <h2 class="text-white font-semibold text-lg mb-6">Ghi nhận sự cố đợt gom</h2>

            @if(session('error'))
                <p class="text-accent-red text-sm mb-4">{{ session('error') }}</p>
            @endif

            <form method="POST" action="{{ route('admin.batch-incidents.store') }}" class="bg-bg-secondary border border-border rounded-xl p-6 space-y-4">
                @csrf
                <div>
                    <label class="text-text-secondary text-sm">Đợt gom</label>
                    <select name="batch_id" class="w-full mt-1 bg-bg-primary border border-border rounded-lg text-white text-sm p-2">
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" @selected(optional($selectedBatch)->id === $batch->id)>#{{ $batch->id }} — {{ $batch->product->name ?? 'N/A' }} ({{ $batch->status }})</option>
                        @endforeach
                    </select>
                    @error('batch_id')<p class="text-accent-red text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-text-secondary text-sm">Loại sự cố</label>
                    <select name="type" class="w-full mt-1 bg-bg-primary border border-border rounded-lg text-white text-sm p-2">
                        @foreach(\App\Models\BatchIncident::TYPES as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="text-accent-red text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-text-secondary text-sm">Mô tả</label>
                    <textarea name="description" rows="3" class="w-full mt-1 bg-bg-primary border border-border rounded-lg text-white text-sm p-2">{{ old('description') }}</textarea>
                </div>
                <button type="submit" class="px-4 py-2 bg-accent-blue text-white text-sm rounded-lg">Ghi nhận</button>
            </form>
        </main>
    </div>
</div>
@endsection
