<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Chapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function store(Request $request, Audiobook $audiobook): RedirectResponse
    {
        $this->authorize('update', $audiobook);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['audiobook_id'] = $audiobook->id;
        $data['order'] = $audiobook->chapters()->max('order') + 1;

        Chapter::create($data);

        return back()->with('success', 'Chapter added.');
    }

    public function update(Request $request, Chapter $chapter): RedirectResponse
    {
        $this->authorize('update', $chapter->audiobook);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $chapter->update($data);

        return back()->with('success', 'Chapter updated.');
    }

    public function destroy(Chapter $chapter): RedirectResponse
    {
        $this->authorize('update', $chapter->audiobook);
        $chapter->delete();
        return back()->with('success', 'Chapter deleted.');
    }
}
