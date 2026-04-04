<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RatingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audiobook_id' => ['required', 'exists:audiobooks,id'],
            'rating'       => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $rating = Auth::user()->ratings()->updateOrCreate(
            ['audiobook_id' => $data['audiobook_id']],
            ['rating' => $data['rating']]
        );

        $avg = DB::table('ratings')->where('audiobook_id', $data['audiobook_id'])->avg('rating');
        Audiobook::where('id', $data['audiobook_id'])->update(['avg_rating' => round($avg, 2)]);

        return response()->json($rating, 201);
    }
}
