@extends('layouts.admin')
@section('title', 'Add Banner')
@section('content')
<div class="max-w-2xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Add Banner</h1>
        <p class="text-sm text-gray-500 mt-0.5">Create a new home-screen carousel banner</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-xl space-y-1">
            @foreach ($errors->all() as $err)
                <p>• {{ $err }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
        @csrf

        {{-- Title --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Banner Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                   placeholder="e.g. New Arrivals — Summer 2026">
        </div>

        {{-- Image --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Banner Image</label>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <p class="text-xs text-gray-400 mt-1">JPG/PNG/WebP, max 5 MB. Recommended: 1200×400 px (3:1 ratio).</p>
        </div>

        {{-- Link type --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Link Type</label>
            <select name="link_type" id="link_type"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    onchange="toggleLinkValue(this.value)">
                <option value="static"   {{ old('link_type') === 'static'   ? 'selected' : '' }}>Static (no link)</option>
                <option value="book"     {{ old('link_type') === 'book'     ? 'selected' : '' }}>Audiobook link</option>
                <option value="external" {{ old('link_type') === 'external' ? 'selected' : '' }}>External URL</option>
            </select>
        </div>

        {{-- Book picker --}}
        <div id="book_picker" class="{{ old('link_type', 'static') === 'book' ? '' : 'hidden' }}">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Select Audiobook</label>
            <select name="link_value"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">— choose a book —</option>
                @foreach ($audiobooks as $ab)
                    <option value="{{ $ab->id }}" {{ old('link_value') == $ab->id ? 'selected' : '' }}>
                        {{ $ab->title }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- External URL --}}
        <div id="external_url" class="{{ old('link_type') === 'external' ? '' : 'hidden' }}">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">URL</label>
            <input type="url" name="link_value" value="{{ old('link_type') === 'external' ? old('link_value') : '' }}"
                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="https://example.com">
        </div>

        {{-- Order --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Display Order</label>
            <input type="number" name="order" value="{{ old('order', 0) }}" min="0"
                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="0">
            <p class="text-xs text-gray-400 mt-1">Lower numbers appear first.</p>
        </div>

        {{-- Active --}}
        <div class="flex items-center gap-3">
            <input type="checkbox" id="is_active" name="is_active" value="1" checked
                   class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <label for="is_active" class="text-sm font-medium text-gray-700">Active (visible in app)</label>
        </div>

        {{-- Submit --}}
        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition">
                Create Banner
            </button>
            <a href="{{ route('admin.banners.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-6 py-2.5 rounded-xl transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function toggleLinkValue(type) {
    document.getElementById('book_picker').classList.toggle('hidden', type !== 'book');
    document.getElementById('external_url').classList.toggle('hidden', type !== 'external');
}
</script>
@endsection
