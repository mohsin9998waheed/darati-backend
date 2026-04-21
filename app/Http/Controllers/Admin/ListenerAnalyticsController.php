<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Listen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ListenerAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        // Top listeners — users with most total play seconds
        $topListeners = User::query()
            ->where('role', 'user')
            ->withCount('listens')
            ->withSum('listens', 'progress_seconds')
            ->orderByDesc('listens_sum_progress_seconds')
            ->take(50)
            ->get();

        // Recent listen activity
        $recentActivity = Listen::query()
            ->with(['user:id,name,email', 'episode.chapter.audiobook:id,title,thumbnail'])
            ->latest('updated_at')
            ->take(100)
            ->get();

        // Per-book unique listener counts (subquery approach for 3-level relation)
        $bookStats = Audiobook::query()
            ->where('status', 'approved')
            ->select([
                'audiobooks.*',
                DB::raw('(SELECT COUNT(DISTINCT listens.user_id)
                          FROM listens
                          JOIN episodes ON episodes.id = listens.episode_id
                          JOIN chapters ON chapters.id = episodes.chapter_id
                          WHERE chapters.audiobook_id = audiobooks.id) as unique_listeners'),
                DB::raw('(SELECT COALESCE(SUM(listens.progress_seconds), 0)
                          FROM listens
                          JOIN episodes ON episodes.id = listens.episode_id
                          JOIN chapters ON chapters.id = episodes.chapter_id
                          WHERE chapters.audiobook_id = audiobooks.id) as total_seconds'),
            ])
            ->orderByDesc('unique_listeners')
            ->take(30)
            ->get();

        // City analytics — group users by city where city is set
        $cityStats = User::query()
            ->where('role', 'user')
            ->whereNotNull('city')
            ->select('city', 'country', DB::raw('count(*) as user_count'))
            ->groupBy('city', 'country')
            ->orderByDesc('user_count')
            ->take(30)
            ->get();

        return view('admin.analytics.listeners', compact(
            'topListeners',
            'recentActivity',
            'bookStats',
            'cityStats',
        ));
    }

    public function bookDetail(Request $request, Audiobook $audiobook): View
    {
        $listeners = Listen::query()
            ->whereHas('episode.chapter', fn ($q) => $q->where('audiobook_id', $audiobook->id))
            ->with(['user:id,name,email', 'episode:id,title,duration_seconds'])
            ->latest('updated_at')
            ->paginate(30);

        $uniqueCount = Listen::query()
            ->whereHas('episode.chapter', fn ($q) => $q->where('audiobook_id', $audiobook->id))
            ->distinct('user_id')
            ->count('user_id');

        $audiobook->load('artist:id,name');

        return view('admin.analytics.listeners_book', compact(
            'audiobook',
            'listeners',
            'uniqueCount',
        ));
    }
}
