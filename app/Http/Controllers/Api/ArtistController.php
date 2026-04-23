<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ArtistController extends Controller
{
    /**
     * GET /api/artists
     * Returns artists who have at least one approved audiobook,
     * ordered by total approved books (most prolific first).
     */
    public function index(): JsonResponse
    {
        $artists = User::where('role', 'artist')
            ->whereHas('audiobooks', fn ($q) => $q->where('status', 'approved'))
            ->withCount(['audiobooks as books_count' => fn ($q) => $q->where('status', 'approved')])
            ->orderByDesc('books_count')
            ->limit(20)
            ->get(['id', 'name', 'avatar', 'bio']);

        $payload = $artists->map(fn ($a) => [
            'id'          => $a->id,
            'name'        => $a->name,
            'avatar'      => $a->avatar_url,
            'bio'         => $a->bio,
            'books_count' => (int) $a->books_count,
        ]);

        return response()->json(['data' => $payload]);
    }

    /**
     * GET /api/artists/{id}/books
     * Returns the artist's profile and all their approved audiobooks.
     */
    public function books(int $id): JsonResponse
    {
        $artist = User::where('role', 'artist')->findOrFail($id);

        $books = $artist->audiobooks()
            ->with('category:id,name')
            ->where('status', 'approved')
            ->orderByDesc('total_listens')
            ->get();

        return response()->json([
            'artist' => [
                'id'          => $artist->id,
                'name'        => $artist->name,
                'avatar'      => $artist->avatar_url,
                'bio'         => $artist->bio,
                'books_count' => $books->count(),
            ],
            'books' => $books->map(fn ($b) => [
                'id'            => $b->id,
                'title'         => $b->title,
                'description'   => $b->description,
                'author_name'   => $b->author_name,
                'thumbnail_url' => $b->thumbnail_url,
                'language'      => $b->language,
                'status'        => $b->status,
                'avg_rating'    => (float) $b->avg_rating,
                'total_listens' => (int) $b->total_listens,
                'is_trending'   => (bool) $b->is_trending,
                'is_favorited'  => false,
                'category'      => $b->category
                    ? ['id' => $b->category->id, 'name' => $b->category->name]
                    : null,
            ]),
        ]);
    }
}
