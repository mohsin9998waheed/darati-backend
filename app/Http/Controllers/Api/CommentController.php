<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function index(Audiobook $audiobook): JsonResponse
    {
        $comments = $audiobook->comments()
            ->with('user:id,name,avatar')
            ->where('is_flagged', false)
            ->paginate(20);
        return response()->json($comments);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audiobook_id' => ['required', 'exists:audiobooks,id'],
            'body'         => ['required', 'string', 'max:1000'],
        ]);

        $comment = Comment::create([
            'user_id'      => Auth::id(),
            'audiobook_id' => $data['audiobook_id'],
            'body'         => $data['body'],
        ]);

        return response()->json($comment->load('user:id,name,avatar'), 201);
    }
}
