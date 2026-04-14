<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\DeviceToken;
use App\Services\FcmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AudiobookController extends Controller
{
    public function index(Request $request): View
    {
        $query = Audiobook::with('artist', 'category');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $audiobooks = $query->latest()->paginate(20)->withQueryString();

        $pendingCount = Audiobook::where('status', 'pending')->count();

        return view('admin.audiobooks.index', compact('audiobooks', 'pendingCount'));
    }

    public function show(Audiobook $audiobook): View
    {
        $audiobook->load('artist', 'category', 'chapters.episodes');
        $audiobook->loadCount(['favorites', 'ratings', 'comments']);
        $totalEpisodes = $audiobook->chapters->sum(fn ($c) => $c->episodes->count());
        $totalDuration = $audiobook->chapters->sum(fn ($c) => $c->episodes->sum('duration_seconds'));
        $recentRatings = $audiobook->ratings()->with('user:id,name')->latest()->take(50)->get();
        $recentComments = $audiobook->comments()->with('user:id,name,email')->latest()->take(50)->get();

        return view('admin.audiobooks.show', compact(
            'audiobook',
            'totalEpisodes',
            'totalDuration',
            'recentRatings',
            'recentComments',
        ));
    }

    public function approve(Audiobook $audiobook): RedirectResponse
    {
        $audiobook->update(['status' => 'approved', 'rejection_reason' => null]);

        // Broadcast a "new book published" push to all registered devices.
        $tokens = DeviceToken::query()->pluck('token')->all();
        if (! empty($tokens)) {
            app(FcmService::class)->sendToDevices(
                $tokens,
                $audiobook->title,
                'New Arrival',
                [
                    'type' => 'new_book',
                    'audiobook_id' => (string) $audiobook->id,
                    'tagline' => 'New Arrival',
                    'book_title' => $audiobook->title,
                ],
                $audiobook->thumbnail_url
            );
        }

        return back()->with('success', "'{$audiobook->title}' has been approved.");
    }

    public function reject(Request $request, Audiobook $audiobook): RedirectResponse
    {
        $request->validate(['rejection_reason' => ['nullable', 'string', 'max:500']]);
        $audiobook->update(['status' => 'rejected', 'rejection_reason' => $request->rejection_reason]);
        return back()->with('success', "'{$audiobook->title}' has been rejected.");
    }

    public function toggleTrending(Audiobook $audiobook): RedirectResponse
    {
        $audiobook->update(['is_trending' => ! $audiobook->is_trending]);
        $label = $audiobook->is_trending ? 'marked as trending' : 'removed from trending';
        return back()->with('success', "'{$audiobook->title}' has been {$label}.");
    }

    public function destroy(Audiobook $audiobook): RedirectResponse
    {
        $audiobook->delete();
        return redirect()->route('admin.audiobooks.index')->with('success', 'Audiobook deleted.');
    }
}
