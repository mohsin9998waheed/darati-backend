<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AudiobookResource;
use App\Models\Audiobook;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AudiobookController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $uid = Auth::id();

        // Cache anonymous (public) index pages — keyed by query params + version.
        // Authenticated requests skip the cache because they carry per-user
        // 'favorited_by_user' data that must not be shared across users.
        $version  = (int) Cache::get('audiobook_index_version', 0);
        $page     = (int) $request->get('page', 1);
        $cacheKey = $uid
            ? null  // no cache for authenticated users
            : "audiobook_index_v{$version}_{$request->getQueryString()}_p{$page}";

        if ($cacheKey) {
            $audiobooks = Cache::remember($cacheKey, 300, fn () => $this->buildIndexQuery($request, null)->paginate(20));
        } else {
            $audiobooks = $this->buildIndexQuery($request, $uid)->paginate(20);
        }

        return AudiobookResource::collection($audiobooks);
    }

    private function buildIndexQuery(Request $request, ?int $uid)
    {
        $query = Audiobook::with('artist:id,name,avatar', 'category:id,name,slug')
            ->where('status', 'approved');

        if ($uid) {
            $query->withExists(['favorites as favorited_by_user' => fn ($q) => $q->where('user_id', $uid)]);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%")
                  ->orWhereHas('artist', fn ($a) => $a->where('name', 'like', "%{$search}%"));
            });
        }

        if ($category = $request->get('category')) {
            $query->where('category_id', $category);
        }

        if ($language = $request->get('language')) {
            $query->where('language', $language);
        }

        if ($request->boolean('trending')) {
            $query->where('is_trending', true);
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'popular'  => $query->orderBy('total_listens', 'desc'),
            'rating'   => $query->orderBy('avg_rating', 'desc'),
            'trending' => $query->where('is_trending', true)->latest(),
            default    => $query->latest(),
        };

        return $query;
    }

    public function show(Audiobook $audiobook): AudiobookResource
    {
        if ($audiobook->status !== 'approved' && ! (Auth::check() && Auth::user()->isAdmin())) {
            abort(404);
        }

        // Cache the base audiobook data (episodes, chapters) for 10 minutes.
        // Per-user fields (favorited_by_user) are layered on top after the cache hit
        // so we don't store user-specific state in a shared cache key.
        $cacheKey = "audiobook_show_{$audiobook->id}";
        $audiobook = Cache::remember($cacheKey, 600, function () use ($audiobook) {
            $audiobook->load('artist:id,name,avatar,bio', 'category:id,name', 'chapters.episodes');
            return $audiobook;
        });

        if ($uid = Auth::id()) {
            $audiobook->loadExists(['favorites as favorited_by_user' => fn ($q) => $q->where('user_id', $uid)]);
        }

        return new AudiobookResource($audiobook);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'language'    => ['required', 'string', 'max:10'],
            'thumbnail'   => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = app(S3Service::class)->upload($request->file('thumbnail'), 'thumbnails');
        }

        $audiobook = Auth::user()->audiobooks()->create($data);

        // New content — bust the index page caches
        $this->bustIndexCache();

        return response()->json(new AudiobookResource($audiobook), 201);
    }

    public function update(Request $request, Audiobook $audiobook): AudiobookResource
    {
        $this->authorize('update', $audiobook);

        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'language'    => ['sometimes', 'string', 'max:10'],
        ]);

        $audiobook->update($data);

        // Invalidate the show cache for this specific book + index pages
        Cache::forget("audiobook_show_{$audiobook->id}");
        $this->bustIndexCache();

        return new AudiobookResource($audiobook);
    }

    public function destroy(Audiobook $audiobook): JsonResponse
    {
        $this->authorize('delete', $audiobook);

        Cache::forget("audiobook_show_{$audiobook->id}");
        $this->bustIndexCache();

        $audiobook->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    /**
     * Bust cached index pages by bumping a version integer stored in cache.
     * The index() method reads this version into its cache key so any bump
     * causes all existing index keys to become unreachable (they expire naturally).
     */
    private function bustIndexCache(): void
    {
        Cache::increment('audiobook_index_version');
    }
}
