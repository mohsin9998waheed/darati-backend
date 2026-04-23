<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Services\S3Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('artist.profile', ['artist' => Auth::user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $artist = Auth::user();

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:100'],
            'bio'    => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($request->hasFile('avatar')) {
            $s3 = app(S3Service::class);
            if ($artist->avatar) {
                $s3->delete($artist->avatar);
            }
            $data['avatar'] = $s3->uploadAvatar($request->file('avatar'));
        }

        $artist->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $artist = Auth::user();

        if (! Hash::check($request->current_password, $artist->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $artist->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password updated successfully.');
    }
}
