@extends('layouts.artist')
@section('title', 'My Audiobooks')
@section('content')
<div class="space-y-4">
    <div class="flex justify-end">
        <a href="{{ route('artist.audiobooks.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Audiobook
        </a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($audiobooks as $book)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
            <div class="h-40 bg-gradient-to-br from-purple-100 to-primary-100 relative">
                @if ($book->thumbnail)
                    <img src="{{ $book->thumbnail_url }}" alt="{{ $book->title }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-purple-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                    </div>
                @endif
                <div class="absolute top-3 right-3">
                    @if ($book->isPending())
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>
                    @elseif ($book->isApproved())
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Live</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rejected</span>
                    @endif
                </div>
            </div>
            <div class="p-4">
                <h4 class="font-semibold text-gray-900 truncate">{{ $book->title }}</h4>
                <p class="text-xs text-gray-500 mt-1">{{ $book->chapters_count }} chapter(s) &bull; {{ number_format($book->total_listens) }} listens</p>
                @if ($book->isRejected() && $book->rejection_reason)
                    <p class="text-xs text-red-500 mt-2 line-clamp-2">Rejected: {{ $book->rejection_reason }}</p>
                @endif
                <div class="flex items-center gap-2 mt-3">
                    <a href="{{ route('artist.audiobooks.show', $book) }}" class="flex-1 text-center text-xs py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Manage</a>
                    <a href="{{ route('artist.audiobooks.edit', $book) }}" class="flex-1 text-center text-xs py-1.5 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50">Edit</a>
                    <form method="POST" action="{{ route('artist.audiobooks.destroy', $book) }}" onsubmit="return confirm('Delete this audiobook?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs py-1.5 px-3 border border-red-200 text-red-600 rounded-lg hover:bg-red-50">Del</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-xl p-12 text-center border border-dashed border-gray-200">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
            <p class="text-gray-400 mb-4">No audiobooks yet. Start creating your first one.</p>
            <a href="{{ route('artist.audiobooks.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">Upload Audiobook</a>
        </div>
        @endforelse
    </div>
    <div>{{ $audiobooks->links() }}</div>
</div>
@endsection
