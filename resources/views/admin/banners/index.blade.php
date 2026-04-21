@extends('layouts.admin')
@section('title', 'Banners')
@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Banners</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage home-screen carousel banners</p>
        </div>
        <a href="{{ route('admin.banners.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Banner
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    {{-- Banner list --}}
    @if ($banners->isEmpty())
        <div class="text-center py-16 bg-white rounded-2xl border border-gray-100">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-400 text-sm">No banners yet. Create one to get started.</p>
        </div>
    @else
        <div class="grid gap-4">
            @foreach ($banners as $banner)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 p-4">

                {{-- Thumbnail --}}
                <div class="w-24 h-14 rounded-xl overflow-hidden bg-gray-100 shrink-0">
                    @if ($banner->image_url)
                        <img src="{{ $banner->image_url }}" class="w-full h-full object-cover" alt="{{ $banner->title }}">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 truncate">{{ $banner->title }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        @php
                            $typeColors = [
                                'book'     => 'bg-blue-50 text-blue-700',
                                'external' => 'bg-purple-50 text-purple-700',
                                'static'   => 'bg-gray-100 text-gray-600',
                            ];
                        @endphp
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $typeColors[$banner->link_type] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($banner->link_type) }}
                        </span>
                        @if ($banner->link_value)
                            <span class="text-xs text-gray-400 truncate max-w-xs">{{ $banner->link_value }}</span>
                        @endif
                        <span class="text-xs text-gray-400">Order: {{ $banner->order }}</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 shrink-0">
                    <form action="{{ route('admin.banners.toggle-active', $banner) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit"
                            class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition
                                {{ $banner->is_active
                                    ? 'border-green-200 text-green-700 bg-green-50 hover:bg-green-100'
                                    : 'border-gray-200 text-gray-500 bg-gray-50 hover:bg-gray-100' }}">
                            {{ $banner->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </form>
                    <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST"
                          onsubmit="return confirm('Delete this banner?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 transition">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
