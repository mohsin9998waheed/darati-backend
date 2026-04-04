<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function index(Audiobook $audiobook): JsonResponse
    {
        if ($audiobook->status !== 'approved') {
            abort(404);
        }
        return response()->json($audiobook->chapters()->with('episodes')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audiobook_id' => ['required', 'exists:audiobooks,id'],
            'title'        => ['required', 'string', 'max:200'],
        ]);

        $audiobook = Audiobook::findOrFail($data['audiobook_id']);
        $this->authorize('update', $audiobook);

        $chapter = $audiobook->chapters()->create([
            'title' => $data['title'],
            'order' => $audiobook->chapters()->max('order') + 1,
        ]);

        return response()->json($chapter, 201);
    }

    public function update(Request $request, Chapter $chapter): JsonResponse
    {
        $this->authorize('update', $chapter->audiobook);
        $chapter->update($request->validate(['title' => ['required', 'string', 'max:200']]));
        return response()->json($chapter);
    }

    public function destroy(Chapter $chapter): JsonResponse
    {
        $this->authorize('update', $chapter->audiobook);
        $chapter->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
