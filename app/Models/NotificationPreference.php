<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'episode_ready',
        'new_episode',
        'resume_reminder',
    ];

    protected function casts(): array
    {
        return [
            'episode_ready'   => 'boolean',
            'new_episode'     => 'boolean',
            'resume_reminder' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check whether a given notification type is enabled.
     * Unknown types default to true (fail open — don't silently suppress new types).
     */
    public function isEnabled(string $type): bool
    {
        return match ($type) {
            'episode_ready'   => $this->episode_ready,
            'new_episode'     => $this->new_episode,
            'resume_reminder' => $this->resume_reminder,
            default           => true,
        };
    }
}
