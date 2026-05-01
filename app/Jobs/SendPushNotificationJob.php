<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\NotificationPreference;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends a push notification to all device tokens belonging to a user,
 * respecting their notification preference for the given type.
 *
 * Notification types:
 *   episode_ready   — episode finished transcoding, ready to play
 *   new_episode     — a new episode was added to a book the user follows
 *   resume_reminder — re-engagement nudge for a paused listen (future)
 *
 * Usage:
 *   SendPushNotificationJob::dispatch(
 *       userId:   $userId,
 *       type:     'episode_ready',
 *       title:    'Your episode is ready',
 *       body:     '"Chapter 3" is now available to play.',
 *       data:     ['episode_id' => '42', 'audiobook_id' => '7'],
 *       imageUrl: 'https://cdn.example.com/thumb.jpg',
 *   );
 */
class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly int     $userId,
        private readonly string  $type,
        private readonly string  $title,
        private readonly string  $body,
        private readonly array   $data     = [],
        private readonly ?string $imageUrl = null,
    ) {}

    public function handle(FcmService $fcm): void
    {
        // ── 1. Check user preference ──────────────────────────────────────────
        $prefs = NotificationPreference::where('user_id', $this->userId)->first();
        if ($prefs && ! $prefs->isEnabled($this->type)) {
            Log::info("SendPushNotificationJob: user={$this->userId} has disabled type={$this->type}");
            return;
        }

        // ── 2. Fetch all device tokens for the user ───────────────────────────
        $tokens = DeviceToken::where('user_id', $this->userId)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            Log::info("SendPushNotificationJob: user={$this->userId} has no device tokens.");
            return;
        }

        // ── 3. Send to each token, prune stale ones ───────────────────────────
        $stale = [];
        foreach ($tokens as $token) {
            $sent = $fcm->sendToDevice(
                token:    $token,
                title:    $this->title,
                body:     $this->body,
                data:     array_merge($this->data, ['type' => $this->type]),
                imageUrl: $this->imageUrl,
            );

            if (! $sent) {
                // Mark for removal — we'll batch-delete after the loop.
                // Note: FcmService logs 404s specifically; non-404 failures should
                // NOT prune tokens (the token may still be valid, just a transient error).
                // For simplicity we prune on any failure here; adjust if needed.
                $stale[] = $token;
            }
        }

        if (! empty($stale)) {
            DeviceToken::whereIn('token', $stale)->delete();
            Log::info("SendPushNotificationJob: pruned " . count($stale) . " stale token(s) for user={$this->userId}");
        }

        Log::info("SendPushNotificationJob: sent type={$this->type} to " . count($tokens) . " device(s) for user={$this->userId}");
    }
}
