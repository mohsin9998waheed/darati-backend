<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Category;
use App\Services\S3Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AudiobookController extends Controller
{
    public function index(): View
    {
        $audiobooks = Auth::user()->audiobooks()
            ->with('category')
            ->withCount('chapters')
            ->latest()
            ->paginate(15);

        return view('artist.audiobooks.index', compact('audiobooks'));
    }

    public function create(): View
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('artist.audiobooks.create', compact('categories'));
    }

    private function checkPhpUploadError(Request $request, string $field): ?string
    {
        if (! array_key_exists($field, $_FILES)) {
            return null; // no file submitted — that's fine
        }

        $code = (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_OK);

        if ($code === UPLOAD_ERR_OK) {
            return null;
        }

        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'File is too large (exceeds server upload_max_filesize: ' . ini_get('upload_max_filesize') . ')',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the MAX_FILE_SIZE set in the form',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded — try again',
            UPLOAD_ERR_NO_FILE    => 'No file was received by the server',
            UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary upload folder',
            UPLOAD_ERR_CANT_WRITE => 'Server failed to write the uploaded file to disk',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload',
        ];

        $msg = $messages[$code] ?? "Unknown PHP upload error (code {$code})";

        Log::error("PHP upload rejected [{$field}]", [
            'error_code'          => $code,
            'error_message'       => $msg,
            'file_size_bytes'     => $_FILES[$field]['size'] ?? 0,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
            'memory_limit'        => ini_get('memory_limit'),
        ]);

        return $msg;
    }

    public function store(Request $request): RedirectResponse
    {
        if ($err = $this->checkPhpUploadError($request, 'thumbnail')) {
            return back()->withErrors(['thumbnail' => $err])->withInput();
        }

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'author_name' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'language'    => ['required', 'string', 'max:10'],
            'thumbnail'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = app(S3Service::class)->upload($request->file('thumbnail'), 'thumbnails');
        } else {
            unset($data['thumbnail']);
        }

        $data['artist_id'] = Auth::id();
        $data['status']    = 'pending';

        $audiobook = Audiobook::create($data);

        return redirect()->route('artist.audiobooks.show', $audiobook)
            ->with('success', 'Audiobook created! Add chapters and episodes next.');
    }

    public function show(Audiobook $audiobook): View
    {
        $this->authorize('update', $audiobook);
        $audiobook->load('chapters.episodes', 'category');
        $audiobook->loadCount(['favorites', 'ratings', 'comments']);
        $totalEpisodes = $audiobook->chapters->sum(fn ($c) => $c->episodes->count());
        $totalDuration = $audiobook->chapters->sum(fn ($c) => $c->episodes->sum('duration_seconds'));
        $recentRatings = $audiobook->ratings()->with('user:id,name')->latest()->take(50)->get();
        $recentComments = $audiobook->comments()->with('user:id,name')->latest()->take(50)->get();

        return view('artist.audiobooks.show', compact(
            'audiobook',
            'totalEpisodes',
            'totalDuration',
            'recentRatings',
            'recentComments',
        ));
    }

    public function edit(Audiobook $audiobook): View
    {
        $this->authorize('update', $audiobook);
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('artist.audiobooks.edit', compact('audiobook', 'categories'));
    }

    public function update(Request $request, Audiobook $audiobook): RedirectResponse
    {
        $this->authorize('update', $audiobook);

        if ($err = $this->checkPhpUploadError($request, 'thumbnail')) {
            return back()->withErrors(['thumbnail' => $err])->withInput();
        }

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'author_name' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'language'    => ['required', 'string', 'max:10'],
            'thumbnail'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $s3 = app(S3Service::class);
            $s3->delete($audiobook->thumbnail);
            $data['thumbnail'] = $s3->upload($request->file('thumbnail'), 'thumbnails');
        } else {
            // No new file chosen — preserve the existing thumbnail instead of wiping it
            unset($data['thumbnail']);
        }

        $audiobook->update($data);

        return back()->with('success', 'Audiobook updated.');
    }

    public function destroy(Audiobook $audiobook): RedirectResponse
    {
        $this->authorize('delete', $audiobook);
        $audiobook->delete();
        return redirect()->route('artist.audiobooks.index')->with('success', 'Audiobook deleted.');
    }
}
