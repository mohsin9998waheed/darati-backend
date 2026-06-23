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
            'author_name'   => $this->author_name,
            'thumbnail_url' => $this->thumbnail_url,
            'language'      => $this->language,
            'status'        => $this->status,
            'is_trending'   => (bool) $this->is_trending,
            'avg_rating'    => $this->avg_rating,
            'total_listens'      => $this->total_listens,
            'total_play_seconds' => (int) ($this->total_play_seconds ?? 0),
            'total_cycles'       => (int) ($this->total_cycles ?? 0),
            'is_favorited'  => $this->when(
                $request->user() !== null,
                fn () => (bool) ($this->resource->getAttribute('favorited_by_user') ?? $request->user()->favorites()->where('audiobook_id', $this->id)->exists()),
            ),
            'created_at'    => $this->created_at?->toISOString(),
            'artist'        => $this->whenLoaded('artist', fn () => $this->artist ? [
                'id'     => $this->artist->id,
                'name'   => $this->artist->name,
                'avatar' => $this->artist->avatar_url,
                'bio'    => $this->artist->bio,
            ] : null),
            'category'      => $this->whenLoaded('category', fn () => $this->category ? [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'chapters'      => $this->whenLoaded('chapters', fn () =>
                $this->chapters->map(fn ($chapter) => [
                    'id'       => $chapter->id,
                    'title'    => $chapter->title,
                    'order'    => $chapter->order,
                    'description' => $chapter->description,
                    'episodes' => $chapter->relationLoaded('episodes')
                        ? $chapter->episodes->map(fn ($ep) => [
                            'id'                    => $ep->id,
                            'title'                 => $ep->title,
                            'duration_seconds'      => $ep->duration_seconds,
                            'is_preview'            => $ep->is_preview,
                            'order'                 => $ep->order,
                            'processing_status'     => $ep->processing_status,
                            // is_playable: explicit boolean the client can branch on without
                            // inspecting processing_status itself.
                            'is_playable'           => $ep->isPlayable(),
                            // playback_block_reason: machine-readable string when not playable,
                            // null when playable. Clients use this for precise UX copy.
                            // Values: 'processing' | 'processing_failed' | 'audio_missing' | null
                            'playback_block_reason' => $ep->playbackBlockReason(),
                            // stream_url only present when episode is playable; null otherwise
                            // so the client never attempts to play an unready episode.
                            'stream_url'            => $ep->isPlayable()
                                ? route('api.stream', $ep->id)
                                : null,
                        ])
                        : [],
                ])
            ),
        ];
    }
}
