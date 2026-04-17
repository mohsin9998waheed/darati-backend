@extends('layouts.artist')
@section('title', 'Analytics: ' . $audiobook->title)
@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('artist.analytics.index') }}" class="text-gray-400 hover:text-gray-600">Analytics</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-700 font-medium truncate">{{ $audiobook->title }}</span>
    </div>

    {{-- ── Book Summary ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center gap-4 flex-wrap">
            @if ($audiobook->thumbnail)
            <div class="w-14 h-14 rounded-xl overflow-hidden shrink-0">
                <img src="{{ $audiobook->thumbnail_url }}" alt="" class="w-full h-full object-cover">
            </div>
            @endif
            <div class="flex-1 min-w-0">
                <h2 class="font-bold text-gray-900 text-lg truncate">{{ $audiobook->title }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $audiobook->chapters->count() }} chapter(s) · {{ $audiobook->chapters->sum(fn($c) => $c->episodes->count()) }} episode(s)</p>
            </div>
            <div class="flex items-center gap-4 flex-wrap">
                <div class="text-center">
                    <p class="text-lg font-bold text-purple-700">{{ number_format($audiobook->total_listens) }}</p>
                    <p class="text-xs text-gray-400">Listens</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-teal-700">{{ $bookListenHours }}</p>
                    <p class="text-xs text-gray-400">Hours</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-yellow-500">{{ $audiobook->avg_rating > 0 ? number_format($audiobook->avg_rating, 1) : '—' }}</p>
                    <p class="text-xs text-gray-400">Rating</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Episode Performance Table ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm">Episode Performance</h3>
            <p class="text-xs text-gray-400 mt-0.5">How listeners engage with each episode</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide">Episode</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide">Plays</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide hidden sm:table-cell">Completed</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide hidden md:table-cell">Listen Time</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide hidden sm:table-cell">Avg Completion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php $maxPlays = $episodeStats->max('play_count') ?: 1; @endphp
                    @forelse ($episodeStats as $stat)
                    <tr class="hover:bg-gray-50/60 transition-colors">
                        <td class="px-5 py-3.5 text-gray-800 font-medium">{{ $stat->title }}</td>

                        {{-- Plays with mini bar --}}
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex flex-col items-end gap-1">
                                <span class="font-bold text-gray-900">{{ number_format($stat->play_count) }}</span>
                                <div class="w-16 bg-gray-100 rounded-full h-1">
                                    <div class="bg-purple-500 h-1 rounded-full" style="width: {{ round(($stat->play_count / $maxPlays) * 100) }}%"></div>
                                </div>
                            </div>
                        </td>

                        {{-- Completed count --}}
                        <td class="px-5 py-3.5 text-right text-gray-600 hidden sm:table-cell">
                            {{ number_format($stat->completed_count) }}
                            @if ($stat->play_count > 0)
                                <span class="text-xs text-gray-400">({{ round(($stat->completed_count / $stat->play_count) * 100) }}%)</span>
                            @endif
                        </td>

                        {{-- Listen time --}}
                        <td class="px-5 py-3.5 text-right hidden md:table-cell">
                            @php $listenMins = intdiv((int)$stat->total_listen_seconds, 60); @endphp
                            @if ($listenMins >= 60)
                                <span class="font-semibold text-teal-700">{{ round($stat->total_listen_seconds / 3600, 1) }}</span>
                                <span class="text-xs text-gray-400">h</span>
                            @elseif ($listenMins > 0)
                                <span class="font-semibold text-teal-700">{{ $listenMins }}</span>
                                <span class="text-xs text-gray-400">min</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Avg completion bar --}}
                        <td class="px-5 py-3.5 text-right hidden sm:table-cell">
                            @php $completion = min(round($stat->avg_completion ?? 0), 100); @endphp
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-24 bg-gray-100 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $completion >= 75 ? 'bg-green-500' : ($completion >= 40 ? 'bg-purple-500' : 'bg-amber-400') }}"
                                         style="width: {{ $completion }}%"></div>
                                </div>
                                <span class="text-gray-600 text-xs font-semibold w-8 text-right">{{ $completion }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-16 text-center">
                            <p class="text-sm text-gray-400">No listen data yet for this audiobook.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
