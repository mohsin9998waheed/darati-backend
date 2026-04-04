<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Comment::with('user', 'audiobook');

        if ($request->boolean('flagged')) {
            $query->where('is_flagged', true);
        }

        $comments = $query->latest()->paginate(20)->withQueryString();

        $flaggedCount = Comment::where('is_flagged', true)->count();

        return view('admin.comments.index', compact('comments', 'flaggedCount'));
    }

    public function flag(Comment $comment): RedirectResponse
    {
        $comment->update(['is_flagged' => ! $comment->is_flagged]);
        $status = $comment->is_flagged ? 'flagged' : 'unflagged';
        return back()->with('success', "Comment {$status}.");
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();
        return back()->with('success', 'Comment deleted.');
    }
}
