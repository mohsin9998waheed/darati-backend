@extends('layouts.artist')
@section('title', 'Analytics')
@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-2xl font-bold text-primary-600">{{ number_format($totalListens) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Listens</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-2xl font-bold text-pink-500">{{ number_format($totalFavorites) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Favorites</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-2xl font-bold text-yellow-500">{{ $avgRating > 0 ? $avgRating . ' / 5' : '—' }}</p>
            <p class="text-sm text-gray-500 mt-1">Avg Rating</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm">Audiobook Performance</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">Title</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600">Listens</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600 hidden sm:table-cell">Rating</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600 hidden md:table-cell">Comments</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600 hidden lg:table-cell">Favorites</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($books as $book)
                    <tr class="hover:bg-surface-50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                                    @if ($book->thumbnail)
                                        <img src="{{ $book->thumbnail_url }}" alt="" class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $book->title }}</p>
                                    @if ($book->isPending())
                                        <span class="text-xs text-amber-600">Pending</span>
                                    @elseif ($book->isApproved())
                                        <span class="text-xs text-green-600">Live</span>
                                    @else
                                        <span class="text-xs text-red-500">Rejected</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ number_format($book->total_listens) }}</td>
                        <td class="px-5 py-3 text-right hidden sm:table-cell">
                            <span class="text-yellow-500 font-medium">{{ $book->avg_rating > 0 ? $book->avg_rating : '—' }}</span>
                            <span class="text-gray-400 text-xs">({{ $book->ratings_count }})</span>
                        </td>
                        <td class="px-5 py-3 text-right text-gray-600 hidden md:table-cell">{{ $book->comments_count }}</td>
                        <td class="px-5 py-3 text-right text-gray-600 hidden lg:table-cell">{{ $book->favorites_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('artist.analytics.show', $book) }}" class="text-xs text-purple-600 hover:underline">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
