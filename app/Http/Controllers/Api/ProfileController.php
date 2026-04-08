<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * GET /api/profile
     * Returns the authenticated user with computed avatar_url.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json($this->userPayload($user));
    }

    /**
     * POST /api/profile
     * Update name, bio, or avatar (multipart/form-data).
     * Omit any field to leave it unchanged.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'   => ['sometimes', 'string', 'max:100'],
            'bio'    => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($request->hasFile('avatar')) {
            $s3 = app(S3Service::class);
            // Remove old avatar to free S3 space
            if ($user->avatar) {
                $s3->delete($user->avatar);
            }
            $data['avatar'] = $s3->upload($request->file('avatar'), 'avatars');
        }

        if (array_key_exists('bio', $data) && $data['bio'] === null) {
            $data['bio'] = null;
        }

        $user->update($data);

        return response()->json($this->userPayload($user->fresh()));
    }

    /**
     * POST /api/profile/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /**
     * GET /api/profile/stats
     * Books listened, favorites count, reviews written.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $booksListened = $user->listens()
            ->selectRaw('COUNT(DISTINCT episode_id) as cnt')
            ->value('cnt') ?? 0;

        $favorites = $user->favorites()->count();
        $reviews   = $user->ratings()->count();

        return response()->json([
            'books_listened' => (int) $booksListened,
            'favorites'      => (int) $favorites,
            'reviews'        => (int) $reviews,
        ]);
    }

    private function userPayload($user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'bio'        => $user->bio,
            'avatar_url' => $user->avatar_url,
            'is_active'  => $user->is_active,
        ];
    }
}
