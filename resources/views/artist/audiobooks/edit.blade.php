@extends('layouts.artist')
@section('title', 'Edit Audiobook')
@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-5">Edit: {{ $audiobook->title }}</h2>

        @if (session('success'))
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                <p class="font-semibold mb-1">Please fix these errors:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('artist.audiobooks.update', $audiobook) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $audiobook->title) }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Author Name</label>
                <input type="text" name="author_name" value="{{ old('author_name', $audiobook->author_name) }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="e.g. Dr. Waseem Barelvi">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="4" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none">{{ old('description', $audiobook->description) }}</textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">No category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $audiobook->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                    <select name="language" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="en" {{ old('language', $audiobook->language) == 'en' ? 'selected' : '' }}>English</option>
                        <option value="ur" {{ old('language', $audiobook->language) == 'ur' ? 'selected' : '' }}>Urdu (اردو)</option>
                        <option value="ar" {{ old('language', $audiobook->language) == 'ar' ? 'selected' : '' }}>Arabic</option>
                        <option value="fr" {{ old('language', $audiobook->language) == 'fr' ? 'selected' : '' }}>French</option>
                        <option value="es" {{ old('language', $audiobook->language) == 'es' ? 'selected' : '' }}>Spanish</option>
                        <option value="de" {{ old('language', $audiobook->language) == 'de' ? 'selected' : '' }}>German</option>
                        <option value="other" {{ old('language', $audiobook->language) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>
            @if ($audiobook->thumbnail)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Cover</label>
                <img src="{{ $audiobook->thumbnail_url }}" alt="" class="h-24 w-24 rounded-xl object-cover">
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Cover Image (optional)</label>
                <input type="file" name="thumbnail" accept="image/*" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none">
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700">Update Audiobook</button>
                <a href="{{ route('artist.audiobooks.show', $audiobook) }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
