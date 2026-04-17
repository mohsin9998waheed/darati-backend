<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Comment;
use App\Models\Episode;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalPlaySeconds = (int) Audiobook::sum('total_play_seconds');

        $stats = [
            'total_users'       => User::where('role', '!=', 'admin')->count(),
            'total_artists'     => User::where('role', 'artist')->count(),
            'total_listeners'   => User::where('role', 'listener')->count(),
            'pending_books'     => Audiobook::where('status', 'pending')->count(),
            'approved_books'    => Audiobook::where('status', 'approved')->count(),
            'rejected_books'    => Audiobook::where('status', 'rejected')->count(),
            'total_listens'     => (int) Audiobook::sum('total_listens'),
            'total_play_hours'  => round($totalPlaySeconds / 3600, 1),
            'flagged_comments'  => Comment::where('is_flagged', true)->count(),
            'processing_episodes' => Episode::whereIn('processing_status', ['queued', 'processing'])->count(),
        ];

        $recentAudiobooks = Audiobook::with('artist', 'category')
            ->latest()
            ->limit(6)
            ->get();

        $topAudiobooks = Audiobook::with('artist')
            ->where('status', 'approved')
            ->orderBy('total_listens', 'desc')
            ->limit(5)
            ->get();

        $recentUsers = User::where('role', '!=', 'admin')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentAudiobooks',
            'topAudiobooks',
            'recentUsers',
        ));
    }
}
