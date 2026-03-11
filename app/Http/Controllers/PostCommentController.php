<?php

namespace App\Http\Controllers;

use App\Models\PostComment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostCommentController extends Controller
{
    public function index(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $comments = $post->comments()
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $comments,
            'count' => $comments->count()
        ]);
    }

    public function store(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = auth()->id();
        Post::findOrFail($id);

        $comment = PostComment::create([
            'user_id' => $userId,
            'post_id' => $id,
            'text' => $request->input('text'),
        ]);

        $comment->load('user:id,name');

        return response()->json([
            'message' => 'Comment added',
            'data' => $comment
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $userId = auth()->id();
        $comment = PostComment::where('id', $id)->where('user_id', $userId)->first();

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
