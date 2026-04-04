@extends('layouts.artist')
@section('title', 'Analytics: ' . $audiobook->title)
@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('artist.analytics.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Analytics</a>
        <span class="text-gray-300">/</span>
        <span class="text-sm font-semibold text-gray-800">{{ $audiobook->title }}</span>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm">Episode Performance</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">Episode</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600">Plays</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600 hidden sm:table-cell">Avg Completion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($episodeStats as $stat)
                    <tr class="hover:bg-surface-50">
                        <td class="px-5 py-3 text-gray-800">{{ $stat->title }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ number_format($stat->play_count) }}</td>
                        <td class="px-5 py-3 text-right hidden sm:table-cell">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-24 bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-purple-500 h-1.5 rounded-full" style="width: {{ min(round($stat->avg_completion ?? 0), 100) }}%"></div>
                                </div>
                                <span class="text-gray-600 text-xs">{{ round($stat->avg_completion ?? 0) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-5 py-12 text-center text-gray-400">No listen data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
