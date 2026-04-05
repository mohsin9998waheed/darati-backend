<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EpisodeStreamController extends Controller
{
    /**
     * Return a time-limited S3 URL so the client can stream directly (low latency),
     * instead of proxying bytes through PHP.
     */
    public function signedAudio(Request $request, Episode $episode): JsonResponse
    {
        $episode->loadMissing('chapter.audiobook');
        if (! $episode->chapter || ! $episode->chapter->audiobook) {
            abort(404, 'Episode has no audiobook.');
        }
        $book = $episode->chapter->audiobook;
        if ($book->status !== 'approved') {
            abort(404);
        }

        $s3 = app(S3Service::class);
        if (! $episode->audio_path || ! $s3->exists($episode->audio_path)) {
            abort(404, 'Audio file not found.');
        }

        // Keep ≤ 7 days (S3 presign limit); client can refresh by calling again.
        return response()->json([
            'play_url' => $s3->temporaryUrl($episode->audio_path, 60 * 24 * 7 - 60),
        ]);
    }

    public function stream(Request $request, Episode $episode): StreamedResponse
    {
        $s3 = app(S3Service::class);

        if (! $episode->audio_path || ! $s3->exists($episode->audio_path)) {
            abort(404, 'Audio file not found.');
        }

        $fileSize = $s3->getSize($episode->audio_path);
        $mimeType = 'audio/mpeg';

        $start  = 0;
        $end    = $fileSize - 1;
        $status = 200;

        $headers = [
            'Content-Type'  => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store',
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
