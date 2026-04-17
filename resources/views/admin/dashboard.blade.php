@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-6">

    {{-- ── Stat Cards ─────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Users --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Total Users</p>
                <p class="text-xs text-gray-400 mt-1">{{ number_format($stats['total_artists']) }} artists · {{ number_format($stats['total_listeners']) }} listeners</p>
            </div>
        </div>

        {{-- Total Listens --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-purple-700">{{ number_format($stats['total_listens']) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Total Listens</p>
                <p class="text-xs text-gray-400 mt-1">Episodes completed by users</p>
            </div>
        </div>

        {{-- Listening Hours --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-700">{{ number_format($stats['total_play_hours'], 1) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Listening Hours</p>
                <p class="text-xs text-gray-400 mt-1">Across all books &amp; users</p>
            </div>
        </div>

        {{-- Pending --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl {{ $stats['pending_books'] > 0 ? 'bg-amber-50' : 'bg-gray-50' }} flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 {{ $stats['pending_books'] > 0 ? 'text-amber-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold {{ $stats['pending_books'] > 0 ? 'text-amber-600' : 'text-gray-700' }}">{{ $stats['pending_books'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Pending Review</p>
                @if ($stats['processing_episodes'] > 0)
                    <p class="text-xs text-amber-500 mt-1">{{ $stats['processing_episodes'] }} episode(s) transcoding</p>
                @else
                    <p class="text-xs text-gray-400 mt-1">{{ $stats['approved_books'] }} approved · {{ $stats['rejected_books'] }} rejected</p>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Main Grid ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recent Audiobooks --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 lg:col-span-2 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">Recent Submissions</h3>
                <a href="{{ route('admin.audiobooks.index') }}" class="text-xs text-purple-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse ($recentAudiobooks as $book)
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
                        <p class="font-medium text-gray-900 truncate text-sm">{{ $book->title }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $book->artist->name ?? '—' }} &bull; {{ $book->created_at->diffForHumans() }}</p>
                    </div>
                    @if ($book->isPending())
                        <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>
                    @elseif ($book->isApproved())
                        <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Live</span>
                    @else
                        <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rejected</span>
                    @endif
                    <a href="{{ route('admin.audiobooks.show', $book) }}" class="shrink-0 text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Review</a>
                </div>
                @empty
                <div class="px-5 py-12 text-center">
                    <p class="text-sm text-gray-400">No recent audiobooks.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-5">

            {{-- Top Books by Listens --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 text-sm">Top Books</h3>
                    <span class="text-xs text-gray-400">by listens</span>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse ($topAudiobooks as $i => $book)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <span class="w-5 text-center text-xs font-bold text-gray-400 shrink-0">{{ $i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 text-sm truncate">{{ $book->title }}</p>
                            <p class="text-xs text-gray-400">{{ $book->artist->name ?? '—' }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-semibold text-purple-600">{{ number_format($book->total_listens) }}</span>
                    </div>
                    @empty
                    <div class="px-5 py-6 text-center text-xs text-gray-400">No listen data yet.</div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Users --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 text-sm">Recent Users</h3>
                    <a href="{{ route('admin.users.index') }}" class="text-xs text-purple-600 hover:underline">View all</a>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse ($recentUsers as $user)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <img src="{{ $user->avatar_url }}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 truncate text-sm">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $user->role === 'artist' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($user->role ?? 'user') }}
                        </span>
                    </div>
                    @empty
                    <div class="px-5 py-6 text-center text-xs text-gray-400">No users yet.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    {{-- ── Moderation Summary ──────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h3 class="font-semibold text-gray-900 text-sm">Platform Overview</h3>
                <p class="text-xs text-gray-500 mt-0.5">Current moderation workload at a glance.</p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <div class="px-4 py-3 rounded-xl bg-green-50 border border-green-100 text-center min-w-[80px]">
                    <p class="text-lg font-bold text-green-700">{{ number_format($stats['approved_books']) }}</p>
                    <p class="text-xs text-green-700/80 mt-0.5">Approved</p>
                </div>
                <div class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-100 text-center min-w-[80px]">
                    <p class="text-lg font-bold text-amber-700">{{ number_format($stats['pending_books']) }}</p>
                    <p class="text-xs text-amber-700/80 mt-0.5">Pending</p>
                </div>
                <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-center min-w-[80px]">
                    <p class="text-lg font-bold text-red-700">{{ number_format($stats['rejected_books']) }}</p>
                    <p class="text-xs text-red-700/80 mt-0.5">Rejected</p>
                </div>
                <div class="px-4 py-3 rounded-xl bg-orange-50 border border-orange-100 text-center min-w-[80px]">
                    <p class="text-lg font-bold text-orange-700">{{ number_format($stats['flagged_comments']) }}</p>
                    <p class="text-xs text-orange-700/80 mt-0.5">Flagged Comments</p>
                </div>
                @if ($stats['processing_episodes'] > 0)
                <div class="px-4 py-3 rounded-xl bg-sky-50 border border-sky-100 text-center min-w-[80px]">
                    <p class="text-lg font-bold text-sky-700">{{ number_format($stats['processing_episodes']) }}</p>
                    <p class="text-xs text-sky-700/80 mt-0.5">Transcoding</p>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
