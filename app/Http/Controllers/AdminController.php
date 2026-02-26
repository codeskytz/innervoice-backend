<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get all users with pagination
     */
    public function getAllUsers(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $search = $request->query('search');
        $verified = $request->query('verified');

        $query = User::orderBy('created_at', 'desc');

        if ($search) {
            $query->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%");
        }

        if ($verified !== null) {
            $query->where('is_verified', (bool) $verified);
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get single user by ID
     */
    public function getUser($id): JsonResponse
    {
        $user = User::with('confessions')->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['user' => $user], 200);
    }

    /**
     * Update user (promote to admin, verify, disable, etc)
     */
    public function updateUser(Request $request, $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'is_verified' => 'sometimes|boolean',
            'is_admin' => 'sometimes|boolean',
            'name' => 'sometimes|string|max:255',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ], 200);
    }

    /**
     * Delete user and their confessions
     */
    public function deleteUser($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Prevent deleting own admin account (optional safety check)
        if ($user->id === auth()->user()->id) {
            return response()->json(['message' => 'Cannot delete your own admin account.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    /**
     * Get dashboard stats
     */
    public function getStats(): JsonResponse
    {
        $totalUsers = User::count();
        $verifiedUsers = User::where('is_verified', true)->count();
        $adminUsers = User::where('is_admin', true)->count();
        $totalConfessions = \App\Models\Confession::count();
        $totalCategories = \App\Models\Category::count();

        return response()->json([
            'totalUsers' => $totalUsers,
            'verifiedUsers' => $verifiedUsers,
            'adminUsers' => $adminUsers,
            'totalConfessions' => $totalConfessions,
            'totalCategories' => $totalCategories,
        ], 200);
    }
}
