<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $artist = Auth::user();

        $totalPlaySeconds = (int) $artist->audiobooks()->sum('total_play_seconds');

        $stats = [
            'total_books'       => $artist->audiobooks()->count(),
            'approved_books'    => $artist->audiobooks()->where('status', 'approved')->count(),
            'pending_books'     => $artist->audiobooks()->where('status', 'pending')->count(),
            'total_listens'     => (int) $artist->audiobooks()->sum('total_listens'),
            'total_play_hours'  => round($totalPlaySeconds / 3600, 1),
            'avg_rating'        => round(
                $artist->audiobooks()->where('avg_rating', '>', 0)->avg('avg_rating') ?? 0,
                1
            ),
        ];

        $recentBooks = $artist->audiobooks()
            ->with('category')
            ->latest()
            ->limit(6)
            ->get();

        return view('artist.dashboard', compact('stats', 'recentBooks'));
    }
}
