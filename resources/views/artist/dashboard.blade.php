@extends('layouts.artist')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-6">

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col gap-1">
            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center mb-1">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_books'] }}</p>
            <p class="text-xs text-gray-500">Total Books</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col gap-1">
            <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center mb-1">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-2xl font-bold text-green-700">{{ $stats['approved_books'] }}</p>
            <p class="text-xs text-gray-500">Live</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col gap-1">
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center mb-1">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-2xl font-bold text-amber-600">{{ $stats['pending_books'] }}</p>
            <p class="text-xs text-gray-500">Pending</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col gap-1">
            <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center mb-1">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-2xl font-bold text-purple-700">{{ number_format($stats['total_listens']) }}</p>
            <p class="text-xs text-gray-500">Total Listens</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col gap-1">
            <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center mb-1">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-2xl font-bold text-teal-700">{{ number_format($stats['total_play_hours'], 1) }}</p>
            <p class="text-xs text-gray-500">Hours Listened</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col gap-1">
            <div class="w-8 h-8 rounded-lg bg-yellow-50 flex items-center justify-center mb-1">
                <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['avg_rating'] > 0 ? $stats['avg_rating'] : '—' }}</p>
            <p class="text-xs text-gray-500">Avg Rating</p>
        </div>

    </div>

    {{-- ── Recent Books ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Recent Audiobooks</h3>
            <div class="flex items-center gap-3">
                <a href="{{ route('artist.analytics.index') }}" class="text-xs text-purple-600 hover:underline">Analytics →</a>
                <a href="{{ route('artist.audiobooks.index') }}" class="text-xs text-gray-500 hover:text-gray-700">View all</a>
            </div>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse ($recentBooks as $book)
            <div class="flex items-center gap-3 px-5 py-3.5">
                <div class="w-10 h-10 bg-gray-100 rounded-xl overflow-hidden shrink-0">
                    @if ($book->thumbnail)
                        <img src="{{ $book->thumbnail_url }}" alt="" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $book->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $book->category?->name ?? 'No category' }} &bull; {{ number_format($book->total_listens) }} listens</p>
                </div>
                @if ($book->isPending())
                    <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>
                @elseif ($book->isApproved())
                    <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Live</span>
                @else
                    <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rejected</span>
                @endif
                <a href="{{ route('artist.audiobooks.show', $book) }}"
                   class="shrink-0 text-xs text-purple-600 hover:underline">Manage</a>
            </div>
            @empty
            <div class="px-5 py-16 text-center">
                <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                <p class="text-gray-400 text-sm mb-3">No audiobooks yet.</p>
                <a href="{{ route('artist.audiobooks.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm rounded-xl hover:bg-purple-700">Upload your first audiobook</a>
            </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
