<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EpisodeStreamController extends Controller
{
    /**
     * Resolve a correlation ID for this request.
     *
     * Prefers the X-Request-Id header set by upstream proxies (Railway, CloudFront, etc.).
     * Falls back to a fresh UUID so every response is always traceable.
     */
    private function correlationId(Request $request): string
    {
        return $request->header('X-Request-Id') ?: (string) Str::uuid();
    }

    /**
     * Build a structured 409 response for a non-playable episode.
     *
     * Using 409 Conflict (rather than 404) signals "temporarily unavailable"
     * vs "does not exist", giving clients enough information to show the right UX
     * without leaking existence of unauthorized content.
     */
    private function notPlayableResponse(Episode $episode, string $correlationId): JsonResponse
    {
        $retryAfter = $episode->isProcessing() ? 30 : null;

        $response = response()->json([
            'error'               => 'episode_not_playable',
            'block_reason'        => $episode->playbackBlockReason(),
            'retry_after_seconds' => $retryAfter,
            'message'             => match ($episode->playbackBlockReason()) {
                'processing'        => 'This episode is still being processed. Please try again shortly.',
                'processing_failed' => 'Audio processing failed for this episode. Please contact support.',
                'audio_missing'     => 'Audio file is unavailable. Please contact support.',
                default             => 'This episode is not available for playback.',
            },
        ], 409);

        $response->headers->set('X-Correlation-Id', $correlationId);
        if ($retryAfter) {
            $response->headers->set('Retry-After', (string) $retryAfter);
        }

        return $response;
    }

    /**
     * Return a time-limited S3 URL so the client can stream directly (low latency),
     * instead of proxying bytes through PHP.
     */
    public function signedAudio(Request $request, Episode $episode): JsonResponse
    {
        $correlationId = $this->correlationId($request);

        $episode->loadMissing('chapter.audiobook');
        if (! $episode->chapter || ! $episode->chapter->audiobook) {
            abort(404, 'Episode has no audiobook.');
        }
        $book = $episode->chapter->audiobook;
        if ($book->status !== 'approved') {
            abort(404);
        }

        if (! $episode->isPlayable()) {
            return $this->notPlayableResponse($episode, $correlationId);
        }

        $s3 = app(S3Service::class);
        if (! $s3->exists($episode->audio_path)) {
            // audio_path is set but S3 object is gone — treat as audio_missing
            $episode->processing_status = 'ready'; // already ready, path just missing from S3
            return response()->json([
                'error'               => 'episode_not_playable',
                'block_reason'        => 'audio_missing',
                'retry_after_seconds' => null,
                'message'             => 'Audio file is unavailable. Please contact support.',
            ], 409)->withHeaders(['X-Correlation-Id' => $correlationId]);
        }

        // Presign for 7 days (S3 max). The response itself is private/no-cache:
        // each user must call this endpoint to get their own signed URL — the URL
        // is already time-limited and contains auth params, so no CDN caching here.
        $expiresMinutes = 60 * 24 * 7 - 60; // ~7 days minus 1 min buffer
        $playUrl        = $s3->temporaryUrl($episode->audio_path, $expiresMinutes);

        return response()->json([
            'play_url'          => $playUrl,
            'expires_in_seconds' => $expiresMinutes * 60,
            'correlation_id'    => $correlationId,
        ])->withHeaders([
            'X-Correlation-Id' => $correlationId,
            // Private: the signed URL is user-scoped; proxies must not cache it.
            'Cache-Control'    => 'private, no-store',
        ]);
    }

    public function stream(Request $request, Episode $episode): StreamedResponse
    {
        $correlationId = $this->correlationId($request);
        $s3 = app(S3Service::class);

        if (! $episode->isPlayable()) {
            // StreamedResponse cannot return JSON; abort with plain 409 here.
            // Clients should prefer /signed-audio; /stream is a fallback.
            abort(response()->json([
                'error'               => 'episode_not_playable',
                'block_reason'        => $episode->playbackBlockReason(),
                'retry_after_seconds' => $episode->isProcessing() ? 30 : null,
                'message'             => 'Episode is not ready for playback.',
            ], 409)->withHeaders(['X-Correlation-Id' => $correlationId]));
        }

        if (! $s3->exists($episode->audio_path)) {
            abort(404, 'Audio file not found.');
        }

        $fileSize = $s3->getSize($episode->audio_path);
        $mimeType = 'audio/mpeg';

        $start  = 0;
        $end    = $fileSize - 1;
        $status = 200;

        $headers = [
            'Content-Type'     => $mimeType,
            'Accept-Ranges'    => 'bytes',
            'Cache-Control'    => 'no-store',
            'X-Correlation-Id' => $correlationId,
        ];

        if ($request->headers->has('Range')) {
            [$unit, $range] = explode('=', $request->header('Range'), 2);
            [$start, $end]  = explode('-', $range, 2);
            $start = (int) $start;
            $end   = $end !== '' ? (int) $end : $fileSize - 1;
            $end   = min($end, $fileSize - 1);

            $headers['Content-Range']  = "bytes {$start}-{$end}/{$fileSize}";
            $headers['Content-Length'] = $end - $start + 1;
            $status = 206;
        } else {
            $headers['Content-Length'] = $fileSize;
        }

        $startFinal = $start;
        $endFinal   = $end;

        return response()->stream(function () use ($s3, $episode, $startFinal, $endFinal) {
            // S3Service::readStream passes the Range header directly to AWS SDK,
            // so S3 returns only the requested byte range — no skipping needed.
            $handle = $s3->readStream($episode->audio_path, $startFinal, $endFinal);

            if (! is_resource($handle)) {
                abort(500, 'Could not open audio stream.');
            }

            $remaining = $endFinal - $startFinal + 1;

            try {
                $bufferSize = 65536; // 64 KB chunks
                while ($remaining > 0 && ! feof($handle)) {
                    $chunk = min($bufferSize, $remaining);
                    $data  = fread($handle, $chunk);
                    if ($data === false || $data === '') {
                        break;
                    }
                    echo $data;
                    $remaining -= strlen($data);
                    flush();
                }
            } finally {
                if (is_resource($handle)) {
                    fclose($handle);
                }
            }
        }, $status, $headers);
    }
}
