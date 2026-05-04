<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // ── Pre-flight: check account state BEFORE granting a session ─────────
        // Auth::attempt() logs the user in immediately. Checking is_active after
        // the fact means there is a brief window where a deactivated user holds a
        // valid session if logout() were to fail. We avoid that by fetching the
        // user first and short-circuiting before any session is touched.
        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if ($user && ! $user->is_active) {
            // Return the same generic message — don't confirm the account exists.
            return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return match (Auth::user()->role) {
                'admin'  => redirect()->route('admin.dashboard'),
                'artist' => redirect()->route('artist.dashboard'),
                default  => tap(redirect()->route('login')->withErrors(['email' => 'No web panel access for this account.']), fn () => Auth::logout()),
            };
        }

        return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
