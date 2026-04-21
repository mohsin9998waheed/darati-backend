@extends('layouts.admin')
@section('title', 'Listener Analytics')
@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Listener Analytics</h1>
            <p class="text-sm text-gray-500 mt-1">Track which users listen to which audiobooks</p>
        </div>
    </div>

    {{-- ── Top Books by Unique Listeners ── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <span class="text-lg">📚</span>
            <h2 class="text-base font-semibold text-gray-900">Books by Unique Listeners</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-6 py-3 text-left">#</th>
                        <th class="px-6 py-3 text-left">Book</th>
                        <th class="px-6 py-3 text-center">Unique Listeners</th>
                        <th class="px-6 py-3 text-center">Total Hours</th>
                        <th class="px-6 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($bookStats as $i => $book)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 text-gray-400 font-medium">{{ $i + 1 }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                @if ($book->thumbnail)
                                    <img src="{{ $book->thumbnail_url }}" alt="" class="w-10 h-10 rounded-lg object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center text-purple-500 text-xs">📖</div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-900">{{ $book->title }}</p>
                                    <p class="text-xs text-gray-400">{{ $book->artist?->name ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold text-xs">
                                👤 {{ number_format($book->unique_listeners ?? 0) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-center text-gray-600">
                            {{ number_format(($book->total_seconds ?? 0) / 3600, 1) }}h
                        </td>
                        <td class="px-6 py-3 text-center">
                            <a href="{{ route('admin.analytics.listeners.book', $book) }}"
                               class="text-xs text-purple-600 hover:underline font-medium">View Detail →</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No listen data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Top Listeners ── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <span class="text-lg">🏆</span>
            <h2 class="text-base font-semibold text-gray-900">Top Listeners</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-6 py-3 text-left">#</th>
                        <th class="px-6 py-3 text-left">User</th>
                        <th class="px-6 py-3 text-center">Episodes Played</th>
                        <th class="px-6 py-3 text-center">Total Hours</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($topListeners as $i => $user)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 text-gray-400 font-medium">{{ $i + 1 }}</td>
                        <td class="px-6 py-3">
                            <p class="font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $user->email }}</p>
                        </td>
                        <td class="px-6 py-3 text-center text-gray-700 font-medium">
                            {{ number_format($user->listens_count) }}
                        </td>
                        <td class="px-6 py-3 text-center text-gray-600">
                            {{ number_format(($user->listens_sum_progress_seconds ?? 0) / 3600, 1) }}h
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No listen data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Recent Activity ── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <span class="text-lg">⏱</span>
            <h2 class="text-base font-semibold text-gray-900">Recent Listening Activity</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-6 py-3 text-left">User</th>
                        <th class="px-6 py-3 text-left">Book / Episode</th>
                        <th class="px-6 py-3 text-center">Progress</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Last Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($recentActivity as $listen)
                    @php
                        $ep = $listen->episode;
                        $book = $ep?->chapter?->audiobook;
                        $dur = (int) ($ep?->duration_seconds ?? 0);
                        $prog = (int) $listen->progress_seconds;
                        $pct = $dur > 0 ? min(100, round($prog / $dur * 100)) : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3">
                            <p class="font-medium text-gray-900">{{ $listen->user?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $listen->user?->email }}</p>
                        </td>
                        <td class="px-6 py-3">
                            <p class="font-medium text-gray-800">{{ $book?->title ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $ep?->title }}</p>
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if ($dur > 0)
                                <div class="flex items-center gap-2 justify-center">
                                    <div class="w-20 bg-gray-100 rounded-full h-1.5">
                                        <div class="bg-purple-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $pct }}%</span>
                                </div>
                            @else
                                <span class="text-xs text-gray-400">{{ gmdate('i:s', $prog) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if ($listen->completed)
                                <span class="px-2 py-0.5 bg-green-50 text-green-700 rounded-full text-xs font-medium">✓ Done</span>
                            @else
                                <span class="px-2 py-0.5 bg-amber-50 text-amber-700 rounded-full text-xs font-medium">In Progress</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right text-xs text-gray-400">
                            {{ $listen->updated_at?->diffForHumans() }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── City Analytics ── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <span class="text-lg">🌍</span>
            <h2 class="text-base font-semibold text-gray-900">Listeners by City</h2>
            <span class="text-xs text-gray-400 ml-2">(based on detected location)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-6 py-3 text-left">#</th>
                        <th class="px-6 py-3 text-left">City</th>
                        <th class="px-6 py-3 text-left">Country</th>
                        <th class="px-6 py-3 text-right">Users</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($cityStats as $i => $row)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 text-gray-400 text-xs">{{ $i + 1 }}</td>
                        <td class="px-6 py-3 font-medium text-gray-900">{{ $row->city ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $row->country ?? '—' }}</td>
                        <td class="px-6 py-3 text-right font-semibold text-indigo-700">{{ number_format($row->user_count) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No location data yet. Cities appear as users open the app.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
