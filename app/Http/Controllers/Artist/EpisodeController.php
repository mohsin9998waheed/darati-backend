<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Episode;
use App\Services\S3Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EpisodeController extends Controller
{
    private function checkPhpUploadError(string $field): ?string
    {
        if (! array_key_exists($field, $_FILES)) {
            return null;
        }
        $code = (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_OK);
        if ($code === UPLOAD_ERR_OK) {
            return null;
        }
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'File too large (server limit: ' . ini_get('upload_max_filesize') . ')',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit',
            UPLOAD_ERR_PARTIAL    => 'Partially uploaded — try again',
            UPLOAD_ERR_NO_FILE    => 'No file received by the server',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temp folder',
            UPLOAD_ERR_CANT_WRITE => 'Server cannot write uploaded file to disk',
            UPLOAD_ERR_EXTENSION  => 'PHP extension blocked the upload',
        ];
        $msg = $messages[$code] ?? "Unknown PHP upload error (code {$code})";
        Log::error("PHP upload rejected [{$field}]", [
            'error_code'          => $code,
            'error_message'       => $msg,
            'file_size_bytes'     => $_FILES[$field]['size'] ?? 0,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
        ]);
        return $msg;
    }

    public function store(Request $request, Chapter $chapter): RedirectResponse
    {
        $this->authorize('update', $chapter->audiobook);

        if ($err = $this->checkPhpUploadError('audio_file')) {
            return back()->withErrors(['audio_file' => $err]);
        }

        $data = $request->validate([
            'title'      => ['required', 'string', 'max:200'],
            'audio_file' => ['required', 'file', 'mimes:mp3,mpeg,wav,ogg,m4a', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/mp4,audio/x-m4a', 'max:204800'],
            'is_preview' => ['boolean'],
        ]);

        $file = $request->file('audio_file');
        $path = app(S3Service::class)->upload($file, 'audio');

        Episode::create([
            'chapter_id'       => $chapter->id,
            'title'            => $data['title'],
            'audio_path'       => $path,
            'file_size'        => $file->getSize(),
            'duration_seconds' => 0,
            'order'            => $chapter->episodes()->max('order') + 1,
            'is_preview'       => $request->boolean('is_preview'),
        ]);

        return back()->with('success', 'Episode uploaded.');
    }

    public function update(Request $request, Episode $episode): RedirectResponse
    {
        $this->authorize('update', $episode->chapter->audiobook);

        $data = $request->validate(['title' => ['required', 'string', 'max:200']]);
        $episode->update($data);

        return back()->with('success', 'Episode updated.');
    }

    public function destroy(Episode $episode): RedirectResponse
    {
        $this->authorize('update', $episode->chapter->audiobook);

        app(S3Service::class)->delete($episode->audio_path);
        $episode->delete();

        return back()->with('success', 'Episode deleted.');
    }
}
