<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chapters remain as a hidden one-per-book wrapper.
 * Creating/updating/deleting chapters via API is deprecated.
 */
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
        return response()->json([
            'message' => 'Chapters are no longer used. Create episodes on the audiobook instead.',
        ], 422);
    }

    public function update(Request $request, Chapter $chapter): JsonResponse
    {
        return response()->json([
            'message' => 'Chapters are no longer used. Update episodes instead.',
        ], 422);
    }

    public function destroy(Chapter $chapter): JsonResponse
    {
        return response()->json([
            'message' => 'Chapters are no longer used. Delete individual episodes instead.',
        ], 422);
    }
}
