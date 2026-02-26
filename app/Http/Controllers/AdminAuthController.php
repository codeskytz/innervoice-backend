<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    /**
     * Admin login - only for admins
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (!$user->is_admin) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        if (!$user->is_verified) {
            return response()->json(['message' => 'Admin account not verified.'], 403);
        }

        $token = Str::random(80);
        $user->api_token = hash('sha256', $token);
        $user->save();

        return response()->json(['token' => $token, 'user' => $user, 'message' => 'Admin logged in successfully'], 200);
    }

    /**
     * Get admin info from auth token
     */
    public function me(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->is_admin) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        return response()->json(['user' => $user], 200);
    }

    /**
     * Logout admin
     */
    public function logout(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->api_token = null;
        $user->save();

        return response()->json(['message' => 'Logged out successfully.'], 200);
    }
}
