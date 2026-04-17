@extends('layouts.artist')
@section('title', 'Analytics')
@section('content')
<div class="space-y-6">

    {{-- ── Summary Cards ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-purple-700">{{ number_format($totalListens) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Total Listens</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-teal-700">{{ number_format($totalPlayHours, 1) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Hours Listened</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-pink-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-pink-600">{{ number_format($totalFavorites) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Total Favorites</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-yellow-600">{{ $avgRating > 0 ? $avgRating . ' / 5' : '—' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Avg Rating</p>
            </div>
        </div>

    </div>

    {{-- ── Per-Book Table ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm">Audiobook Performance</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide">Title</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide">Listens</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide hidden sm:table-cell">Hours</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide hidden sm:table-cell">Rating</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide hidden md:table-cell">Comments</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide hidden lg:table-cell">Favorites</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php $maxListens = $books->max('total_listens') ?: 1; @endphp
                    @forelse ($books as $book)
                    <tr class="hover:bg-gray-50/60 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-gray-100 rounded-xl overflow-hidden shrink-0">
                                    @if ($book->thumbnail)
                                        <img src="{{ $book->thumbnail_url }}" alt="" class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $book->title }}</p>
                                    @if ($book->isPending())
                                        <span class="text-xs text-amber-600 font-medium">Pending</span>
                                    @elseif ($book->isApproved())
                                        <span class="text-xs text-green-600 font-medium">Live</span>
                                    @else
                                        <span class="text-xs text-red-500 font-medium">Rejected</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex flex-col items-end gap-1">
                                <span class="font-bold text-gray-900">{{ number_format($book->total_listens) }}</span>
                                <div class="w-20 bg-gray-100 rounded-full h-1">
                                    <div class="bg-purple-500 h-1 rounded-full" style="width: {{ round(($book->total_listens / $maxListens) * 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-right hidden sm:table-cell">
                            <span class="font-semibold text-teal-700">{{ round($book->total_play_seconds / 3600, 1) }}</span>
                            <span class="text-xs text-gray-400 ml-0.5">h</span>
                        </td>
                        <td class="px-5 py-3.5 text-right hidden sm:table-cell">
                            <span class="text-yellow-500 font-semibold">{{ $book->avg_rating > 0 ? $book->avg_rating : '—' }}</span>
                            <span class="text-gray-400 text-xs">&nbsp;({{ $book->ratings_count }})</span>
                        </td>
                        <td class="px-5 py-3.5 text-right text-gray-600 hidden md:table-cell">{{ $book->comments_count }}</td>
                        <td class="px-5 py-3.5 text-right text-gray-600 hidden lg:table-cell">{{ $book->favorites_count }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('artist.analytics.show', $book) }}"
                               class="text-xs text-purple-600 hover:underline font-medium">View →</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <p class="text-sm text-gray-400">No data yet. Upload and publish your first audiobook.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
