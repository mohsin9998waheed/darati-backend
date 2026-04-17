<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Episode;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EpisodeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chapter_id'  => ['required', 'exists:chapters,id'],
            'title'       => ['required', 'string', 'max:200'],
            'audio_file'  => ['required', 'file', 'mimes:mp3,mpeg,wav,ogg,m4a', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/mp4,audio/x-m4a', 'max:204800'],
            'is_preview'  => ['boolean'],
        ]);

        $chapter = Chapter::findOrFail($data['chapter_id']);
        $this->authorize('update', $chapter->audiobook);

        $file = $request->file('audio_file');
        $path = app(S3Service::class)->upload($file, 'audio');

        $episode = Episode::create([
            'chapter_id'       => $chapter->id,
            'title'            => $data['title'],
            'audio_path'       => $path,
            'file_size'        => $file->getSize(),
            'duration_seconds' => 0,
            'order'            => $chapter->episodes()->max('order') + 1,
            'is_preview'       => $request->boolean('is_preview'),
        ]);

        return response()->json($episode, 201);
    }

    public function update(Request $request, Episode $episode): JsonResponse
    {
        $this->authorize('update', $episode->chapter->audiobook);
        $episode->update($request->validate(['title' => ['required', 'string']]));
        return response()->json($episode);
    }

    public function destroy(Episode $episode): JsonResponse
    {
        $this->authorize('update', $episode->chapter->audiobook);
        app(S3Service::class)->delete($episode->audio_path);
        $episode->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
