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
    /**
     * Max seconds credited toward total_play_seconds per API call.
     * The mobile client syncs every 10 seconds, so a normal delta is ~10s.
     * The cap (60s) absorbs seek-forward jumps without inflating the counter.
     */
    private const PLAY_TIME_CREDIT_CAP = 60;

    /**
     * Episode completion threshold (90%) — sticky "completed" flag.
     */
    private const COMPLETION_THRESHOLD = 0.90;

    /**
     * Cycle threshold for single-episode books (75%).
     * A user must hear at least this fraction of the one episode to count as a cycle.
     * Multi-chapter books use a different rule — see _maybeCreditCycle().
     */
    private const CYCLE_THRESHOLD_SINGLE = 0.75;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'episode_id'       => ['required', 'exists:episodes,id'],
            'progress_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $episode     = Episode::with('chapter.audiobook')->findOrFail($data['episode_id']);
        $audiobookId = $episode->chapter->audiobook_id;
        $chapterId   = $episode->chapter_id;
        $userId      = Auth::id();

        // ── Snapshot the pre-save state ───────────────────────────────────────
        $existing     = Listen::where('user_id', $userId)
                               ->where('episode_id', $data['episode_id'])
                               ->first();
        $oldProgress  = $existing?->progress_seconds ?? 0;
        $wasCompleted = $existing?->completed ?? false;

        // ── Clamp newProgress to episode duration (if known) ──────────────────
        // BUG FIX: when duration_seconds = 0 the previous code used the raw client
        // value as the cap, meaning a rogue client could send progress=99999 and
        // skip the remaining-duration guard entirely on every sync for that episode.
        // Now we never credit more than PLAY_TIME_CREDIT_CAP regardless of duration.
        $knownDuration = $episode->duration_seconds > 0;
        $newProgress   = $knownDuration
            ? min($data['progress_seconds'], $episode->duration_seconds)
            : $data['progress_seconds'];

        // ── Completion flag (sticky — never un-completed after seeking back) ───
        $completed        = $knownDuration
            && $newProgress >= (int) floor($episode->duration_seconds * self::COMPLETION_THRESHOLD);
        $shouldBeCompleted = $completed || $wasCompleted;

        Listen::updateOrCreate(
            ['user_id' => $userId, 'episode_id' => $data['episode_id']],
            ['progress_seconds' => $newProgress, 'completed' => $shouldBeCompleted]
        );

        // ── Credit total_play_seconds ─────────────────────────────────────────
        // delta = forward progress only (rewinds/seeks-back contribute 0).
        // Cap 1: PLAY_TIME_CREDIT_CAP — absorbs seek-forward jumps.
        // Cap 2: remaining duration from oldProgress — prevents crediting past end.
        //        This cap applies only when duration is known; unknown-duration
        //        episodes fall back to Cap 1 alone (still safe).
        $delta = max(0, $newProgress - $oldProgress);
        if ($delta > 0) {
            $credited = min($delta, self::PLAY_TIME_CREDIT_CAP);
            if ($knownDuration) {
                $remaining = max(0, $episode->duration_seconds - $oldProgress);
                $credited  = min($credited, $remaining);
            }
            if ($credited > 0) {
                Audiobook::where('id', $audiobookId)->increment('total_play_seconds', $credited);
            }
        }

        // ── Credit total_listens (once per user per audiobook) ────────────────
        // Triggered on the very first listen event for any episode in this book.
        if ($existing === null) {
            $alreadyListenedBook = Listen::where('user_id', $userId)
                ->where('episode_id', '!=', $data['episode_id'])
                ->whereHas('episode.chapter', fn ($q) => $q->where('audiobook_id', $audiobookId))
                ->exists();

            if (! $alreadyListenedBook) {
                Audiobook::where('id', $audiobookId)->increment('total_listens');
            }
        }

        // ── Credit total_cycles ───────────────────────────────────────────────
        $this->maybeCreditCycle(
            userId:       $userId,
            audiobookId:  $audiobookId,
            chapterId:    $chapterId,
            episode:      $episode,
            episodeId:    $data['episode_id'],
            oldProgress:  $oldProgress,
            newProgress:  $newProgress,
            existing:     $existing,
            knownDuration: $knownDuration,
        );

        $listen = Listen::where('user_id', $userId)
            ->where('episode_id', $data['episode_id'])
            ->first();

        return response()->json($listen);
    }

    /**
     * Increment total_cycles on the audiobook when a cycle is completed.
     *
     * Definition:
     *
     *  Single-episode book (exactly 1 chapter with exactly 1 episode):
     *    A cycle is earned the first time a unique user's progress crosses the
     *    CYCLE_THRESHOLD_SINGLE (75%) mark. The crossing is detected by comparing
     *    oldProgress < threshold ≤ newProgress so it fires exactly once even if
     *    the client sends many syncs near that boundary.
     *
     *  Multi-chapter / multi-episode book:
     *    A cycle is earned the first time a unique user sends ANY listen event
     *    for any episode belonging to a given chapter. Each (user × chapter) pair
     *    counts as one cycle, so a user working through 3 chapters earns 3 cycles.
     *    The guard ensures we only fire on the very first episode-listen for that
     *    chapter (before the Listen row exists for any sibling episode).
     */
    private function maybeCreditCycle(
        int     $userId,
        int     $audiobookId,
        int     $chapterId,
        Episode $episode,
        int     $episodeId,
        int     $oldProgress,
        int     $newProgress,
        ?Listen $existing,
        bool    $knownDuration,
    ): void {
        // Determine book structure: count chapters in this audiobook.
        // We scope to the audiobook already loaded on the episode relation.
        $chapterCount = $episode->chapter->audiobook->chapters()->count();

        // Count episodes across ALL chapters of this audiobook.
        $episodeCount = Episode::whereHas(
            'chapter',
            fn ($q) => $q->where('audiobook_id', $audiobookId)
        )->count();

        $isSingleEpisodeBook = ($chapterCount === 1 && $episodeCount === 1);

        if ($isSingleEpisodeBook) {
            // ── Single-episode rule: 75% threshold crossing ───────────────────
            if (! $knownDuration) {
                // Can't determine threshold without known duration — skip.
                return;
            }

            $thresholdSeconds = (int) floor($episode->duration_seconds * self::CYCLE_THRESHOLD_SINGLE);

            $wasBelow = $oldProgress < $thresholdSeconds;
            $isAbove  = $newProgress >= $thresholdSeconds;

            if ($wasBelow && $isAbove) {
                // First time this user crossed 75% — count one cycle.
                Audiobook::where('id', $audiobookId)->increment('total_cycles');
            }

        } else {
            // ── Multi-chapter rule: first listen for this (user × chapter) ────
            // $existing is null ↔ this is the first Listen row for this user+episode.
            // We additionally verify no Listen row exists for any OTHER episode
            // in the same chapter, so we only fire once per chapter per user.
            if ($existing !== null) {
                return; // Already had a row for this episode — chapter already counted.
            }

            $alreadyListenedChapter = Listen::where('user_id', $userId)
                ->where('episode_id', '!=', $episodeId)
                ->whereHas('episode', fn ($q) => $q->where('chapter_id', $chapterId))
                ->exists();

            if (! $alreadyListenedChapter) {
                Audiobook::where('id', $audiobookId)->increment('total_cycles');
            }
        }
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
