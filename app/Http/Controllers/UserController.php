<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function show($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->avatar) {
            $user->avatar = Storage::url($user->avatar);
        }

        return response()->json(['data' => $user]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['data' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:6',
            'nickname' => 'sometimes|nullable|string',
            'bio' => 'sometimes|nullable|string',
            'age' => 'sometimes|nullable|integer',
            'gender' => 'sometimes|nullable|string',
            'interests' => 'sometimes|nullable|array',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json(['data' => $user]);
    }
    public function uploadAvatar(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($request->hasFile('avatar')) {
            // Delete old avatar if it exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            
            $user->update([
                'avatar' => $path
            ]);

            return response()->json([
                'message' => 'Avatar updated successfully', 
                'avatar_url' => Storage::url($path)
            ]);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }
}
