<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * POST /api/notifications/register-token
     * Upserts the FCM device token for the current user.
     */
    public function registerToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => ['required', 'string', 'max:512'],
            'platform' => ['sometimes', 'in:android,ios'],
        ]);

        DeviceToken::updateOrCreate(
            ['token'   => $data['token']],
            ['user_id' => $request->user()->id,
             'platform' => $data['platform'] ?? 'android'],
        );

        return response()->json(['message' => 'Device token registered.']);
    }

    /**
     * DELETE /api/notifications/deregister-token
     * Removes an FCM token (e.g. on logout).
     */
    public function deregisterToken(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        DeviceToken::where('token', $request->token)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Device token removed.']);
    }
}
