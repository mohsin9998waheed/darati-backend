@extends('layouts.admin')
@section('title', 'Audiobooks')
@section('content')
<div class="space-y-4">
    @if ($pendingCount > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p class="text-sm text-amber-700 font-medium">{{ $pendingCount }} audiobook(s) awaiting your review.</p>
        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}" class="ml-auto text-xs text-amber-700 underline hover:no-underline">View pending</a>
    </div>
    @endif
    <div class="flex flex-col sm:flex-row gap-3">
        <form method="GET" class="flex gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search audiobooks..." class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <select name="status" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">Filter</button>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">Book</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600 hidden sm:table-cell">Artist</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600 hidden md:table-cell">Category</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">Status</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($audiobooks as $book)
                    <tr class="hover:bg-surface-50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                                    @if ($book->thumbnail)
                                        <img src="{{ $book->thumbnail_url }}" alt="" class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ $book->title }}</p>
                                    <p class="text-xs text-gray-500">{{ $book->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 hidden sm:table-cell text-gray-700">{{ $book->artist->name }}</td>
                        <td class="px-5 py-3 hidden md:table-cell text-gray-500 text-xs">{{ $book->category?->name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @if ($book->isPending())
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>
                            @elseif ($book->isApproved())
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Approved</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rejected</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.audiobooks.show', $book) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">View</a>
                                @if ($book->isPending())
                                <form method="POST" action="{{ route('admin.audiobooks.approve', $book) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs px-3 py-1.5 rounded-lg bg-green-600 text-white hover:bg-green-700">Approve</button>
                                </form>
                                @endif
                                @if (!$book->isRejected())
                                <button type="button" onclick="document.getElementById('reject-{{ $book->id }}').classList.remove('hidden')" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50">Reject</button>
                                @endif
                            </div>
                            <form id="reject-{{ $book->id }}" method="POST" action="{{ route('admin.audiobooks.reject', $book) }}" class="hidden mt-2">
                                @csrf @method('PATCH')
                                <input type="text" name="rejection_reason" placeholder="Reason (optional)" class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg mb-1">
                                <button type="submit" class="text-xs px-3 py-1.5 bg-red-600 text-white rounded-lg w-full">Confirm Reject</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400">No audiobooks found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">{{ $audiobooks->links() }}</div>
    </div>
</div>
@endsection
