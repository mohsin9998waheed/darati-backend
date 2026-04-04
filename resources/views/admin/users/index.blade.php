@extends('layouts.admin')
@section('title', 'Users')
@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row gap-3">
        <form method="GET" class="flex gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..." class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <select name="role" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Roles</option>
                <option value="artist" {{ request('role') === 'artist' ? 'selected' : '' }}>Artist</option>
                <option value="listener" {{ request('role') === 'listener' ? 'selected' : '' }}>Listener</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">Search</button>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">User</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600 hidden sm:table-cell">Role</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600 hidden md:table-cell">Books</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600 hidden lg:table-cell">Joined</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">Status</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($users as $user)
                    <tr class="hover:bg-surface-50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $user->avatar_url }}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 hidden sm:table-cell">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $user->role === 'artist' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ ucfirst($user->role) }}</span>
                        </td>
                        <td class="px-5 py-3 hidden md:table-cell text-gray-600">{{ $user->audiobooks_count ?? 0 }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell text-gray-500 text-xs">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Active' : 'Banned' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border {{ $user->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-green-200 text-green-600 hover:bg-green-50' }}">
                                        {{ $user->is_active ? 'Ban' : 'Activate' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">{{ $users->links() }}</div>
    </div>
</div>
@endsection
