<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * GET /notifications/preferences
     * Returns the authenticated user's notification preferences.
     * Creates a default record (all enabled) if none exists yet.
     */
    public function show(Request $request): JsonResponse
    {
        $prefs = NotificationPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'episode_ready'   => true,
                'new_episode'     => true,
                'resume_reminder' => true,
            ]
        );

        return response()->json([
            'episode_ready'   => $prefs->episode_ready,
            'new_episode'     => $prefs->new_episode,
            'resume_reminder' => $prefs->resume_reminder,
        ]);
    }

    /**
     * PUT /notifications/preferences
     * Updates one or more preference flags. Partial updates supported —
     * only the fields present in the request are changed.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'episode_ready'   => ['sometimes', 'boolean'],
            'new_episode'     => ['sometimes', 'boolean'],
            'resume_reminder' => ['sometimes', 'boolean'],
        ]);

        $prefs = NotificationPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'episode_ready'   => true,
                'new_episode'     => true,
                'resume_reminder' => true,
            ]
        );

        $prefs->update($data);

        return response()->json([
            'episode_ready'   => $prefs->episode_ready,
            'new_episode'     => $prefs->new_episode,
            'resume_reminder' => $prefs->resume_reminder,
        ]);
    }
}
