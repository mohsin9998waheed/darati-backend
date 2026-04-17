<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $artist = Auth::user();

        $books = $artist->audiobooks()
            ->with('category')
            ->withCount(['ratings', 'comments', 'favorites'])
            ->orderBy('total_listens', 'desc')
            ->get();

        $totalListens      = $books->sum('total_listens');
        $totalFavorites    = $books->sum('favorites_count');
        $totalPlaySeconds  = (int) $books->sum('total_play_seconds');
        $totalPlayHours    = round($totalPlaySeconds / 3600, 1);
        $avgRating         = round($books->where('avg_rating', '>', 0)->avg('avg_rating') ?? 0, 1);

        return view('artist.analytics.index', compact(
            'books',
            'totalListens',
            'totalFavorites',
            'totalPlayHours',
            'avgRating',
        ));
    }

    public function show(Audiobook $audiobook): View
    {
        $this->authorize('update', $audiobook);
        $audiobook->load('chapters.episodes');

        $episodeStats = DB::table('listens')
            ->join('episodes', 'listens.episode_id', '=', 'episodes.id')
            ->join('chapters', 'episodes.chapter_id', '=', 'chapters.id')
            ->where('chapters.audiobook_id', $audiobook->id)
            ->select(
                'episodes.id',
                'episodes.title',
                'episodes.duration_seconds',
                DB::raw('COUNT(*) as play_count'),
                DB::raw('COUNT(CASE WHEN listens.completed = 1 THEN 1 END) as completed_count'),
                DB::raw('SUM(listens.progress_seconds) as total_listen_seconds'),
                DB::raw('AVG(CASE WHEN listens.completed = 1 THEN 100 ELSE (listens.progress_seconds / NULLIF(episodes.duration_seconds, 0)) * 100 END) as avg_completion')
            )
            ->groupBy('episodes.id', 'episodes.title', 'episodes.duration_seconds')
            ->orderBy('play_count', 'desc')
            ->get();

        // Listening hours for the whole book
        $bookListenHours = round($audiobook->total_play_seconds / 3600, 1);

        return view('artist.analytics.show', compact('audiobook', 'episodeStats', 'bookListenHours'));
    }
}
