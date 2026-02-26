<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Confession;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Get all comments for a confession (with nested replies)
    public function index($confessionId)
    {
        $confession = Confession::findOrFail($confessionId);

        $comments = Comment::where('confession_id', $confessionId)
            ->whereNull('parent_comment_id')
            ->with(['user', 'replies.user'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($comment) {
                return $this->formatComment($comment);
            });

        return response()->json([
            'data' => $comments,
            'count' => $comments->count(),
        ]);
    }

    // Create a new comment on a confession
    public function store(Request $request, $confessionId)
    {
        $request->validate([
            'text' => 'required|string|min:1|max:1000',
            'parent_comment_id' => 'nullable|exists:comments,id',
        ]);

        $confession = Confession::findOrFail($confessionId);
        $user = auth()->user();

        // Verify that parent_comment_id belongs to the same confession (if provided)
        if ($request->parent_comment_id) {
            $parentComment = Comment::findOrFail($request->parent_comment_id);
            if ($parentComment->confession_id !== $confession->id) {
                return response()->json([
                    'message' => 'Invalid parent comment',
                ], 422);
            }
        }

        $comment = Comment::create([
            'user_id' => $user->id,
            'confession_id' => $confessionId,
            'text' => $request->text,
            'parent_comment_id' => $request->parent_comment_id,
        ]);

        // Increment comments_count on confession
        $confession->increment('comments_count');

        // Load relationships
        $comment->load('user', 'replies');

        return response()->json([
            'data' => $this->formatComment($comment),
            'message' => 'Comment created successfully',
        ], 201);
    }

    // Update a comment
    public function update(Request $request, $commentId)
    {
        $request->validate([
            'text' => 'required|string|min:1|max:1000',
        ]);

        $comment = Comment::findOrFail($commentId);
        $user = auth()->user();

        // Check authorization
        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $comment->update([
            'text' => $request->text,
        ]);

        $comment->load('user', 'replies');

        return response()->json([
            'data' => $this->formatComment($comment),
            'message' => 'Comment updated successfully',
        ]);
    }

    // Delete a comment
    public function destroy($commentId)
    {
        $comment = Comment::findOrFail($commentId);
        $user = auth()->user();

        // Check authorization
        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $confessionId = $comment->confession_id;
        $confession = Confession::find($confessionId);

        // Delete the comment (cascades to replies)
        $comment->delete();

        // Decrement comments_count on confession (for root comments only)
        if ($confession && $comment->isRootComment()) {
            $confession->decrement('comments_count');
        }

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }

    // Get replies for a specific comment
    public function getReplies($commentId)
    {
        $comment = Comment::findOrFail($commentId);

        $replies = $comment->replies()
            ->with('user')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($reply) {
                return $this->formatComment($reply);
            });

        return response()->json([
            'data' => $replies,
            'count' => $replies->count(),
        ]);
    }

    // Add a reply to a comment
    public function addReply(Request $request, $commentId)
    {
        $request->validate([
            'text' => 'required|string|min:1|max:1000',
        ]);

        $parentComment = Comment::findOrFail($commentId);
        $user = auth()->user();

        $reply = Comment::create([
            'user_id' => $user->id,
            'confession_id' => $parentComment->confession_id,
            'text' => $request->text,
            'parent_comment_id' => $commentId,
        ]);

        // Increment comments_count on confession
        $confession = Confession::find($parentComment->confession_id);
        if ($confession) {
            $confession->increment('comments_count');
        }

        $reply->load('user');

        return response()->json([
            'data' => $this->formatComment($reply),
            'message' => 'Reply added successfully',
        ], 201);
    }

    // Helper function to format comment with nested replies
    private function formatComment($comment)
    {
        return [
            'id' => $comment->id,
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
                'is_anonymous' => false,
            ],
            'text' => $comment->text,
            'likes_count' => $comment->likes_count,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
            'parent_comment_id' => $comment->parent_comment_id,
            'replies' => $comment->replies ? $comment->replies->map(function ($reply) {
                return $this->formatComment($reply);
            })->toArray() : [],
        ];
    }
}
