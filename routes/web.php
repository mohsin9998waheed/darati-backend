<?php

use App\Http\Controllers\Admin\AudiobookController as AdminAudiobookController;
use App\Http\Controllers\Debug\FirebaseHealthController;
use App\Http\Controllers\Web\EpisodeStreamController as WebEpisodeStreamController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Artist\AnalyticsController as ArtistAnalyticsController;
use App\Http\Controllers\Artist\AudiobookController as ArtistAudiobookController;
use App\Http\Controllers\Artist\ChapterController as ArtistChapterController;
use App\Http\Controllers\Artist\DashboardController as ArtistDashboardController;
use App\Http\Controllers\Artist\CommentModerationController as ArtistCommentModerationController;
use App\Http\Controllers\Artist\EpisodeController as ArtistEpisodeController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Browser diagnostics for Firebase/FCM credentials and scheduler wiring.
// In production, protect this with DEBUG_HEALTH_KEY query param.
Route::get('/debug/firebase-health', FirebaseHealthController::class);
Route::match(['GET', 'POST'], '/debug/firebase-push-test', [FirebaseHealthController::class, 'sendTest'])
    ->withoutMiddleware([ValidateCsrfToken::class]);
Route::get('/debug/firebase-push-status', [FirebaseHealthController::class, 'pushStatus']);

// Audio streaming for web panels (session auth, Range-request aware)
Route::get('/episodes/{episode}/play', [WebEpisodeStreamController::class, 'stream'])
    ->name('episodes.play')
    ->middleware(['auth']);

// Auth
Route::get('/login', [LoginController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return match (Auth::user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'artist' => redirect()->route('artist.dashboard'),
        default => abort(403, 'No web panel access for this account.'),
    };
});

// Admin Panel
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('/audiobooks', [AdminAudiobookController::class, 'index'])->name('audiobooks.index');
    Route::patch('/audiobooks/{audiobook}/approve', [AdminAudiobookController::class, 'approve'])->name('audiobooks.approve');
    Route::patch('/audiobooks/{audiobook}/reject', [AdminAudiobookController::class, 'reject'])->name('audiobooks.reject');
    Route::patch('/audiobooks/{audiobook}/toggle-trending', [AdminAudiobookController::class, 'toggleTrending'])->name('audiobooks.toggle-trending');
    Route::get('/audiobooks/{audiobook}', [AdminAudiobookController::class, 'show'])->name('audiobooks.show');
    Route::delete('/audiobooks/{audiobook}', [AdminAudiobookController::class, 'destroy'])->name('audiobooks.destroy');

    Route::resource('/categories', AdminCategoryController::class)->except(['show']);

    Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::patch('/comments/{comment}/flag', [AdminCommentController::class, 'flag'])->name('comments.flag');
    Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
});

// Artist Panel
Route::prefix('artist')->name('artist.')->middleware(['auth', 'role:artist'])->group(function () {
    Route::get('/dashboard', [ArtistDashboardController::class, 'index'])->name('dashboard');

    Route::get('/audiobooks', [ArtistAudiobookController::class, 'index'])->name('audiobooks.index');
    Route::get('/audiobooks/create', [ArtistAudiobookController::class, 'create'])->name('audiobooks.create');
    Route::post('/audiobooks', [ArtistAudiobookController::class, 'store'])->name('audiobooks.store');
    Route::get('/audiobooks/{audiobook}/edit', [ArtistAudiobookController::class, 'edit'])->name('audiobooks.edit');
    Route::put('/audiobooks/{audiobook}', [ArtistAudiobookController::class, 'update'])->name('audiobooks.update');
    Route::delete('/audiobooks/{audiobook}', [ArtistAudiobookController::class, 'destroy'])->name('audiobooks.destroy');
    Route::get('/audiobooks/{audiobook}', [ArtistAudiobookController::class, 'show'])->name('audiobooks.show');

    Route::post('/audiobooks/{audiobook}/chapters', [ArtistChapterController::class, 'store'])->name('chapters.store');
    Route::put('/chapters/{chapter}', [ArtistChapterController::class, 'update'])->name('chapters.update');
    Route::delete('/chapters/{chapter}', [ArtistChapterController::class, 'destroy'])->name('chapters.destroy');

    Route::post('/chapters/{chapter}/episodes', [ArtistEpisodeController::class, 'store'])->name('episodes.store');
    Route::put('/episodes/{episode}', [ArtistEpisodeController::class, 'update'])->name('episodes.update');
    Route::delete('/episodes/{episode}', [ArtistEpisodeController::class, 'destroy'])->name('episodes.destroy');

    Route::get('/analytics', [ArtistAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/{audiobook}', [ArtistAnalyticsController::class, 'show'])->name('analytics.show');

    Route::patch('/comments/{comment}/toggle-flag', [ArtistCommentModerationController::class, 'toggleFlag'])->name('comments.toggle-flag');
});
