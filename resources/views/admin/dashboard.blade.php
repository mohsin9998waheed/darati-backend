@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_users'] ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Users</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-2xl font-bold text-purple-600">{{ $stats['total_artists'] ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Artists</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-2xl font-bold text-primary-600">{{ number_format($stats['total_listens'] ?? 0) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Listens</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-2xl font-bold text-amber-600">{{ $stats['pending_books'] ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Pending Audiobooks</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 lg:col-span-2 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">Recent Audiobooks</h3>
                <a href="{{ route('admin.audiobooks.index') }}" class="text-xs text-primary-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse ($recentAudiobooks as $book)
                    <div class="flex items-center gap-3 px-5 py-4">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                            @if ($book->thumbnail)
                                <img src="{{ $book->thumbnail_url }}" alt="" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ $book->title }}</p>
                            <p class="text-xs text-gray-500 truncate">
                                {{ $book->artist->name ?? '—' }} • {{ $book->category?->name ?? 'No category' }}
                            </p>
                        </div>
                        <a href="{{ route('admin.audiobooks.show', $book) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                            Review
                        </a>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="text-sm text-gray-400">No recent audiobooks.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">Recent Users</h3>
                <a href="{{ route('admin.users.index') }}" class="text-xs text-primary-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse ($recentUsers as $user)
                    <div class="flex items-center gap-3 px-5 py-4">
                        <img src="{{ $user->avatar_url }}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 truncate text-sm">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                            {{ ucfirst($user->role ?? 'user') }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="text-sm text-gray-400">No recent users.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h3 class="font-semibold text-gray-900 text-sm">Moderation Summary</h3>
                <p class="text-sm text-gray-500 mt-1">Quick overview of the current admin workload.</p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <div class="px-3 py-2 rounded-lg bg-green-50 border border-green-100">
                    <p class="text-sm font-semibold text-green-700">{{ $stats['approved_books'] ?? 0 }}</p>
                    <p class="text-xs text-green-700/80">Approved</p>
                </div>
                <div class="px-3 py-2 rounded-lg bg-red-50 border border-red-100">
                    <p class="text-sm font-semibold text-red-700">{{ $stats['rejected_books'] ?? 0 }}</p>
                    <p class="text-xs text-red-700/80">Rejected</p>
                </div>
                <div class="px-3 py-2 rounded-lg bg-amber-50 border border-amber-100">
                    <p class="text-sm font-semibold text-amber-700">{{ $stats['flagged_comments'] ?? 0 }}</p>
                    <p class="text-xs text-amber-700/80">Flagged Comments</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

