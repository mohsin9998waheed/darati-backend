<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AudiobookResource;
use App\Models\Audiobook;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = Auth::id();
        $audiobookIds = Favorite::where('user_id', $userId)->pluck('audiobook_id');

        $audiobooks = Audiobook::with('artist:id,name,avatar', 'category:id,name,slug')
            ->whereIn('id', $audiobookIds)
            ->withExists(['favorites as favorited_by_user' => fn ($q) => $q->where('user_id', $userId)])
            ->latest()
            ->paginate(50);

        return response()->json(AudiobookResource::collection($audiobooks));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['audiobook_id' => ['required', 'exists:audiobooks,id']]);

        $favorite = Favorite::firstOrCreate([
            'user_id'      => Auth::id(),
            'audiobook_id' => $data['audiobook_id'],
        ]);

        return response()->json($favorite, 201);
    }

    public function destroy(int $audiobookId): JsonResponse
    {
        Favorite::where('user_id', Auth::id())->where('audiobook_id', $audiobookId)->delete();
        return response()->json(['message' => 'Removed from favorites.']);
    }
}
