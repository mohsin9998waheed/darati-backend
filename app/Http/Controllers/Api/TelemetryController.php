<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlaybackEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TelemetryController extends Controller
{
    /**
     * Known event names. Anything outside this list is rejected with 422
     * so rogue clients can't pollute the table with junk event names.
     */
    private const KNOWN_EVENTS = [
        'playback_requested',
        'signed_url_fetched',
        'audio_source_loaded',
        'playback_started',
        'playback_error',
        'url_refresh_attempted',
        'url_refresh_success',
        'url_refresh_failed',
    ];

    /**
     * POST /telemetry/playback
     *
     * Fire-and-forget from the mobile app. We store whatever passes validation
     * and return 204. Any event that fails validation is silently dropped with
     * a 422 — the client never retries telemetry, so we don't need a retry queue.
     */
    public function playback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event'             => ['required', 'string', Rule::in(self::KNOWN_EVENTS)],
            'ts'                => ['nullable', 'string', 'max:32'],
            'device_os'         => ['nullable', 'string', 'max:16'],
            'device_os_version' => ['nullable', 'string', 'max:32'],
            'device_model'      => ['nullable', 'string', 'max:64'],
            'network_type'      => ['nullable', 'string', 'max:16'],
            'episode_id'        => ['nullable'],
            'audiobook_id'      => ['nullable'],
            'elapsed_ms'        => ['nullable', 'integer', 'min:0', 'max:300000'],
            // All other fields land in meta — no fixed schema enforced here
        ]);

        // Pull out the structured columns; everything else goes into meta.
        $structured = [
            'user_id'           => $request->user()->id,
            'event'             => $validated['event'],
            'ts'                => $validated['ts'] ?? null,
            'device_os'         => $validated['device_os'] ?? 'unknown',
            'device_os_version' => $validated['device_os_version'] ?? 'unknown',
            'device_model'      => $validated['device_model'] ?? 'unknown',
            'network_type'      => $validated['network_type'] ?? 'unknown',
            'episode_id'        => isset($validated['episode_id'])
                                       ? (string) $validated['episode_id']
                                       : null,
            'audiobook_id'      => isset($validated['audiobook_id'])
                                       ? (string) $validated['audiobook_id']
                                       : null,
            'elapsed_ms'        => $validated['elapsed_ms'] ?? null,
        ];

        // Anything the client sent that isn't a structured column becomes meta.
        $metaKeys = array_diff(array_keys($request->all()), array_keys($structured), ['ts']);
        $meta     = count($metaKeys) > 0
            ? array_intersect_key($request->all(), array_flip($metaKeys))
            : null;

        PlaybackEvent::create(array_merge($structured, ['meta' => $meta]));

        // Emit a structured log line so Papertrail / CloudWatch can pick it up
        // for real-time SLO alerting without requiring a DB query.
        Log::channel('stderr')->info('telemetry.playback', array_filter([
            'event'      => $structured['event'],
            'user_id'    => $structured['user_id'],
            'episode_id' => $structured['episode_id'],
            'elapsed_ms' => $structured['elapsed_ms'],
            'os'         => $structured['device_os'],
            'net'        => $structured['network_type'],
        ]));

        return response()->json(null, 204);
    }
}
