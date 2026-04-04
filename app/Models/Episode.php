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
        'duration_seconds',
        'file_size',
        'order',
        'is_preview',
    ];

    protected function casts(): array
    {
        return ['is_preview' => 'boolean'];
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
