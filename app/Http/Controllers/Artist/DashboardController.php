<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $artist = Auth::user();

        $stats = [
            'total_books'    => $artist->audiobooks()->count(),
            'approved_books' => $artist->audiobooks()->where('status', 'approved')->count(),
            'pending_books'  => $artist->audiobooks()->where('status', 'pending')->count(),
            'total_listens'  => $artist->audiobooks()->sum('total_listens'),
            'avg_rating'     => round($artist->audiobooks()->where('avg_rating', '>', 0)->avg('avg_rating') ?? 0, 1),
        ];

        $recentBooks = $artist->audiobooks()
            ->with('category')
            ->latest()
            ->limit(5)
            ->get();

        return view('artist.dashboard', compact('stats', 'recentBooks'));
    }
}
