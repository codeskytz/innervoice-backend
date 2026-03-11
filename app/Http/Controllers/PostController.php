<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * Get all posts, optionally filtering by dating related.
     */
    public function index(Request $request)
    {
        $posts = Post::with('user:id,name')
            ->withCount(['likes', 'comments'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Check if user is authenticated to include liked status
        $userId = auth()->id();

        $posts->transform(function ($post) use ($userId) {
            $post->image_url = url(Storage::url($post->image_url));
            $post->liked = $userId
                ? $post->likes()->where('user_id', $userId)->exists()
                : false;
            return $post;
        });

        return response()->json([
            'status' => 'success',
            'data' => $posts
        ]);
    }

    /**
     * Store a new picture post.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
            'caption' => 'nullable|string|max:500',
            'is_dating_related' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = auth()->id();

            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $imagePath = $request->file('image')->store('posts', 'public');

            $post = Post::create([
                'user_id' => $userId,
                'caption' => $request->input('caption'),
                'image_url' => $imagePath,
                'is_dating_related' => $request->input('is_dating_related', true)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Post created successfully',
                'data' => clone $post
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create post',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
