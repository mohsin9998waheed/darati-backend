<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        // Validation must run outside try/catch — ValidationException is a Throwable
        // and would otherwise be swallowed as a generic 500 "server error".
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
        ]);

        try {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => 'listener',
            ]);

            $token = $user->createToken('api')->plainTextToken;

            Log::info('api.auth.register.success', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['user' => $user, 'token' => $token], 201);
        } catch (QueryException $e) {
            Log::error('api.auth.register.db_error', [
                'message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? null,
                'sql_code' => $e->errorInfo[1] ?? null,
                'ip' => $request->ip(),
                'email' => (string) $request->input('email'),
            ]);

            return response()->json([
                'message' => 'Registration failed due to a database issue.',
            ], 500);
        } catch (Throwable $e) {
            Log::error('api.auth.register.unexpected_error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'ip' => $request->ip(),
                'email' => (string) $request->input('email'),
            ]);

            return response()->json([
                'message' => 'Registration failed due to a server error.',
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        try {
            if (! Auth::attempt($credentials)) {
                Log::warning('api.auth.login.invalid_credentials', [
                    'email' => (string) $request->input('email'),
                    'ip' => $request->ip(),
                ]);
                return response()->json(['message' => 'Invalid credentials.'], 401);
            }

            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                Log::warning('api.auth.login.inactive_user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);
                return response()->json(['message' => 'Account deactivated.'], 403);
            }

            $token = $user->createToken('api')->plainTextToken;
            Log::info('api.auth.login.success', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return response()->json(['user' => $user, 'token' => $token]);
        } catch (Throwable $e) {
            Log::error('api.auth.login.unexpected_error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'ip' => $request->ip(),
                'email' => (string) $request->input('email'),
            ]);

            return response()->json([
                'message' => 'Login failed due to a server error.',
            ], 500);
        }
    }

    public function google(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id_token' => ['required', 'string'],
            ]);

            // Verify token with Google tokeninfo endpoint.
            $verify = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $data['id_token'],
            ]);

            if (! $verify->ok()) {
                Log::warning('api.auth.google.verify_failed', [
                    'status' => $verify->status(),
                    'body' => $verify->body(),
                    'ip' => $request->ip(),
                ]);
                return response()->json(['message' => 'Invalid Google token.'], 401);
            }

            $payload = $verify->json();
            $email = (string) ($payload['email'] ?? '');
            $name = trim((string) ($payload['name'] ?? ''));
            $emailVerified = (string) ($payload['email_verified'] ?? '') === 'true';
            $audience = (string) ($payload['aud'] ?? '');
            $allowedClientIds = config('services.google.allowed_client_ids', []);

            if ($email === '' || ! $emailVerified) {
                return response()->json(['message' => 'Google account email is not verified.'], 401);
            }

            if (! empty($allowedClientIds) && ! in_array($audience, $allowedClientIds, true)) {
                Log::warning('api.auth.google.invalid_audience', [
                    'aud' => $audience,
                    'allowed' => $allowedClientIds,
                    'ip' => $request->ip(),
                ]);
                return response()->json(['message' => 'Google token audience mismatch.'], 401);
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name !== '' ? $name : strstr($email, '@', true),
                    // Random hashed password because auth is delegated to Google.
                    'password' => Hash::make(bin2hex(random_bytes(24))),
                    'role' => 'listener',
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->is_active) {
                return response()->json(['message' => 'Account deactivated.'], 403);
            }

            $token = $user->createToken('api')->plainTextToken;

            Log::info('api.auth.google.success', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return response()->json(['user' => $user, 'token' => $token]);
        } catch (Throwable $e) {
            Log::error('api.auth.google.error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Google login failed due to server error.'], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('audiobooks:id,title,status,artist_id'));
    }
}
