@extends('layouts.admin')
@section('title', 'Listeners — ' . $audiobook->title)
@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.analytics.listeners') }}" class="text-purple-600 hover:text-purple-700 text-sm">← Analytics</a>
        <span class="text-gray-300">/</span>
        <h1 class="text-xl font-bold text-gray-900 truncate">{{ $audiobook->title }}</h1>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm text-center">
            <p class="text-2xl font-bold text-blue-600">{{ number_format($uniqueCount) }}</p>
            <p class="text-xs text-gray-500 mt-1">Unique Listeners</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm text-center">
            <p class="text-2xl font-bold text-purple-600">{{ number_format($listeners->total()) }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Episode Plays</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm text-center">
            <p class="text-2xl font-bold text-green-600">
                {{ number_format($listeners->sum('progress_seconds') / 3600, 1) }}h
            </p>
            <p class="text-xs text-gray-500 mt-1">Total Listen Time</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-900">Listener Records</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-6 py-3 text-left">User</th>
                        <th class="px-6 py-3 text-left">Episode</th>
                        <th class="px-6 py-3 text-center">Progress</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Last Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($listeners as $listen)
                    @php
                        $dur = (int) ($listen->episode?->duration_seconds ?? 0);
                        $prog = (int) $listen->progress_seconds;
                        $pct = $dur > 0 ? min(100, round($prog / $dur * 100)) : 0;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <p class="font-medium text-gray-900">{{ $listen->user?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $listen->user?->email }}</p>
                        </td>
                        <td class="px-6 py-3 text-gray-700 max-w-xs truncate">
                            {{ $listen->episode?->title ?? '—' }}
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
                                <span class="text-xs text-gray-400">{{ gmdate('i:s', $prog) }} played</span>
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
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No listeners yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($listeners->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $listeners->links() }}</div>
        @endif
    </div>
</div>
@endsection
