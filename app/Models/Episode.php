<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
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
