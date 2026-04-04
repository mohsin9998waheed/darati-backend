@extends('layouts.admin')
@section('title', $audiobook->title)
@section('content')
@php
    $durationMin = intdiv($totalDuration, 60);
@endphp
<div class="space-y-5">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('admin.audiobooks.index') }}" class="text-gray-400 hover:text-gray-600">Audiobooks</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-700 font-medium truncate">{{ $audiobook->title }}</span>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Hero ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex flex-col sm:flex-row gap-5 p-5">
            {{-- Cover --}}
            <div class="w-full sm:w-40 h-40 bg-gradient-to-br from-primary-100 to-purple-100 rounded-xl overflow-hidden shrink-0">
                @if ($audiobook->thumbnail)
                    <img src="{{ $audiobook->thumbnail_url }}" alt="{{ $audiobook->title }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-14 h-14 text-primary-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                    </div>
                @endif
            </div>
            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ $audiobook->title }}</h2>
                        <div class="flex items-center gap-2 mt-1.5 flex-wrap text-sm text-gray-500">
                            <span>By <span class="font-semibold text-gray-700">{{ $audiobook->artist->name }}</span></span>
                            <span class="text-gray-200">|</span>
                            @if ($audiobook->category)
                                <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">{{ $audiobook->category->name }}</span>
                            @endif
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-medium uppercase">{{ $audiobook->language }}</span>
                            @if ($audiobook->isApproved())
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Approved</span>
                            @elseif ($audiobook->isPending())
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold">Pending Review</span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Rejected</span>
                            @endif
                        </div>
                    </div>
                    {{-- Admin actions --}}
                    <div class="flex items-center gap-2 shrink-0 flex-wrap">
                        {{-- Trending toggle --}}
                        <form method="POST" action="{{ route('admin.audiobooks.toggle-trending', $audiobook) }}" data-no-loader>
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 border text-xs font-medium rounded-lg transition
                                {{ $audiobook->is_trending
                                    ? 'border-amber-400 bg-amber-50 text-amber-700 hover:bg-amber-100'
                                    : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                <svg class="w-3.5 h-3.5" fill="{{ $audiobook->is_trending ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                {{ $audiobook->is_trending ? 'Trending ✓' : 'Mark Trending' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.audiobooks.destroy', $audiobook) }}" onsubmit="return confirm('Permanently delete this audiobook?')" data-no-loader>
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-red-200 text-red-600 text-xs font-medium rounded-lg hover:bg-red-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>

                @if ($audiobook->description)
                    <p class="text-sm text-gray-500 mt-3 leading-relaxed">{{ $audiobook->description }}</p>
                @endif

                @if ($audiobook->isRejected() && $audiobook->rejection_reason)
                    <div class="mt-3 bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-xs text-red-700">
                        <span class="font-semibold">Rejection reason:</span> {{ $audiobook->rejection_reason }}
                    </div>
                @endif

                {{-- Stats --}}
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-4">
                    @foreach ([
                        ['label' => 'Chapters',  'value' => $audiobook->chapters->count()],
                        ['label' => 'Episodes',  'value' => $totalEpisodes],
                        ['label' => 'Listens',   'value' => number_format($audiobook->total_listens)],
                        ['label' => 'Favorites', 'value' => $audiobook->favorites_count],
                        ['label' => 'Comments',  'value' => $audiobook->comments_count],
                    ] as $stat)
                    <div class="bg-surface-50 rounded-xl px-3 py-2.5 text-center">
                        <p class="text-lg font-bold text-gray-900">{{ $stat['value'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $stat['label'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ── Left: Content ── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Artist Info --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Artist</h4>
                <div class="flex items-center gap-3">
                    <img src="{{ $audiobook->artist->avatar_url }}" alt="{{ $audiobook->artist->name }}" class="w-11 h-11 rounded-full object-cover shrink-0">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 text-sm">{{ $audiobook->artist->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $audiobook->artist->email }}</p>
                        @if ($audiobook->artist->bio)
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $audiobook->artist->bio }}</p>
                        @endif
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-xs text-gray-400">Total books</p>
                        <p class="text-sm font-bold text-gray-700">{{ $audiobook->artist->audiobooks()->count() }}</p>
                    </div>
                </div>
            </div>

            {{-- Chapters & Episodes --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h4 class="font-semibold text-gray-900">Chapters & Episodes</h4>
                    <span class="text-xs text-gray-400">{{ $audiobook->chapters->count() }} chapter(s) · {{ $totalEpisodes }} episode(s) @if($durationMin > 0) · {{ $durationMin }} min @endif</span>
                </div>

                @forelse ($audiobook->chapters as $chapter)
                <div class="border-b border-gray-50 last:border-b-0" x-data="{ open: true }">
                    {{-- Chapter row --}}
                    <div class="flex items-center gap-3 px-5 py-3 bg-surface-50/60 cursor-pointer hover:bg-surface-100 select-none" @click="open = !open">
                        <span class="w-6 h-6 flex items-center justify-center bg-primary-100 text-primary-700 rounded-full text-xs font-bold shrink-0">{{ $chapter->order }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800">{{ $chapter->title }}</p>
                            @if ($chapter->description)
                                <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $chapter->description }}</p>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 shrink-0">{{ $chapter->episodes->count() }} episodes</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    {{-- Episodes --}}
                    <div x-show="open" x-transition>
                        @forelse ($chapter->episodes as $episode)
                        <div class="flex items-center gap-3 px-5 py-3 border-t border-gray-50 hover:bg-blue-50/20 group">
                            @if ($episode->audio_path)
                            <button
                                type="button"
                                onclick="playEpisode({ id: {{ $episode->id }}, title: {{ Js::from($episode->title) }}, bookTitle: {{ Js::from($audiobook->title) }}, url: '{{ route('episodes.play', $episode) }}', thumbnailUrl: {{ Js::from($audiobook->thumbnail_url) }} })"
                                class="w-9 h-9 flex items-center justify-center rounded-full bg-primary-600 hover:bg-primary-700 active:scale-95 shrink-0 transition shadow-sm"
                                title="Play episode"
                            >
                                <svg class="w-4 h-4 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </button>
                            @else
                            <div class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 border-2 border-dashed border-gray-300 shrink-0" title="No audio file">
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    <span class="text-gray-400 font-normal">{{ $episode->order }}.</span> {{ $episode->title }}
                                </p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    @if ($episode->is_preview)
                                        <span class="text-xs bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded font-medium">Free Preview</span>
                                    @endif
                                    @if ($episode->duration_seconds > 0)
                                        <span class="text-xs text-gray-400">{{ $episode->duration_formatted }}</span>
                                    @endif
                                    @if ($episode->file_size)
                                        <span class="text-xs text-gray-300">{{ round($episode->file_size / 1048576, 1) }} MB</span>
                                    @endif
                                    @if (! $episode->audio_path)
                                        <span class="text-xs text-amber-500">No audio uploaded</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="px-5 py-4 text-center text-xs text-gray-400">No episodes in this chapter.</div>
                        @endforelse
                    </div>
                </div>
                @empty
                <div class="text-center py-10 text-sm text-gray-400">No chapters added yet.</div>
                @endforelse
            </div>
        </div>

        {{-- ── Right Sidebar ── --}}
        <div class="space-y-4">

            {{-- Review Actions --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Review Actions</h4>

                @if ($audiobook->isPending())
                <div class="space-y-3">
                    <form method="POST" action="{{ route('admin.audiobooks.approve', $audiobook) }}" data-no-loader>
                        @csrf @method('PATCH')
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Approve & Publish
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.audiobooks.reject', $audiobook) }}" data-no-loader>
                        @csrf @method('PATCH')
                        <textarea name="rejection_reason" rows="2" placeholder="Reason for rejection (optional)..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl mb-2 focus:outline-none focus:ring-2 focus:ring-red-300 resize-none"></textarea>
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Reject
                        </button>
                    </form>
                </div>

                @elseif ($audiobook->isApproved())
                <div class="space-y-3">
                    <div class="flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-xl">
                        <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span class="text-sm text-green-700 font-semibold">Currently Live</span>
                    </div>
                    <form method="POST" action="{{ route('admin.audiobooks.reject', $audiobook) }}" data-no-loader>
                        @csrf @method('PATCH')
                        <textarea name="rejection_reason" rows="2" placeholder="Reason for revoking..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl mb-2 focus:outline-none resize-none"></textarea>
                        <button type="submit" class="w-full py-2.5 border border-red-300 text-red-600 text-sm font-medium rounded-xl hover:bg-red-50">Revoke Approval</button>
                    </form>
                </div>

                @else
                <div class="space-y-3">
                    <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                        <p class="font-semibold mb-1">Rejection reason:</p>
                        <p>{{ $audiobook->rejection_reason ?? 'No reason given.' }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.audiobooks.approve', $audiobook) }}" data-no-loader>
                        @csrf @method('PATCH')
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Approve Instead
                        </button>
                    </form>
                </div>
                @endif
            </div>

            {{-- Book Details --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Book Details</h4>
                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Category</span>
                        <span class="font-medium">{{ $audiobook->category?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Language</span>
                        <span class="font-medium uppercase">{{ $audiobook->language }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Avg Rating</span>
                        <span class="font-medium">{{ $audiobook->avg_rating > 0 ? number_format($audiobook->avg_rating,1).' / 5' : '—' }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Total duration</span>
                        <span class="font-medium">{{ $durationMin > 0 ? $durationMin.' min' : '—' }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Submitted</span>
                        <span class="font-medium">{{ $audiobook->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Last updated</span>
                        <span class="font-medium">{{ $audiobook->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Listener ratings & comments (moderation) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h4 class="text-sm font-semibold text-gray-900 mb-1">Listener ratings & comments</h4>
        <p class="text-xs text-gray-500 mb-4">“Hide” flags a comment so it no longer appears in the mobile app. Use the same action to show it again.</p>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-3">Recent ratings ({{ $recentRatings->count() }})</h5>
                <div class="max-h-80 overflow-y-auto space-y-2 pr-1">
                    @forelse ($recentRatings as $r)
                        <div class="flex items-start justify-between gap-2 text-sm border border-gray-100 rounded-lg px-3 py-2 bg-surface-50">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 truncate">{{ $r->user?->name ?? 'User' }}</p>
                                <p class="text-xs text-gray-500">{{ $r->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="shrink-0 text-amber-600 font-semibold">{{ $r->rating }}★</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No ratings yet.</p>
                    @endforelse
                </div>
            </div>
            <div>
                <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-3">Comments ({{ $recentComments->count() }})</h5>
                <div class="max-h-80 overflow-y-auto space-y-2 pr-1">
                    @forelse ($recentComments as $c)
                        <div class="border border-gray-100 rounded-lg px-3 py-2 bg-surface-50 text-sm">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ $c->user?->name ?? 'User' }}</p>
                                    <p class="text-xs text-gray-500">{{ $c->created_at->diffForHumans() }}</p>
                                </div>
                                @if ($c->is_flagged)
                                    <span class="shrink-0 text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-medium">Hidden</span>
                                @else
                                    <span class="shrink-0 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800 font-medium">Visible</span>
                                @endif
                            </div>
                            <p class="text-gray-700 text-xs leading-relaxed line-clamp-4">{{ $c->body }}</p>
                            <form method="POST" action="{{ route('admin.comments.flag', $c) }}" class="mt-2" data-no-loader>
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs font-medium {{ $c->is_flagged ? 'text-green-600 hover:underline' : 'text-amber-700 hover:underline' }}">
                                    {{ $c->is_flagged ? 'Show in app' : 'Hide from app' }}
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No comments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
