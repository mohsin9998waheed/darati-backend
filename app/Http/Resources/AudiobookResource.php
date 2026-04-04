<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudiobookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'thumbnail_url' => $this->thumbnail_url,
            'language'      => $this->language,
            'status'        => $this->status,
            'is_trending'   => (bool) $this->is_trending,
            'avg_rating'    => $this->avg_rating,
            'total_listens'      => $this->total_listens,
            'total_play_seconds' => (int) ($this->total_play_seconds ?? 0),
            'is_favorited'  => $this->when(
                $request->user() !== null,
                fn () => (bool) ($this->resource->getAttribute('favorited_by_user') ?? $request->user()->favorites()->where('audiobook_id', $this->id)->exists()),
            ),
            'created_at'    => $this->created_at?->toISOString(),
            'artist'        => $this->whenLoaded('artist', fn () => [
                'id'     => $this->artist->id,
                'name'   => $this->artist->name,
                'avatar' => $this->artist->avatar_url,
                'bio'    => $this->artist->bio,
            ]),
            'category'      => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'chapters'      => $this->whenLoaded('chapters', fn () =>
                $this->chapters->map(fn ($chapter) => [
                    'id'       => $chapter->id,
                    'title'    => $chapter->title,
                    'order'    => $chapter->order,
                    'description' => $chapter->description,
                    'episodes' => $chapter->relationLoaded('episodes')
                        ? $chapter->episodes->map(fn ($ep) => [
                            'id'               => $ep->id,
                            'title'            => $ep->title,
                            'duration_seconds' => $ep->duration_seconds,
                            'is_preview'       => $ep->is_preview,
                            'order'            => $ep->order,
                            'stream_url'       => route('api.stream', $ep->id),
                        ])
                        : [],
                ])
            ),
        ];
    }
}
