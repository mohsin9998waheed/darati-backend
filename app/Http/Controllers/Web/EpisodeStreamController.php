<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EpisodeStreamController extends Controller
{
    public function stream(Request $request, Episode $episode): StreamedResponse
    {
        $user = Auth::user();

        // Admins hear everything; artists only hear their own audiobooks' episodes
        if (! $user->isAdmin()) {
            $audiobook = $episode->chapter->audiobook;
            if ($audiobook->artist_id !== $user->id) {
                abort(403, 'You do not have access to this episode.');
            }
        }

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
            'Content-Type'              => $mimeType,
            'Accept-Ranges'             => 'bytes',
            'Cache-Control'             => 'no-store',
            'Access-Control-Allow-Origin' => '*',
        ];

        if ($request->headers->has('Range')) {
            [$unit, $range] = explode('=', $request->header('Range'), 2);
            [$start, $end]  = explode('-', $range, 2);
            $start  = (int) $start;
            $end    = $end !== '' ? (int) $end : $fileSize - 1;
            $end    = min($end, $fileSize - 1);

            $headers['Content-Range']  = "bytes {$start}-{$end}/{$fileSize}";
            $headers['Content-Length'] = $end - $start + 1;
            $status = 206;
        } else {
            $headers['Content-Length'] = $fileSize;
        }

        $startFinal = $start;
        $endFinal   = $end;

        return response()->stream(function () use ($s3, $episode, $startFinal, $endFinal) {
            $handle = $s3->readStream($episode->audio_path, $startFinal, $endFinal);

            if (! is_resource($handle)) {
                abort(500, 'Could not open audio stream.');
            }

            $remaining = $endFinal - $startFinal + 1;

            try {
                while ($remaining > 0 && ! feof($handle)) {
                    $chunk = min(65536, $remaining);
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
