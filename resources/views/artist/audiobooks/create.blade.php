@extends('layouts.artist')
@section('title', 'Upload Audiobook')
@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900">New Audiobook</h2>
            <p class="text-sm text-gray-500 mt-1">Fill in the details below. After saving you can add chapters and episodes.</p>
        </div>

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

        <form method="POST" action="{{ route('artist.audiobooks.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" required placeholder="My Amazing Audiobook">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="4" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none" placeholder="Brief description of this audiobook...">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Select category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Language <span class="text-red-500">*</span></label>
                    <select name="language" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>English</option>
                        <option value="ar" {{ old('language') == 'ar' ? 'selected' : '' }}>Arabic</option>
                        <option value="fr" {{ old('language') == 'fr' ? 'selected' : '' }}>French</option>
                        <option value="es" {{ old('language') == 'es' ? 'selected' : '' }}>Spanish</option>
                        <option value="de" {{ old('language') == 'de' ? 'selected' : '' }}>German</option>
                        <option value="other" {{ old('language') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cover Image</label>
                <div class="flex items-center gap-4">
                    <label class="flex-1 flex flex-col items-center px-4 py-6 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition" x-data="{ preview: null }" @dragover.prevent @drop.prevent="preview = URL.createObjectURL($event.dataTransfer.files[0])">
                        <div x-show="!preview" class="text-center">
                            <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p class="text-xs text-gray-500">Click or drag to upload cover</p>
                            <p class="text-xs text-gray-400">JPG, PNG, WEBP up to 2MB</p>
                        </div>
                        <img x-show="preview" :src="preview" class="h-32 object-cover rounded-lg">
                        <input type="file" name="thumbnail" accept="image/*" class="hidden" @change="preview = URL.createObjectURL($event.target.files[0])">
                    </label>
                </div>
                @error('thumbnail') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700">Create Audiobook</button>
                <a href="{{ route('artist.audiobooks.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
