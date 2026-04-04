@extends('layouts.admin')
@section('title', 'Categories')
@section('content')
<div class="space-y-4">
    <div class="flex justify-end">
        <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Category
        </a>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Name</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600 hidden sm:table-cell">Slug</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600 hidden md:table-cell">Books</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($categories as $category)
                <tr class="hover:bg-surface-50">
                    <td class="px-5 py-3 font-medium text-gray-900">
                        @if ($category->icon) <span class="mr-1">{{ $category->icon }}</span> @endif
                        {{ $category->name }}
                    </td>
                    <td class="px-5 py-3 hidden sm:table-cell text-gray-500 text-xs font-mono">{{ $category->slug }}</td>
                    <td class="px-5 py-3 hidden md:table-cell text-gray-600">{{ $category->audiobooks_count }}</td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Edit</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-gray-100">{{ $categories->links() }}</div>
    </div>
</div>
@endsection
