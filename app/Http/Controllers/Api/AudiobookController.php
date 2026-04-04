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

class AudiobookController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Audiobook::with('artist:id,name,avatar', 'category:id,name,slug')
            ->where('status', 'approved');

        if ($uid = Auth::id()) {
            $query->withExists(['favorites as favorited_by_user' => fn ($q) => $q->where('user_id', $uid)]);
        }

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
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

        $audiobooks = $query->paginate(20);

        return AudiobookResource::collection($audiobooks);
    }

    public function show(Audiobook $audiobook): AudiobookResource
    {
        if ($audiobook->status !== 'approved' && ! (Auth::check() && Auth::user()->isAdmin())) {
            abort(404);
        }

        $audiobook->load('artist:id,name,avatar,bio', 'category:id,name', 'chapters.episodes');

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

        return new AudiobookResource($audiobook);
    }

    public function destroy(Audiobook $audiobook): JsonResponse
    {
        $this->authorize('delete', $audiobook);
        $audiobook->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
