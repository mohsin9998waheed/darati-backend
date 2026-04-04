<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function index(): JsonResponse
    {
        $favorites = Auth::user()->favorites()->with('audiobook.artist:id,name')->paginate(20);
        return response()->json($favorites);
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
