<?php

namespace App\Http\Controllers;

use App\Models\PostLike;
use App\Models\Post;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    public function store(Request $request, $id)
    {
        $userId = auth()->id();
        $post = Post::findOrFail($id);

        $existing = PostLike::where('user_id', $userId)->where('post_id', $id)->first();
        if ($existing) {
            return response()->json(['message' => 'Already liked'], 409);
        }

        PostLike::create(['user_id' => $userId, 'post_id' => $id]);

        return response()->json([
            'message' => 'Post liked',
            'likes_count' => $post->likes()->count()
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $userId = auth()->id();
        $like = PostLike::where('user_id', $userId)->where('post_id', $id)->first();

        if (!$like) {
            return response()->json(['message' => 'Not liked'], 404);
        }

        $like->delete();
        $post = Post::findOrFail($id);

        return response()->json([
            'message' => 'Post unliked',
            'likes_count' => $post->likes()->count()
        ]);
    }

    public function check(Request $request, $id)
    {
        $userId = auth()->id();
        $liked = PostLike::where('user_id', $userId)->where('post_id', $id)->exists();

        return response()->json(['liked' => $liked]);
    }
}
