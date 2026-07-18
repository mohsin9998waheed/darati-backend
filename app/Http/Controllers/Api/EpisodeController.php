<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\TranscodeAudioJob;
use App\Models\Audiobook;
use App\Models\Chapter;
use App\Models\Episode;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EpisodeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audiobook_id' => ['nullable', 'exists:audiobooks,id'],
            'chapter_id'   => ['nullable', 'exists:chapters,id'],
            'title'        => ['required', 'string', 'max:200'],
            'audio_file'   => [
                'required', 'file',
                'mimes:mp3,mpeg,wav,ogg,m4a',
                'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/mp4,audio/x-m4a,application/octet-stream',
                'max:524288', // 512 MB
            ],
            'is_preview'   => ['boolean'],
        ]);

        $chapter = $this->resolveChapter($data);
        $this->authorize('update', $chapter->audiobook);

        $file   = $request->file('audio_file');
        $rawKey = app(S3Service::class)->upload($file, 'audio-raw');

        $episode = Episode::create([
            'chapter_id'        => $chapter->id,
            'title'             => $data['title'],
            'audio_path'        => $rawKey,
            'raw_audio_path'    => $rawKey,
            'file_size'         => $file->getSize(),
            'duration_seconds'  => 0,
            'order'             => $chapter->episodes()->max('order') + 1,
            'is_preview'        => $request->boolean('is_preview'),
            'processing_status' => 'queued',
        ]);

        TranscodeAudioJob::dispatch($episode->id, $rawKey);

        return response()->json($episode, 201);
    }

    /**
     * Prefer audiobook_id (new Book → Episodes flow); accept chapter_id for older clients.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveChapter(array $data): Chapter
    {
        if (! empty($data['audiobook_id'])) {
            $audiobook = Audiobook::findOrFail($data['audiobook_id']);

            return $audiobook->ensureDefaultChapter();
        }

        if (! empty($data['chapter_id'])) {
            return Chapter::findOrFail($data['chapter_id']);
        }

        throw ValidationException::withMessages([
            'audiobook_id' => 'Provide audiobook_id (preferred) or chapter_id.',
        ]);
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

        $s3 = app(S3Service::class);
        $s3->delete($episode->audio_path);
        if ($episode->raw_audio_path && $episode->raw_audio_path !== $episode->audio_path) {
            $s3->delete($episode->raw_audio_path);
        }
        $episode->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function status(Episode $episode): JsonResponse
    {
        return response()->json([
            'id'                => $episode->id,
            'processing_status' => $episode->processing_status,
            'duration_seconds'  => $episode->duration_seconds,
            'file_size'         => $episode->file_size,
        ]);
    }
}
