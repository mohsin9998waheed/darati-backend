<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Chapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Chapters are an internal wrapper only (one per book).
 * Artists manage episodes directly on the audiobook.
 */
class ChapterController extends Controller
{
    public function store(Request $request, Audiobook $audiobook): RedirectResponse
    {
        $this->authorize('update', $audiobook);

        return back()->with('error', 'Chapters are no longer used. Add episodes directly to the book.');
    }

    public function update(Request $request, Chapter $chapter): RedirectResponse
    {
        $this->authorize('update', $chapter->audiobook);

        return back()->with('error', 'Chapters are no longer used. Edit episodes instead.');
    }

    public function destroy(Chapter $chapter): RedirectResponse
    {
        $this->authorize('update', $chapter->audiobook);

        return back()->with('error', 'Chapters are no longer used. Delete individual episodes instead.');
    }
}
