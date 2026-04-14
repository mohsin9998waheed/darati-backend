<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Models\DeviceToken;
use App\Models\Audiobook;
use App\Models\Listen;
use App\Services\FcmService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notify:continue-listening', function () {
    $fcm = app(FcmService::class);
    $sent = 0;
    $checkedUsers = 0;
    $skippedNoListen = 0;
    $skippedBookNotApproved = 0;
    $skippedCompleted = 0;
    $skippedNoTokens = 0;

    $userIds = DeviceToken::query()
        ->select('user_id')
        ->distinct()
        ->pluck('user_id');

    Log::info('notify:continue-listening.started', [
        'users_with_device_tokens' => $userIds->count(),
    ]);

    foreach ($userIds as $userId) {
        $checkedUsers++;
        $listen = Listen::query()
            ->where('user_id', $userId)
            ->where('progress_seconds', '>', 30)
            ->with('episode.chapter.audiobook')
            ->latest('updated_at')
            ->first();

        if (! $listen || ! $listen->episode || ! $listen->episode->chapter || ! $listen->episode->chapter->audiobook) {
            $skippedNoListen++;
            continue;
        }

        $episode = $listen->episode;
        $book = $episode->chapter->audiobook;
        if ($book->status !== 'approved') {
            $skippedBookNotApproved++;
            continue;
        }

        $duration = (int) $episode->duration_seconds;
        $progress = (int) $listen->progress_seconds;
        // Skip if effectively completed.
        if ($duration > 0 && $progress >= max(1, $duration - 15)) {
            $skippedCompleted++;
            continue;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->all();

        if (empty($tokens)) {
            $skippedNoTokens++;
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
            Log::info('notify:continue-listening.sent_to_user', [
                'user_id' => $userId,
                'token_count' => count($tokens),
                'book_id' => $book->id,
                'episode_id' => $episode->id,
            ]);
        } else {
            Log::warning('notify:continue-listening.send_failed_for_user', [
                'user_id' => $userId,
                'token_count' => count($tokens),
                'book_id' => $book->id,
                'episode_id' => $episode->id,
            ]);
        }
    }

    Log::info('notify:continue-listening.completed', [
        'checked_users' => $checkedUsers,
        'sent' => $sent,
        'skipped_no_listen' => $skippedNoListen,
        'skipped_book_not_approved' => $skippedBookNotApproved,
        'skipped_completed' => $skippedCompleted,
        'skipped_no_tokens' => $skippedNoTokens,
    ]);
    $this->info("Reminders sent: {$sent} / checked: {$checkedUsers}");
})->purpose('Send daily continue-listening reminders to users');

Artisan::command('notify:new-arrivals', function () {
    $fcm = app(FcmService::class);

    $topBooks = Audiobook::query()
        ->where('status', 'approved')
        ->latest()
        ->take(3)
        ->get();

    if ($topBooks->isEmpty()) {
        Log::info('notify:new-arrivals.skipped_no_books');
        $this->info('No approved books found.');
        return;
    }

    $tokens = DeviceToken::query()->pluck('token')->all();
    if (empty($tokens)) {
        Log::info('notify:new-arrivals.skipped_no_tokens');
        $this->info('No device tokens found.');
        return;
    }

    $sent = 0;
    foreach ($topBooks as $book) {
        $ok = $fcm->sendToDevices(
            $tokens,
            $book->title,
            'New Arrival',
            [
                'type' => 'new_book',
                'audiobook_id' => (string) $book->id,
                'tagline' => 'New Arrival',
                'book_title' => $book->title,
            ],
            $book->thumbnail_url
        );

        if ($ok) {
            $sent++;
            Log::info('notify:new-arrivals.sent', [
                'book_id' => $book->id,
                'title' => $book->title,
                'token_count' => count($tokens),
            ]);
        } else {
            Log::warning('notify:new-arrivals.failed', [
                'book_id' => $book->id,
                'title' => $book->title,
                'token_count' => count($tokens),
            ]);
        }
    }

    Log::info('notify:new-arrivals.completed', [
        'books_attempted' => $topBooks->count(),
        'books_sent_ok' => $sent,
        'token_count' => count($tokens),
    ]);
    $this->info("New-arrival pushes attempted for {$topBooks->count()} books.");
})->purpose('Send top-3 new-arrivals push notifications');

// Pakistan reminder schedule (Asia/Karachi).
Schedule::command('notify:continue-listening')->dailyAt('17:10')->timezone('Asia/Karachi');
Schedule::command('notify:new-arrivals')->cron('*/8 * * * *')->timezone('Asia/Karachi');
