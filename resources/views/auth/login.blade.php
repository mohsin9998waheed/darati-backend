<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In – Darati</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        .bg-scene {
            position: fixed;
            inset: 0;
            background-image: url('/splash_bg.jpeg');
            background-size: cover;
            background-position: center;
            z-index: 0;
        }

        /* Multi-layer overlay: deep tint + bottom vignette */
        .bg-scene::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                rgba(5, 10, 20, 0.82) 0%,
                rgba(10, 20, 40, 0.72) 50%,
                rgba(5, 10, 20, 0.88) 100%
            );
        }
        .bg-scene::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 60% 0%, rgba(29,185,84,0.12) 0%, transparent 65%),
                        radial-gradient(ellipse at 10% 100%, rgba(29,185,84,0.08) 0%, transparent 55%);
        }

        /* Glassmorphism card */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(24px) saturate(160%);
            -webkit-backdrop-filter: blur(24px) saturate(160%);
            border: 1px solid rgba(255, 255, 255, 0.10);
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.04) inset,
                0 32px 64px -12px rgba(0,0,0,0.6),
                0 8px 24px -4px rgba(0,0,0,0.4);
        }

        /* Input styling */
        .auth-input {
            width: 100%;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            color: #fff;
            font-size: 0.9rem;
            padding: 0.7rem 1rem;
            outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .auth-input::placeholder { color: rgba(255,255,255,0.3); }
        .auth-input:focus {
            border-color: rgba(29,185,84,0.7);
            background: rgba(255,255,255,0.10);
            box-shadow: 0 0 0 3px rgba(29,185,84,0.15);
        }
        .auth-input:-webkit-autofill,
        .auth-input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px rgba(20,32,50,0.95) inset, 0 0 0 3px rgba(29,185,84,0.15);
            -webkit-text-fill-color: #fff;
        }

        /* Password wrapper */
        .pw-wrap { position: relative; }
        .pw-wrap .auth-input { padding-right: 2.75rem; }
        .pw-toggle {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: rgba(255,255,255,0.35);
            display: flex;
            align-items: center;
            padding: 0;
            transition: color 0.15s;
        }
        .pw-toggle:hover { color: rgba(255,255,255,0.7); }

        /* Submit button */
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #1DB954 0%, #17a349 100%);
            color: #fff;
            font-weight: 600;
            font-size: 0.92rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            letter-spacing: 0.01em;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(29,185,84,0.35);
            font-family: 'Inter', sans-serif;
        }
        .btn-primary:hover  { opacity: 0.92; box-shadow: 0 6px 24px rgba(29,185,84,0.45); transform: translateY(-1px); }
        .btn-primary:active { opacity: 1; transform: translateY(0); box-shadow: 0 2px 10px rgba(29,185,84,0.3); }

        /* Remember checkbox */
        .custom-check { accent-color: #1DB954; width: 1rem; height: 1rem; cursor: pointer; }

        /* Label */
        .field-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 500;
            color: rgba(255,255,255,0.55);
            margin-bottom: 0.4rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        /* Error banner */
        .error-banner {
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
        }

        /* Divider */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
        }

        /* Subtle animated glow behind logo */
        .logo-glow {
            position: absolute;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(29,185,84,0.4) 0%, transparent 70%);
            filter: blur(20px);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: pulse-glow 3s ease-in-out infinite alternate;
        }
        @keyframes pulse-glow {
            from { opacity: 0.5; transform: translate(-50%, -50%) scale(0.9); }
            to   { opacity: 0.9; transform: translate(-50%, -50%) scale(1.1); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative">

    {{-- Background scene --}}
    <div class="bg-scene"></div>

    {{-- Content layer --}}
    <div class="relative z-10 w-full max-w-sm">

        {{-- Logo block --}}
        <div class="text-center mb-8">
            <div class="relative inline-block mb-5">
                <div class="logo-glow"></div>
                <div class="relative w-20 h-20 rounded-[22px] overflow-hidden ring-2 ring-white/10 shadow-2xl mx-auto"
                     style="background: rgba(255,255,255,0.06); backdrop-filter: blur(12px);">
                    <img src="/icon.png" alt="Darati" class="w-full h-full object-contain p-2">
                </div>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight leading-none">Darati</h1>
            <p class="text-sm mt-2" style="color: rgba(255,255,255,0.40); letter-spacing: 0.04em; text-transform: uppercase; font-size: 0.72rem; font-weight: 500;">
                Admin &amp; Artist Portal
            </p>
        </div>

        {{-- Card --}}
        <div class="glass-card rounded-2xl p-7">
            <h2 class="text-lg font-semibold text-white mb-1">Welcome back</h2>
            <p class="text-sm mb-6" style="color: rgba(255,255,255,0.38);">Sign in to your account to continue</p>

            @if ($errors->any())
                <div class="error-banner mb-5">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm" style="color: rgba(252,165,165,1);">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="field-label">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        value="{{ old('email') }}"
                        class="auth-input"
                        placeholder="you@example.com"
                    >
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="field-label">Password</label>
                    <div class="pw-wrap">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="auth-input"
                            placeholder="••••••••"
                        >
                        <button type="button" class="pw-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            {{-- Eye open (shown when password is hidden) --}}
                            <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            {{-- Eye off (shown when password is visible) --}}
                            <svg id="eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember --}}
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input id="remember" name="remember" type="checkbox" class="custom-check">
                        <span class="text-sm" style="color: rgba(255,255,255,0.5);">Remember me</span>
                    </label>
                </div>

                <div class="divider"></div>

                <button type="submit" class="btn-primary">
                    Sign in →
                </button>
            </form>
        </div>

        <p class="text-center mt-6 text-xs" style="color: rgba(255,255,255,0.22);">
            &copy; {{ date('Y') }} Darati. All rights reserved.
        </p>
    </div>

    <script>
        function togglePassword() {
            const input   = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeOff  = document.getElementById('eye-off');
            const isHidden = input.type === 'password';

            input.type       = isHidden ? 'text' : 'password';
            eyeOpen.style.display = isHidden ? 'none'  : 'block';
            eyeOff.style.display  = isHidden ? 'block' : 'none';
        }
    </script>

</body>
</html>
