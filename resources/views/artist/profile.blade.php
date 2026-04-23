@extends('layouts.artist')
@section('title', 'My Profile')
@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- ── Avatar card ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-sm font-semibold text-gray-800 mb-5">Profile Picture</h2>

        <div class="flex items-center gap-6">
            <div class="relative shrink-0">
                <img id="avatar-preview"
                     src="{{ $artist->avatar_url }}"
                     alt="{{ $artist->name }}"
                     class="w-24 h-24 rounded-full object-cover ring-2 ring-gray-100">
                <label for="avatar-input"
                       class="absolute bottom-0 right-0 w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-purple-700 transition shadow">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </label>
            </div>

            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ $artist->name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $artist->email }}</p>
                <p class="text-xs text-gray-400 mt-3">JPG, PNG or WebP · Max 4 MB</p>
                <p class="text-xs text-gray-400">Recommended: 512 × 512 px or larger</p>
            </div>
        </div>

        {{-- Avatar-only form --}}
        <form id="avatar-form"
              method="POST"
              action="{{ route('artist.profile.update') }}"
              enctype="multipart/form-data"
              class="mt-4 hidden">
            @csrf
            {{-- carry over current name & bio so they are not wiped --}}
            <input type="hidden" name="name" value="{{ $artist->name }}">
            <input type="hidden" name="bio"  value="{{ $artist->bio }}">
            <input type="file" id="avatar-input" name="avatar"
                   accept="image/jpeg,image/png,image/webp" class="sr-only">
        </form>
    </div>

    {{-- ── Info card ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-sm font-semibold text-gray-800 mb-5">Personal Information</h2>

        @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('artist.profile.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Display Name</label>
                <input type="text" name="name" value="{{ old('name', $artist->name) }}" required
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Bio <span class="text-gray-400">(optional)</span></label>
                <textarea name="bio" rows="3" maxlength="500"
                          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none"
                          placeholder="Tell listeners about yourself…">{{ old('bio', $artist->bio) }}</textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="px-5 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- ── Change password card ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-sm font-semibold text-gray-800 mb-5">Change Password</h2>

        @if ($errors->hasBag('default') && $errors->has('current_password'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
            {{ $errors->first('current_password') }}
        </div>
        @endif

        <form method="POST" action="{{ route('artist.profile.change-password') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Current Password</label>
                <input type="password" name="current_password" required
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">New Password</label>
                <input type="password" name="password" required
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="px-5 py-2 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-900 transition">
                    Update Password
                </button>
            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
    // Instant avatar preview + auto-submit on file pick
    const input   = document.getElementById('avatar-input');
    const preview = document.getElementById('avatar-preview');
    const form    = document.getElementById('avatar-form');

    input.addEventListener('change', function () {
        if (!this.files.length) return;
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(this.files[0]);
        form.submit();
    });
</script>
@endpush
@endsection
