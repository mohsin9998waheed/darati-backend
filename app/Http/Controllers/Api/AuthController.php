<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name'     => ['required', 'string', 'max:100'],
                'email'    => ['required', 'email', 'unique:users,email'],
                'password' => ['required', Password::min(8)],
            ]);

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
        try {
            $credentials = $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required'],
            ]);

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
