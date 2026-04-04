@extends('layouts.artist')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_books'] }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Books</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-2xl font-bold text-green-600">{{ $stats['approved_books'] }}</p>
            <p class="text-sm text-gray-500 mt-1">Approved</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-2xl font-bold text-amber-600">{{ $stats['pending_books'] }}</p>
            <p class="text-sm text-gray-500 mt-1">Pending</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-2xl font-bold text-primary-600">{{ number_format($stats['total_listens']) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Listens</p>
        </div>
        <div class="col-span-2 lg:col-span-1 bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-2xl font-bold text-yellow-500">{{ $stats['avg_rating'] > 0 ? $stats['avg_rating'] : '—' }}</p>
            <p class="text-sm text-gray-500 mt-1">Avg Rating</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Recent Audiobooks</h3>
            <a href="{{ route('artist.audiobooks.index') }}" class="text-xs text-purple-600 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse ($recentBooks as $book)
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $book->title }}</p>
                    <p class="text-xs text-gray-500">{{ $book->category?->name ?? 'No category' }} &bull; {{ number_format($book->total_listens) }} listens</p>
                </div>
                @if ($book->isPending())
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>
                @elseif ($book->isApproved())
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Live</span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rejected</span>
                @endif
                <a href="{{ route('artist.audiobooks.show', $book) }}" class="text-xs text-purple-600 hover:underline">Manage</a>
            </div>
            @empty
            <div class="px-5 py-12 text-center">
                <p class="text-gray-400 text-sm mb-3">No audiobooks yet.</p>
                <a href="{{ route('artist.audiobooks.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700">Upload your first audiobook</a>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
