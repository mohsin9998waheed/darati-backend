<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Episode;
use App\Models\Listen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListenController extends Controller
{
    /** Max seconds credited toward total play time per API call (client syncs ~every 10s). */
    private const PLAY_TIME_CREDIT_CAP = 60;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'episode_id'       => ['required', 'exists:episodes,id'],
            'progress_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $episode = Episode::with('chapter.audiobook')->findOrFail($data['episode_id']);
        $audiobookId = $episode->chapter->audiobook_id;

        $existing = Listen::where('user_id', Auth::id())
            ->where('episode_id', $data['episode_id'])
            ->first();

        $oldProgress = $existing?->progress_seconds ?? 0;
        $wasCompleted = $existing?->completed ?? false;

        $maxProgress = $episode->duration_seconds > 0
            ? $episode->duration_seconds
            : $data['progress_seconds'];
        $newProgress = min($data['progress_seconds'], $maxProgress);

        $completed = $episode->duration_seconds > 0
            && $newProgress >= (int) floor($episode->duration_seconds * 0.90);

        // Completion is sticky — once a user marks an episode done, never un-done it
        // (so seeking back to 0 doesn't wipe the completion flag or re-count a listen).
        $shouldBeCompleted = $completed || $wasCompleted;

        Listen::updateOrCreate(
            ['user_id' => Auth::id(), 'episode_id' => $data['episode_id']],
            ['progress_seconds' => $newProgress, 'completed' => $shouldBeCompleted]
        );

        // Approximate listening time: positive progress delta, capped per request.
        $delta = max(0, $newProgress - $oldProgress);
        if ($delta > 0) {
            $credited = min($delta, self::PLAY_TIME_CREDIT_CAP);
            if ($episode->duration_seconds > 0) {
                $remaining = max(0, $episode->duration_seconds - $oldProgress);
                $credited = min($credited, $remaining);
            }
            if ($credited > 0) {
                Audiobook::where('id', $audiobookId)->increment('total_play_seconds', $credited);
            }
        }

        // Count one "listen" per user+episode the first time they reach ~complete
        // or once they have played at least 60 seconds (handles unknown duration).
        $countListen = false;
        if ($episode->duration_seconds > 0) {
            $countListen = $completed && ! $wasCompleted;
        } elseif (! $wasCompleted && $newProgress >= 60) {
            $countListen = true;
        }
        if ($countListen) {
            Audiobook::where('id', $audiobookId)->increment('total_listens');
        }

        $listen = Listen::where('user_id', Auth::id())
            ->where('episode_id', $data['episode_id'])
            ->first();

        return response()->json($listen);
    }

    public function progress(Request $request): JsonResponse
    {
        if ($episodeId = $request->get('episode_id')) {
            $listen = Listen::where('user_id', Auth::id())
                ->where('episode_id', $episodeId)
                ->first();

            return response()->json($listen);
        }

        $listens = Listen::where('user_id', Auth::id())
            ->with(['episode.chapter.audiobook'])
            ->latest('updated_at')
            ->paginate(20);

        return response()->json($listens);
    }
}
