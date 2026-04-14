<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Models\DeviceToken;
use App\Models\Listen;
use App\Services\FcmService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notify:continue-listening', function () {
    $fcm = app(FcmService::class);
    $sent = 0;

    $userIds = DeviceToken::query()
        ->select('user_id')
        ->distinct()
        ->pluck('user_id');

    foreach ($userIds as $userId) {
        $listen = Listen::query()
            ->where('user_id', $userId)
            ->where('progress_seconds', '>', 30)
            ->with('episode.chapter.audiobook')
            ->latest('updated_at')
            ->first();

        if (! $listen || ! $listen->episode || ! $listen->episode->chapter || ! $listen->episode->chapter->audiobook) {
            continue;
        }

        $episode = $listen->episode;
        $book = $episode->chapter->audiobook;
        if ($book->status !== 'approved') {
            continue;
        }

        $duration = (int) $episode->duration_seconds;
        $progress = (int) $listen->progress_seconds;
        // Skip if effectively completed.
        if ($duration > 0 && $progress >= max(1, $duration - 15)) {
            continue;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->all();

        if (empty($tokens)) {
            continue;
        }

        $ok = $fcm->sendToDevices(
            $tokens,
            'Continue your audiobook',
            "Pick up where you left off: {$book->title}",
            [
                'type' => 'continue_listening',
                'episode_id' => (string) $episode->id,
                'audiobook_id' => (string) $book->id,
            ],
            $book->thumbnail_url
        );

        if ($ok) {
            $sent++;
        }
    }

    Log::info('notify:continue-listening completed', ['sent' => $sent]);
    $this->info("Reminders sent: {$sent}");
})->purpose('Send daily continue-listening reminders to users');

// Pakistan prime-time reminder (Asia/Karachi).
Schedule::command('notify:continue-listening')->dailyAt('19:30')->timezone('Asia/Karachi');
