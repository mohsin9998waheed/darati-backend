<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlaybackEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only analytics endpoints built on the playback_events table (P2).
 *
 * All routes are protected by auth:sanctum + role:admin middleware.
 *
 * Endpoints:
 *   GET /admin/analytics/overview   — event counts + TTFA percentiles
 *   GET /admin/analytics/devices    — breakdown by OS + network type
 *   GET /admin/analytics/episodes   — top episodes by play count
 *
 * Query params (all endpoints):
 *   days  — look-back window in days (default: 7, max: 90)
 */
class AnalyticsController extends Controller
{
    private const MAX_DAYS = 90;

    // ── Overview ──────────────────────────────────────────────────────────────

    public function overview(Request $request): JsonResponse
    {
        $days  = $this->days($request);
        $since = now()->subDays($days);

        // ── Event counts ──────────────────────────────────────────────────────
        $counts = PlaybackEvent::where('created_at', '>=', $since)
            ->select('event', DB::raw('count(*) as total'))
            ->groupBy('event')
            ->pluck('total', 'event');

        $plays   = (int) ($counts['playback_started']  ?? 0);
        $errors  = (int) ($counts['playback_error']    ?? 0);
        $requests = (int) ($counts['playback_requested'] ?? 0);

        $errorRate = $requests > 0
            ? round(($errors / $requests) * 100, 2)
            : 0.0;

        // ── TTFA percentiles (audio_source_loaded elapsed_ms) ─────────────────
        $ttfaRows = PlaybackEvent::where('event', 'audio_source_loaded')
            ->where('created_at', '>=', $since)
            ->whereNotNull('elapsed_ms')
            ->orderBy('elapsed_ms')
            ->pluck('elapsed_ms')
            ->toArray();

        $ttfa = $this->percentiles($ttfaRows, [50, 75, 90, 95, 99]);

        // ── URL-fetch latency percentiles (signed_url_fetched elapsed_ms) ─────
        $urlRows = PlaybackEvent::where('event', 'signed_url_fetched')
            ->where('created_at', '>=', $since)
            ->whereNotNull('elapsed_ms')
            ->orderBy('elapsed_ms')
            ->pluck('elapsed_ms')
            ->toArray();

        $urlLatency = $this->percentiles($urlRows, [50, 90, 99]);

        // ── Mid-session refresh rate ──────────────────────────────────────────
        $refreshAttempts = (int) ($counts['url_refresh_attempted'] ?? 0);
        $refreshSuccess  = (int) ($counts['url_refresh_success']   ?? 0);
        $refreshRate = $plays > 0
            ? round(($refreshAttempts / $plays) * 100, 2)
            : 0.0;

        return response()->json([
            'period_days'      => $days,
            'event_counts'     => $counts,
            'plays'            => $plays,
            'errors'           => $errors,
            'error_rate_pct'   => $errorRate,
            'ttfa_ms'          => $ttfa,
            'url_latency_ms'   => $urlLatency,
            'refresh_attempts' => $refreshAttempts,
            'refresh_success'  => $refreshSuccess,
            'refresh_rate_pct' => $refreshRate,
        ]);
    }

    // ── Device & network breakdown ────────────────────────────────────────────

    public function devices(Request $request): JsonResponse
    {
        $days  = $this->days($request);
        $since = now()->subDays($days);

        $base = PlaybackEvent::where('event', 'playback_started')
            ->where('created_at', '>=', $since);

        $byOs = (clone $base)
            ->select('device_os', DB::raw('count(*) as plays'))
            ->groupBy('device_os')
            ->orderByDesc('plays')
            ->get();

        $byNetwork = (clone $base)
            ->select('network_type', DB::raw('count(*) as plays'))
            ->groupBy('network_type')
            ->orderByDesc('plays')
            ->get();

        // Error rate broken down by OS
        $errorsByOs = PlaybackEvent::where('event', 'playback_error')
            ->where('created_at', '>=', $since)
            ->select('device_os', DB::raw('count(*) as errors'))
            ->groupBy('device_os')
            ->pluck('errors', 'device_os');

        // TTFA median by OS
        $ttfaByOs = [];
        foreach ($byOs->pluck('device_os') as $os) {
            $rows = PlaybackEvent::where('event', 'audio_source_loaded')
                ->where('device_os', $os)
                ->where('created_at', '>=', $since)
                ->whereNotNull('elapsed_ms')
                ->orderBy('elapsed_ms')
                ->pluck('elapsed_ms')
                ->toArray();
            $ttfaByOs[$os] = $this->percentile($rows, 50);
        }

        return response()->json([
            'period_days'    => $days,
            'by_os'          => $byOs->map(fn ($r) => [
                'os'          => $r->device_os,
                'plays'       => $r->plays,
                'errors'      => (int) ($errorsByOs[$r->device_os] ?? 0),
                'ttfa_p50_ms' => $ttfaByOs[$r->device_os] ?? null,
            ]),
            'by_network'     => $byNetwork,
        ]);
    }

    // ── Top episodes ──────────────────────────────────────────────────────────

    public function episodes(Request $request): JsonResponse
    {
        $days  = $this->days($request);
        $since = now()->subDays($days);
        $limit = min((int) $request->get('limit', 20), 50);

        $top = PlaybackEvent::where('event', 'playback_started')
            ->where('created_at', '>=', $since)
            ->whereNotNull('episode_id')
            ->select('episode_id', DB::raw('count(*) as plays'))
            ->groupBy('episode_id')
            ->orderByDesc('plays')
            ->limit($limit)
            ->get();

        // Attach error counts for the same episodes
        $episodeIds = $top->pluck('episode_id')->toArray();
        $errors = PlaybackEvent::where('event', 'playback_error')
            ->where('created_at', '>=', $since)
            ->whereIn('episode_id', $episodeIds)
            ->select('episode_id', DB::raw('count(*) as errors'))
            ->groupBy('episode_id')
            ->pluck('errors', 'episode_id');

        return response()->json([
            'period_days' => $days,
            'episodes'    => $top->map(fn ($r) => [
                'episode_id' => $r->episode_id,
                'plays'      => $r->plays,
                'errors'     => (int) ($errors[$r->episode_id] ?? 0),
            ]),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function days(Request $request): int
    {
        return min((int) $request->get('days', 7), self::MAX_DAYS);
    }

    /**
     * Compute multiple percentiles from a pre-sorted array of integers.
     * Returns null for each percentile if the array is empty.
     *
     * @param  int[]   $sorted     Already sorted values
     * @param  int[]   $percentiles e.g. [50, 90, 95]
     * @return array<string, int|null>
     */
    private function percentiles(array $sorted, array $percentiles): array
    {
        $result = [];
        foreach ($percentiles as $p) {
            $result["p{$p}"] = $this->percentile($sorted, $p);
        }
        return $result;
    }

    private function percentile(array $sorted, int $p): ?int
    {
        $n = count($sorted);
        if ($n === 0) return null;

        $idx = (int) ceil($p / 100 * $n) - 1;
        return $sorted[max(0, min($idx, $n - 1))];
    }
}
