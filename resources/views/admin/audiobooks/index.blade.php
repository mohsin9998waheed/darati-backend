@extends('layouts.admin')
@section('title', 'Audiobooks')
@section('content')
<div class="space-y-4">

    @if ($pendingCount > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p class="text-sm text-amber-700 font-medium">{{ $pendingCount }} audiobook(s) awaiting your review.</p>
        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}" class="ml-auto text-xs text-amber-700 underline hover:no-underline">View pending →</a>
    </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <form method="GET" class="flex gap-2 flex-1" data-no-loader>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title…"
                   class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
            <select name="status" class="px-3 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">All Status</option>
                <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white text-sm rounded-xl hover:bg-purple-700">Filter</button>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide">Book</th>
                        <th class="text-left px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide hidden sm:table-cell">Artist</th>
                        <th class="text-left px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide hidden md:table-cell">Category</th>
                        <th class="text-center px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide hidden lg:table-cell">Episodes</th>
                        <th class="text-left px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide">Status</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-gray-500 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($audiobooks as $book)
                    <tr class="hover:bg-gray-50/60 transition-colors">

                        {{-- Book --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-100 rounded-xl overflow-hidden shrink-0">
                                    @if ($book->thumbnail)
                                        <img src="{{ $book->thumbnail_url }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 truncate">{{ $book->title }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $book->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Artist --}}
                        <td class="px-5 py-3.5 hidden sm:table-cell text-gray-700 text-sm">{{ $book->artist->name }}</td>

                        {{-- Category --}}
                        <td class="px-5 py-3.5 hidden md:table-cell">
                            @if ($book->category)
                                <span class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-medium">{{ $book->category->name }}</span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Episodes + transcoding indicator --}}
                        <td class="px-5 py-3.5 hidden lg:table-cell text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="text-sm font-medium text-gray-700">{{ $book->episodes_count }}</span>
                                @if ($book->processing_episodes_count > 0)
                                    <span title="{{ $book->processing_episodes_count }} episode(s) still transcoding"
                                          class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-medium">
                                        <svg class="w-2.5 h-2.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-dasharray="40" stroke-dashoffset="15" stroke-linecap="round"/></svg>
                                        {{ $book->processing_episodes_count }}
                                    </span>
                                @elseif ($book->episodes_count > 0)
                                    <span class="w-2 h-2 rounded-full bg-green-400 shrink-0" title="All episodes ready"></span>
                                @endif
                            </div>
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-3.5">
                            @if ($book->isPending())
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Pending</span>
                            @elseif ($book->isApproved())
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Approved</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Rejected</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.audiobooks.show', $book) }}"
                                   class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium">View</a>
                                @if ($book->isPending())
                                <form method="POST" action="{{ route('admin.audiobooks.approve', $book) }}" data-no-loader>
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="text-xs px-3 py-1.5 rounded-lg bg-green-600 text-white hover:bg-green-700 font-medium">Approve</button>
                                </form>
                                @endif
                                @if (!$book->isRejected())
                                <button type="button"
                                        onclick="document.getElementById('reject-{{ $book->id }}').classList.remove('hidden')"
                                        class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 font-medium">Reject</button>
                                @endif
                            </div>
                            <form id="reject-{{ $book->id }}" method="POST"
                                  action="{{ route('admin.audiobooks.reject', $book) }}" class="hidden mt-2" data-no-loader>
                                @csrf @method('PATCH')
                                <input type="text" name="rejection_reason" placeholder="Reason (optional)"
                                       class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg mb-1 focus:outline-none focus:ring-2 focus:ring-red-300">
                                <button type="submit"
                                        class="text-xs px-3 py-1.5 bg-red-600 text-white rounded-lg w-full font-medium hover:bg-red-700">Confirm Reject</button>
                            </form>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center">
                            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                            <p class="text-sm text-gray-400">No audiobooks found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $audiobooks->links() }}
        </div>
    </div>

</div>
@endsection
