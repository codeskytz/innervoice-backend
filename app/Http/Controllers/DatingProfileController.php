<?php

namespace App\Http\Controllers;

use App\Models\DatingMatch;
use App\Models\DatingMessage;
use App\Models\DatingProfile;
use App\Models\User;
use Illuminate\Http\Request;

class DatingProfileController extends Controller
{
    public function index()
    {
        $profiles = DatingProfile::with('user:id,name,email')->get();
        return response()->json(['data' => $profiles]);
    }

    public function show($id)
    {
        $profile = DatingProfile::with('user:id,name,email')->findOrFail($id);
        return response()->json(['data' => $profile]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'nullable|string',
            'bio' => 'nullable|string',
            'age' => 'nullable|integer',
            'gender' => 'nullable|string',
            'photos' => 'nullable|array',
            'location' => 'nullable|string',
            'interests' => 'nullable|array',
        ]);

        $profile = DatingProfile::updateOrCreate(
            ['user_id' => $validated['user_id']],
            $validated
        );

        return response()->json(['data' => $profile], 201);
    }

    public function likeProfile(Request $request, $profileId)
    {
        $likerId = $request->query('liker_id');
        if (!$likerId) {
            return response()->json(['message' => 'Liker ID is required'], 400);
        }

        $likedProfile = DatingProfile::findOrFail($profileId);
        $likedUserId = $likedProfile->user_id;

        // Ensure users don't like themselves
        if ($likerId == $likedUserId) {
            return response()->json(['message' => 'Cannot like yourself'], 400);
        }

        // Check if there's already a match
        $match = DatingMatch::where(function ($query) use ($likerId, $likedUserId) {
            $query->where('user1_id', $likerId)->where('user2_id', $likedUserId);
        })->orWhere(function ($query) use ($likerId, $likedUserId) {
            $query->where('user1_id', $likedUserId)->where('user2_id', $likerId);
        })->first();

        if ($match) {
            // If already pending backwards, it becomes matched
            if ($match->status === 'pending' && $match->user1_id == $likedUserId) {
                $match->update(['status' => 'matched']);
            }
        } else {
            $match = DatingMatch::create([
                'user1_id' => $likerId,
                'user2_id' => $likedUserId,
                'status' => 'pending'
            ]);
        }

        return response()->json(['message' => 'Profile liked successfully', 'match' => $match]);
    }

    public function matches($userId)
    {
        $matches = DatingMatch::where('status', 'matched')
            ->where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)->orWhere('user2_id', $userId);
            })
            ->with(['user1:id,name', 'user2:id,name'])
            ->get();

        return response()->json(['data' => $matches]);
    }

    public function messages($matchId)
    {
        $messages = DatingMessage::where('match_id', $matchId)
            ->with('sender:id,name')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['data' => $messages]);
    }

    public function sendMessage(Request $request, $matchId)
    {
        $validated = $request->validate([
            'sender_id' => 'required|exists:users,id',
            'text' => 'required|string',
        ]);

        $message = DatingMessage::create([
            'match_id' => $matchId,
            'sender_id' => $validated['sender_id'],
            'text' => $validated['text'],
        ]);

        $message->load('sender:id,name');

        return response()->json(['data' => $message], 201);
    }
}
