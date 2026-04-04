<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CommentModerationController extends Controller
{
    /**
     * Toggle is_flagged — hidden comments are not shown in the mobile API.
     */
    public function toggleFlag(Comment $comment): RedirectResponse
    {
        $comment->load('audiobook');
        if ((int) $comment->audiobook->artist_id !== (int) Auth::id()) {
            abort(403);
        }

        $comment->update(['is_flagged' => ! $comment->is_flagged]);
        $status = $comment->is_flagged ? 'hidden from the app' : 'visible in the app again';

        return back()->with('success', "Comment is now {$status}.");
    }
}
