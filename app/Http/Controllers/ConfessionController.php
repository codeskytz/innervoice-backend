<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use Illuminate\Http\Request;

class ConfessionController extends Controller
{
    /**
     * Get all confessions for the feed (paginated, public)
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $category = $request->query('category');

        $query = Confession::with('user')
            ->published()
            ->latest();

        if ($category && $category !== 'All') {
            $query->where('category', $category);
        }

        $confessions = $query->paginate($perPage);

        return response()->json([
            'data' => $confessions->items(),
            'pagination' => [
                'total' => $confessions->total(),
                'per_page' => $confessions->perPage(),
                'current_page' => $confessions->currentPage(),
                'last_page' => $confessions->lastPage(),
                'from' => $confessions->firstItem(),
                'to' => $confessions->lastItem(),
            ],
        ], 200);
    }

    /**
     * Get a specific confession
     */
    public function show($id)
    {
        $confession = Confession::with('user')->find($id);

        if (!$confession) {
            return response()->json(['message' => 'Confession not found'], 404);
        }

        return response()->json(['confession' => $confession], 200);
    }

    /**
     * Get current user's confessions
     */
    public function myConfessions(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $perPage = $request->query('per_page', 15);
        $confessions = Confession::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $confessions->items(),
            'pagination' => [
                'total' => $confessions->total(),
                'per_page' => $confessions->perPage(),
                'current_page' => $confessions->currentPage(),
                'last_page' => $confessions->lastPage(),
            ],
        ], 200);
    }

    /**
     * Create a new confession
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'category' => 'required|string|max:100',
            'is_anonymous' => 'boolean',
        ]);

        try {
            $confession = Confession::create([
                'user_id' => $user->id,
                'text' => $validated['text'],
                'category' => $validated['category'],
                'is_anonymous' => $validated['is_anonymous'] ?? true,
            ]);

            // Reload with user relationship
            $confession->load('user');

            return response()->json([
                'message' => 'Confession created successfully',
                'confession' => $confession,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create confession',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a confession
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $confession = Confession::find($id);

        if (!$confession) {
            return response()->json(['message' => 'Confession not found'], 404);
        }

        if ($confession->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'text' => 'sometimes|string|max:5000',
            'category' => 'sometimes|string|max:100',
            'is_anonymous' => 'sometimes|boolean',
        ]);

        try {
            $confession->update($validated);
            $confession->load('user');

            return response()->json([
                'message' => 'Confession updated successfully',
                'confession' => $confession,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update confession',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a confession
     */
    public function destroy($id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $confession = Confession::find($id);

        if (!$confession) {
            return response()->json(['message' => 'Confession not found'], 404);
        }

        if ($confession->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $confession->delete();

            return response()->json(['message' => 'Confession deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete confession',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
