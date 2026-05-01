<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'title',
        'audio_path',
        'raw_audio_path',
        'duration_seconds',
        'file_size',
        'order',
        'is_preview',
        'processing_status',
    ];

    protected function casts(): array
    {
        return ['is_preview' => 'boolean'];
    }

    public function isProcessing(): bool
    {
        return in_array($this->processing_status, ['queued', 'processing'], true);
    }

    public function isFailed(): bool
    {
        return $this->processing_status === 'failed';
    }

    public function isReady(): bool
    {
        return $this->processing_status === 'ready';
    }

    /**
     * Machine-readable reason why this episode cannot be played right now.
     * Returns null when the episode is fully playable.
     *
     * Values:
     *  - 'processing'        — audio is queued or being transcoded; retry shortly
     *  - 'processing_failed' — transcode job failed; artist must re-upload
     *  - 'audio_missing'     — status=ready but S3 object gone (storage issue)
     *  - null                — episode is ready and the audio file exists
     */
    public function playbackBlockReason(): ?string
    {
        return match (true) {
            $this->isProcessing() => 'processing',
            $this->isFailed()     => 'processing_failed',
            $this->isReady() && ! $this->audio_path => 'audio_missing',
            default               => null,
        };
    }

    /**
     * True when the episode is ready and has an audio_path.
     * Note: does NOT verify the file actually exists in S3 (that would be a network call).
     * The stream/signed-audio endpoints do the live S3 check before serving.
     */
    public function isPlayable(): bool
    {
        return $this->isReady() && (bool) $this->audio_path;
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function listens(): HasMany
    {
        return $this->hasMany(Listen::class);
    }

    public function getDurationFormattedAttribute(): string
    {
        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
