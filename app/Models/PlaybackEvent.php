<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaybackEvent extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'episode_id',
        'audiobook_id',
        'elapsed_ms',
        'device_os',
        'device_os_version',
        'device_model',
        'network_type',
        'meta',
        'ts',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
