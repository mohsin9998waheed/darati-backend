@extends('layouts.admin')
@section('title', 'Comments')
@section('content')
<div class="space-y-4">
    @if ($flaggedCount > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
        <p class="text-sm text-red-700 font-medium">{{ $flaggedCount }} flagged comment(s) need attention.</p>
        <a href="{{ request()->fullUrlWithQuery(['flagged' => '1']) }}" class="ml-auto text-xs text-red-700 underline">View flagged</a>
    </div>
    @endif
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">User</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">Comment</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600 hidden md:table-cell">Audiobook</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600 hidden lg:table-cell">Date</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($comments as $comment)
                    <tr class="{{ $comment->is_flagged ? 'bg-red-50' : 'hover:bg-surface-50' }}">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <img src="{{ $comment->user->avatar_url }}" alt="" class="w-7 h-7 rounded-full">
                                <span class="font-medium text-gray-900 text-xs">{{ $comment->user->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 max-w-xs">
                            <p class="text-gray-700 text-xs line-clamp-2">{{ $comment->body }}</p>
                            @if ($comment->is_flagged) <span class="text-xs text-red-500 font-medium">Flagged</span> @endif
                        </td>
                        <td class="px-5 py-3 hidden md:table-cell text-xs text-gray-500">{{ $comment->audiobook->title }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('admin.comments.flag', $comment) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border {{ $comment->is_flagged ? 'border-gray-200 text-gray-600' : 'border-amber-200 text-amber-600' }} hover:bg-gray-50">
                                        {{ $comment->is_flagged ? 'Unflag' : 'Flag' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}" onsubmit="return confirm('Delete comment?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400">No comments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">{{ $comments->links() }}</div>
    </div>
</div>
@endsection
