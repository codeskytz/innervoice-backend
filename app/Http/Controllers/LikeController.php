<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Confession;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    // Like a confession
    public function store(Request $request, $confessionId)
    {
        $confession = Confession::findOrFail($confessionId);
        $user = auth()->user();

        // Check if user already liked this confession
        $existingLike = Like::where('user_id', $user->id)
            ->where('confession_id', $confessionId)
            ->first();

        if ($existingLike) {
            return response()->json([
                'message' => 'Already liked',
                'already_liked' => true,
            ], 200);
        }

        // Create the like
        Like::create([
            'user_id' => $user->id,
            'confession_id' => $confessionId,
        ]);

        // Increment likes_count on confession
        $confession->increment('likes_count');

        return response()->json([
            'message' => 'Confession liked successfully',
            'likes_count' => $confession->likes_count,
        ], 201);
    }

    // Unlike a confession
    public function destroy(Request $request, $confessionId)
    {
        $confession = Confession::findOrFail($confessionId);
        $user = auth()->user();

        // Find and delete the like
        $like = Like::where('user_id', $user->id)
            ->where('confession_id', $confessionId)
            ->first();

        if (!$like) {
            return response()->json([
                'message' => 'Like not found',
            ], 404);
        }

        $like->delete();

        // Decrement likes_count on confession
        $confession->decrement('likes_count');

        return response()->json([
            'message' => 'Confession unliked successfully',
            'likes_count' => $confession->likes_count,
        ], 200);
    }

    // Check if current user has liked a confession
    public function check(Request $request, $confessionId)
    {
        $user = auth()->user();

        $liked = Like::where('user_id', $user->id)
            ->where('confession_id', $confessionId)
            ->exists();

        return response()->json([
            'liked' => $liked,
        ]);
    }
}
