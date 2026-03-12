<?php

namespace App\Http\Controllers;

use App\Models\DatingMatch;
use App\Models\DatingMessage;
use App\Models\DatingProfile;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatingProfileController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        if (!$userId) {
             return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get IDs of users the current user has already liked/matched with or been matched with
        $interactedUserIds = DatingMatch::where('user1_id', $userId)
            ->pluck('user2_id')
            ->concat(DatingMatch::where('user2_id', $userId)->pluck('user1_id'))
            ->toArray();

        // Also add the current user's ID to exclude themselves
        $interactedUserIds[] = $userId;

        $profiles = DatingProfile::with('user:id,name,email,avatar')
            ->whereNotIn('user_id', $interactedUserIds)
            ->get();

        // Map relative photo paths to URLs
        $profiles->transform(function ($profile) {
            if (is_array($profile->photos)) {
                $profile->photos = array_map(function ($photo) {
                     // Check if it's an external URL (e.g., placeholder) or a local storage path
                     return filter_var($photo, FILTER_VALIDATE_URL) ? $photo : Storage::url($photo);
                }, $profile->photos);
            }
            // Map user avatar
            if ($profile->user && $profile->user->avatar) {
                 $profile->user->avatar = Storage::url($profile->user->avatar);
            }
            return $profile;
        });

        return response()->json(['data' => $profiles]);
    }

    public function show($id)
    {
        $profile = DatingProfile::with('user:id,name,email,avatar')->findOrFail($id);
        
         if (is_array($profile->photos)) {
                $profile->photos = array_map(function ($photo) {
                     return filter_var($photo, FILTER_VALIDATE_URL) ? $photo : Storage::url($photo);
                }, $profile->photos);
         }
         if ($profile->user && $profile->user->avatar) {
                 $profile->user->avatar = Storage::url($profile->user->avatar);
         }

        return response()->json(['data' => $profile]);
    }

    public function store(Request $request)
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'nickname' => 'required|string',
            'bio' => 'nullable|string',
            'age' => 'required|integer',
            'gender' => 'nullable|string',
            'picture_base64' => 'nullable|string',
            'location' => 'nullable|string',
            'interests' => 'nullable|array',
        ]);

        $photos = [];
        if (!empty($validated['picture_base64'])) {
            // Check if it's already a URL (e.g., from seed data) or a base64 string
            if (filter_var($validated['picture_base64'], FILTER_VALIDATE_URL)) {
                 $photos[] = $validated['picture_base64'];
            } else {
                // Decode base64 and store it securely
                $imageParts = explode(";base64,", $validated['picture_base64']);
                if (count($imageParts) == 2) {
                    $imageTypeAux = explode("image/", $imageParts[0]);
                    $imageType = count($imageTypeAux) > 1 ? $imageTypeAux[1] : 'jpeg';
                    $imageBase64 = base64_decode($imageParts[1]);
                    $fileName = 'dating_photos/' . $userId . '_' . Str::random(10) . '.' . $imageType;
                    
                    Storage::disk('public')->put($fileName, $imageBase64);
                    $photos[] = $fileName;
                }
            }
        }

        $profile = DatingProfile::updateOrCreate(
            ['user_id' => $userId],
            [
                'name' => $validated['nickname'], // Map nickname payload to database 'name'
                'bio' => $validated['bio'],
                'age' => $validated['age'],
                'gender' => $validated['gender'] ?? null,
                'photos' => empty($photos) ? null : collect($photos)->toArray(), // Overwrite photos array with the new one
                'location' => $validated['location'] ?? null,
                'interests' => collect($validated['interests'] ?? [])->toArray(),
            ]
        );

        return response()->json(['data' => $profile], 201);
    }

    public function likeProfile(Request $request, $profileId)
    {
        $likerId = auth()->id();
        
        if (!$likerId) {
            return response()->json(['message' => 'Unauthorized'], 401);
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
