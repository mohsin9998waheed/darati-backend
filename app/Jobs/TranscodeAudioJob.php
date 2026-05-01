<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Services\S3Service;
use App\Jobs\SendPushNotificationJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TranscodeAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 600; // 10 minutes — enough for a 350 MB file

    public function __construct(
        private readonly int    $episodeId,
        private readonly string $rawS3Key,
    ) {}

    public function handle(): void
    {
        $episode = Episode::find($this->episodeId);

        if (! $episode || $episode->processing_status === 'ready') {
            Log::info("TranscodeAudioJob: episode {$this->episodeId} not found or already ready — skipping.");
            return;
        }

        $episode->update(['processing_status' => 'processing']);

        $s3  = app(S3Service::class);
        $ext = strtolower(pathinfo($this->rawS3Key, PATHINFO_EXTENSION)) ?: 'mp3';

        $localRaw = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'darati_raw_' . Str::uuid() . '.' . $ext;
        $localOut = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'darati_out_' . Str::uuid() . '.mp3';

        try {
            // ── 1. Download raw audio from S3 ──────────────────────────────
            Log::info("TranscodeAudioJob[{$this->episodeId}]: downloading raw audio from S3 key={$this->rawS3Key}");
            $stream = $s3->readStream($this->rawS3Key);
            $fh     = fopen($localRaw, 'wb');
            stream_copy_to_stream($stream, $fh);
            fclose($fh);
            Log::info("TranscodeAudioJob[{$this->episodeId}]: raw download complete, size=" . filesize($localRaw) . " bytes");

            // ── 2. Transcode with FFmpeg (if available) ────────────────────
            $ffmpegBin = $this->findBinary('ffmpeg');
            $transcoded = false;

            if ($ffmpegBin) {
                Log::info("TranscodeAudioJob[{$this->episodeId}]: running ffmpeg ({$ffmpegBin})");

                $cmd = escapeshellcmd($ffmpegBin)
                    . ' -i '        . escapeshellarg($localRaw)
                    . ' -codec:a libmp3lame -b:a 128k -ar 44100 -ac 2 -map_metadata -1 -y '
                    . escapeshellarg($localOut)
                    . ' 2>&1';

                exec($cmd, $output, $returnCode);
                $transcoded = ($returnCode === 0 && file_exists($localOut) && filesize($localOut) > 0);

                if ($transcoded) {
                    $origMB  = round(filesize($localRaw) / 1_048_576, 1);
                    $transMB = round(filesize($localOut) / 1_048_576, 1);
                    Log::info("TranscodeAudioJob[{$this->episodeId}]: transcoded OK — {$origMB} MB → {$transMB} MB");
                } else {
                    Log::warning("TranscodeAudioJob[{$this->episodeId}]: ffmpeg exited {$returnCode}", [
                        'last_lines' => implode("\n", array_slice($output, -15)),
                    ]);
                }
            } else {
                Log::warning("TranscodeAudioJob[{$this->episodeId}]: ffmpeg not found — using raw audio as-is");
            }

            // ── 3. Upload final file to S3 ────────────────────────────────
            $pathToUpload = $transcoded ? $localOut : $localRaw;
            $finalExt     = $transcoded ? 'mp3'     : $ext;
            $finalMime    = $transcoded ? 'audio/mpeg' : $this->mimeForExtension($finalExt);

            Log::info("TranscodeAudioJob[{$this->episodeId}]: uploading final audio to S3");
            $finalKey = $s3->uploadFromLocalPath($pathToUpload, 'audio', $finalExt, $finalMime);

            // ── 4. Get duration ───────────────────────────────────────────
            $duration = $this->getDuration($pathToUpload, $ffmpegBin);

            // ── 4.5. Validate output before publishing ────────────────────
            // This is the gate that prevents corrupt or truncated audio from
            // ever reaching listeners. Throws on failure → caught below → 'failed'.
            $this->validateAudioOutput(
                path:       $pathToUpload,
                duration:   $duration,
                transcoded: $transcoded,
                episodeId:  $this->episodeId,
            );

            // ── 5. Update episode record ──────────────────────────────────
            $episode->update([
                'audio_path'        => $finalKey,
                'file_size'         => filesize($pathToUpload),
                'duration_seconds'  => $duration,
                'processing_status' => 'ready',
            ]);

            // ── 6. Delete the raw file from S3 to save storage ────────────
            if ($transcoded) {
                $s3->delete($this->rawS3Key);
            }

            // ── 7. Notify the artist that their episode is ready ──────────
            // Load the artist via chapter → audiobook → artist.
            $episode->load('chapter.audiobook.artist');
            $artist    = $episode->chapter?->audiobook?->artist;
            $bookTitle = $episode->chapter?->audiobook?->title ?? 'your audiobook';
            $thumbUrl  = $episode->chapter?->audiobook?->thumbnail_url ?? null;

            if ($artist) {
                SendPushNotificationJob::dispatch(
                    userId:   $artist->id,
                    type:     'episode_ready',
                    title:    '🎙️ Episode ready to play',
                    body:     "\"{$episode->title}\" from {$bookTitle} has finished processing.",
                    data:     [
                        'episode_id'   => (string) $episode->id,
                        'audiobook_id' => (string) ($episode->chapter?->audiobook?->id ?? ''),
                    ],
                    imageUrl: $thumbUrl,
                );
            }

            Log::info("TranscodeAudioJob[{$this->episodeId}]: DONE — transcoded={$transcoded}, duration={$duration}s, key={$finalKey}");

        } catch (\Throwable $e) {
            Log::error("TranscodeAudioJob[{$this->episodeId}]: FAILED", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $episode->update(['processing_status' => 'failed']);
            throw $e;
        } finally {
            @unlink($localRaw);
            @unlink($localOut);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("TranscodeAudioJob[{$this->episodeId}]: permanently failed after all retries", [
            'error' => $e->getMessage(),
        ]);
        Episode::find($this->episodeId)?->update(['processing_status' => 'failed']);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function findBinary(string $name): ?string
    {
        $candidates = [
            "/usr/bin/{$name}",
            "/usr/local/bin/{$name}",
            "/opt/homebrew/bin/{$name}",
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        exec("which {$name} 2>/dev/null", $out, $code);
        if ($code === 0 && ! empty($out[0]) && trim($out[0]) !== '') {
            return trim($out[0]);
        }

        return null;
    }

    private function getDuration(string $path, ?string $ffmpegBin): int
    {
        if (! $ffmpegBin) {
            return 0;
        }

        $probeBin = str_replace('ffmpeg', 'ffprobe', $ffmpegBin);

        if (! file_exists($probeBin)) {
            exec("which ffprobe 2>/dev/null", $out, $code);
            $probeBin = ($code === 0 && ! empty($out[0])) ? trim($out[0]) : null;
        }

        if (! $probeBin) {
            return 0;
        }

        $cmd = escapeshellcmd($probeBin)
            . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '
            . escapeshellarg($path)
            . ' 2>&1';

        exec($cmd, $out, $exitCode);

        if ($exitCode === 0 && isset($out[0]) && is_numeric($out[0])) {
            return (int) round((float) $out[0]);
        }

        return 0;
    }

    private function mimeForExtension(string $ext): string
    {
        return match (strtolower($ext)) {
            'mp3', 'mpeg' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a', 'mp4' => 'audio/mp4',
            default => 'application/octet-stream',
        };
    }

    /**
     * Gate that must pass before we mark an episode as 'ready'.
     *
     * Checks enforced:
     *  1. File exists at the expected path.
     *  2. File size is above the minimum threshold (10 KB).
     *     Any valid audio file — even a 1-second MP3 — is larger than this.
     *     A smaller file almost certainly means truncation or a failed write.
     *  3. Duration is non-zero when FFmpeg was available to measure it.
     *     A zero duration from a successful transcode means the file is unreadable.
     *
     * @throws \RuntimeException on any validation failure (caught → episode marked 'failed')
     */
    private function validateAudioOutput(
        string $path,
        int    $duration,
        bool   $transcoded,
        int    $episodeId,
    ): void {
        // 1. File must exist
        if (! file_exists($path)) {
            throw new \RuntimeException(
                "TranscodeAudioJob[{$episodeId}]: output file does not exist at path={$path}"
            );
        }

        // 2. Minimum file size: 10 KB
        $minBytes  = 10_240;
        $actualBytes = filesize($path);
        if ($actualBytes < $minBytes) {
            throw new \RuntimeException(
                "TranscodeAudioJob[{$episodeId}]: output file is suspiciously small " .
                "({$actualBytes} bytes < {$minBytes} minimum). " .
                "Likely truncated or a failed write."
            );
        }

        // 3. Duration must be readable when we ran FFmpeg.
        //    If FFmpeg was unavailable we skip this check (duration stays 0 from raw).
        if ($transcoded && $duration <= 0) {
            throw new \RuntimeException(
                "TranscodeAudioJob[{$episodeId}]: FFmpeg produced output but duration " .
                "could not be read (got 0s). File may be corrupt or contain no audio stream."
            );
        }

        Log::info(
            "TranscodeAudioJob[{$episodeId}]: output validation passed — " .
            "size={$actualBytes}B duration={$duration}s transcoded={$transcoded}"
        );
    }
}
