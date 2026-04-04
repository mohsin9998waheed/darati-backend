@extends('layouts.artist')
@section('title', $audiobook->title)
@section('content')
@php
    $durationMin = intdiv($totalDuration, 60);
@endphp
<div class="space-y-5">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('artist.audiobooks.index') }}" class="text-gray-400 hover:text-gray-600">My Books</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-700 font-medium truncate">{{ $audiobook->title }}</span>
    </div>

    {{-- Flash / Errors --}}
    @if (session('success'))
        <div class="flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            <p class="font-semibold mb-1">Please fix:</p>
            <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ── Hero ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex flex-col sm:flex-row gap-5 p-5">
            {{-- Cover --}}
            <div class="w-full sm:w-36 h-36 bg-gradient-to-br from-purple-100 to-blue-100 rounded-xl overflow-hidden shrink-0">
                @if ($audiobook->thumbnail)
                    <img src="{{ $audiobook->thumbnail_url }}" alt="{{ $audiobook->title }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-14 h-14 text-purple-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                    </div>
                @endif
            </div>
            {{-- Info --}}
            <div class="flex-1 min-w-0 flex flex-col justify-between gap-3">
                <div>
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 leading-tight">{{ $audiobook->title }}</h2>
                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                @if ($audiobook->isPending())
                                    <span class="px-2.5 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold">Under Review</span>
                                @elseif ($audiobook->isApproved())
                                    <span class="px-2.5 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Live</span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Rejected</span>
                                @endif
                                @if ($audiobook->category)
                                    <span class="px-2.5 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">{{ $audiobook->category->name }}</span>
                                @endif
                                <span class="px-2.5 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-medium uppercase">{{ $audiobook->language }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('artist.audiobooks.edit', $audiobook) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            <form method="POST" action="{{ route('artist.audiobooks.destroy', $audiobook) }}" onsubmit="return confirm('Delete this audiobook and all its content?')" data-no-loader>
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-red-200 text-red-600 text-xs font-medium rounded-lg hover:bg-red-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    @if ($audiobook->description)
                        <p class="text-sm text-gray-500 mt-2 line-clamp-2">{{ $audiobook->description }}</p>
                    @endif
                    @if ($audiobook->isRejected() && $audiobook->rejection_reason)
                        <p class="text-xs text-red-600 mt-2 bg-red-50 rounded-lg px-3 py-2"><span class="font-semibold">Rejected:</span> {{ $audiobook->rejection_reason }}</p>
                    @endif
                </div>
                {{-- Stats row --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-1">
                    <div class="bg-surface-50 rounded-xl px-3 py-2.5 text-center">
                        <p class="text-lg font-bold text-gray-900">{{ $audiobook->chapters->count() }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Chapters</p>
                    </div>
                    <div class="bg-surface-50 rounded-xl px-3 py-2.5 text-center">
                        <p class="text-lg font-bold text-gray-900">{{ $totalEpisodes }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Episodes</p>
                    </div>
                    <div class="bg-surface-50 rounded-xl px-3 py-2.5 text-center">
                        <p class="text-lg font-bold text-gray-900">{{ number_format($audiobook->total_listens) }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Listens</p>
                    </div>
                    <div class="bg-surface-50 rounded-xl px-3 py-2.5 text-center">
                        <p class="text-lg font-bold text-gray-900">{{ $durationMin > 0 ? $durationMin . 'm' : '—' }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Total Time</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- ── Left: Chapters & Episodes ── --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Chapters & Episodes</h3>
                    <span class="text-xs text-gray-400">{{ $audiobook->chapters->count() }} chapter(s) · {{ $totalEpisodes }} episode(s)</span>
                </div>

                @forelse ($audiobook->chapters as $chapter)
                <div class="border-b border-gray-50 last:border-b-0" x-data="{ open: true }">
                    {{-- Chapter header --}}
                    <div class="flex items-center gap-3 px-5 py-3 bg-surface-50/60 hover:bg-surface-100 cursor-pointer select-none" @click="open = !open">
                        <span class="w-6 h-6 flex items-center justify-center bg-purple-100 text-purple-700 rounded-full text-xs font-bold shrink-0">{{ $chapter->order }}</span>
                        <div class="flex-1 min-w-0">
                            <span class="font-semibold text-gray-800 text-sm">{{ $chapter->title }}</span>
                            @if ($chapter->description)
                                <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $chapter->description }}</p>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 shrink-0">{{ $chapter->episodes->count() }} ep.</span>
                        <form method="POST" action="{{ route('artist.chapters.destroy', $chapter) }}" onsubmit="return confirm('Delete chapter and all its episodes?')" data-no-loader class="shrink-0">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-2 py-1 text-xs text-red-400 hover:text-red-600 hover:bg-red-50 rounded">Delete</button>
                        </form>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </div>

                    {{-- Episodes list --}}
                    <div x-show="open" x-transition>
                        @forelse ($chapter->episodes as $episode)
                        <div class="flex items-center gap-3 px-5 py-3 border-t border-gray-50 hover:bg-purple-50/30 group">
                            {{-- Play button --}}
                            @if ($episode->audio_path)
                            <button
                                type="button"
                                onclick="playEpisode({ id: {{ $episode->id }}, title: {{ Js::from($episode->title) }}, bookTitle: {{ Js::from($audiobook->title) }}, url: '{{ route('episodes.play', $episode) }}', thumbnailUrl: {{ Js::from($audiobook->thumbnail_url) }} })"
                                class="w-9 h-9 flex items-center justify-center rounded-full bg-purple-600 hover:bg-purple-700 active:scale-95 shrink-0 transition shadow-sm shadow-purple-200"
                                title="Play {{ $episode->title }}"
                            >
                                <svg class="w-4 h-4 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </button>
                            @else
                            <div class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 border-2 border-dashed border-gray-300 shrink-0" title="No audio uploaded yet">
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    <span class="text-gray-400 font-normal">{{ $episode->order }}.</span> {{ $episode->title }}
                                </p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    @if ($episode->is_preview)
                                        <span class="text-xs bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded font-medium">Preview</span>
                                    @endif
                                    @if ($episode->duration_seconds > 0)
                                        <span class="text-xs text-gray-400">{{ $episode->duration_formatted }}</span>
                                    @endif
                                    @if ($episode->file_size)
                                        <span class="text-xs text-gray-300">{{ round($episode->file_size / 1048576, 1) }} MB</span>
                                    @endif
                                    @if (! $episode->audio_path)
                                        <span class="text-xs text-amber-500 font-medium">No audio</span>
                                    @endif
                                </div>
                            </div>

                            <form method="POST" action="{{ route('artist.episodes.destroy', $episode) }}" onsubmit="return confirm('Delete episode?')" data-no-loader class="shrink-0 opacity-0 group-hover:opacity-100 transition">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-xs text-red-400 hover:text-red-600 hover:bg-red-50 rounded">Delete</button>
                            </form>
                        </div>
                        @empty
                        <div class="px-5 py-4 text-center">
                            <p class="text-xs text-gray-400">No episodes yet — add one below ↓</p>
                        </div>
                        @endforelse

                        {{-- Add Episode --}}
                        <div class="px-5 py-3 bg-surface-50/40 border-t border-dashed border-gray-200" x-data="{ open: false }">
                            <button @click="open = !open" type="button" class="flex items-center gap-1.5 text-xs text-purple-600 hover:text-purple-800 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Add Episode
                            </button>
                            <form x-show="open" x-transition method="POST" action="{{ route('artist.episodes.store', $chapter) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                                @csrf
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Episode title *</label>
                                        <input type="text" name="title" required placeholder="e.g. Part 1 – Introduction" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Audio file * <span class="text-gray-400">(mp3/wav/ogg/m4a, max 200 MB)</span></label>
                                        <input type="file" name="audio_file" accept=".mp3,.wav,.ogg,.m4a" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                        <input type="checkbox" name="is_preview" value="1" class="w-3.5 h-3.5 rounded text-purple-600">
                                        Mark as free preview
                                    </label>
                                    <button type="submit" class="px-5 py-2 bg-purple-600 text-white text-xs font-semibold rounded-lg hover:bg-purple-700">Upload Episode</button>
                                    <button @click.prevent="open = false" type="button" class="text-xs text-gray-400 hover:text-gray-600">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-12">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                    <p class="text-sm text-gray-400">No chapters yet. Add your first chapter below.</p>
                </div>
                @endforelse
            </div>

            {{-- Add Chapter --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5" x-data="{ open: true }">
                <button @click="open = !open" class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <h3 class="font-semibold text-gray-900 text-sm">Add New Chapter</h3>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <form x-show="open" x-transition method="POST" action="{{ route('artist.chapters.store', $audiobook) }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="text" name="title" placeholder="Chapter title..." required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <textarea name="description" rows="2" placeholder="Chapter description (optional)..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none"></textarea>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="px-5 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700">Add Chapter</button>
                        <p class="text-xs text-gray-400">Chapter {{ $audiobook->chapters->count() + 1 }} will be added</p>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Right Sidebar ── --}}
        <div class="space-y-4">

            {{-- Engagement --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Engagement</h4>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Listens
                        </span>
                        <span class="font-semibold text-gray-800">{{ number_format($audiobook->total_listens) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-gray-500">
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            Rating
                        </span>
                        <span class="font-semibold text-gray-800">{{ $audiobook->avg_rating > 0 ? number_format($audiobook->avg_rating, 1) . ' / 5' : '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            Favorites
                        </span>
                        <span class="font-semibold text-gray-800">{{ $audiobook->favorites_count ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            Comments
                        </span>
                        <span class="font-semibold text-gray-800">{{ $audiobook->comments_count ?? 0 }}</span>
                    </div>
                </div>
            </div>

            {{-- Book Details --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Details</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Status</span>
                        <span class="font-medium {{ $audiobook->isApproved() ? 'text-green-600' : ($audiobook->isRejected() ? 'text-red-600' : 'text-amber-600') }}">
                            {{ ucfirst($audiobook->status) }}
                        </span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Category</span>
                        <span class="font-medium">{{ $audiobook->category?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Language</span>
                        <span class="font-medium uppercase">{{ $audiobook->language }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Total duration</span>
                        <span class="font-medium">{{ $durationMin > 0 ? $durationMin . ' min' : '—' }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Created</span>
                        <span class="font-medium">{{ $audiobook->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Last updated</span>
                        <span class="font-medium">{{ $audiobook->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Quick Actions</h4>
                <div class="space-y-2">
                    <a href="{{ route('artist.audiobooks.edit', $audiobook) }}" class="flex items-center gap-2 w-full px-4 py-2.5 border border-gray-200 text-gray-700 text-sm rounded-xl hover:bg-gray-50">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit book details
                    </a>
                    <a href="{{ route('artist.analytics.show', $audiobook) }}" class="flex items-center gap-2 w-full px-4 py-2.5 border border-gray-200 text-gray-700 text-sm rounded-xl hover:bg-gray-50">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        View analytics
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Listener ratings & comments (full width) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h4 class="text-sm font-semibold text-gray-900 mb-1">Listener ratings & comments</h4>
        <p class="text-xs text-gray-500 mb-4">Hide removes a comment from the mobile app for listeners. Toggle again to restore.</p>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-3">Recent ratings ({{ $recentRatings->count() }})</h5>
                <div class="max-h-72 overflow-y-auto space-y-2 pr-1">
                    @forelse ($recentRatings as $r)
                        <div class="flex items-start justify-between gap-2 text-sm border border-gray-100 rounded-lg px-3 py-2 bg-surface-50">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 truncate">{{ $r->user?->name ?? 'Listener' }}</p>
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
                <div class="max-h-72 overflow-y-auto space-y-2 pr-1">
                    @forelse ($recentComments as $c)
                        <div class="border border-gray-100 rounded-lg px-3 py-2 bg-surface-50 text-sm">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ $c->user?->name ?? 'Listener' }}</p>
                                    <p class="text-xs text-gray-500">{{ $c->created_at->diffForHumans() }}</p>
                                </div>
                                @if ($c->is_flagged)
                                    <span class="shrink-0 text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-medium">Hidden</span>
                                @else
                                    <span class="shrink-0 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800 font-medium">Visible</span>
                                @endif
                            </div>
                            <p class="text-gray-700 text-xs leading-relaxed line-clamp-4">{{ $c->body }}</p>
                            <form method="POST" action="{{ route('artist.comments.toggle-flag', $c) }}" class="mt-2" data-no-loader>
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
