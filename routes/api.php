<?php

use App\Http\Controllers\Api\AudiobookController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\EpisodeController;
use App\Http\Controllers\Api\EpisodeStreamController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ListenController;
use App\Http\Controllers\Api\RatingController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Public browsing
Route::get('/audiobooks', [AudiobookController::class, 'index']);
Route::get('/audiobooks/{audiobook}', [AudiobookController::class, 'show']);
Route::get('/audiobooks/{audiobook}/chapters', [ChapterController::class, 'index']);
Route::get('/comments/{audiobook}', [CommentController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Artist: audiobook management
    Route::middleware('role:artist,admin')->group(function () {
        Route::post('/audiobooks', [AudiobookController::class, 'store']);
        Route::put('/audiobooks/{audiobook}', [AudiobookController::class, 'update']);
        Route::delete('/audiobooks/{audiobook}', [AudiobookController::class, 'destroy']);
        Route::post('/chapters', [ChapterController::class, 'store']);
        Route::put('/chapters/{chapter}', [ChapterController::class, 'update']);
        Route::delete('/chapters/{chapter}', [ChapterController::class, 'destroy']);
        Route::post('/episodes', [EpisodeController::class, 'store']);
        Route::put('/episodes/{episode}', [EpisodeController::class, 'update']);
        Route::delete('/episodes/{episode}', [EpisodeController::class, 'destroy']);
    });

    // Listener engagement
    Route::post('/rate', [RatingController::class, 'store']);
    Route::post('/comment', [CommentController::class, 'store']);
    Route::post('/listen', [ListenController::class, 'store']);
    Route::get('/progress', [ListenController::class, 'progress']);
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{audiobook}', [FavoriteController::class, 'destroy']);

    Route::get('/episodes/{episode}/signed-audio', [EpisodeStreamController::class, 'signedAudio']);
});

// Audio streaming (signed URL or token-based)
Route::get('/stream/{episode}', [EpisodeStreamController::class, 'stream'])
    ->middleware('auth:sanctum')
    ->name('api.stream');
