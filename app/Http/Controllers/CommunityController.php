<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\CommunityMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommunityController extends Controller
{
    /**
     * Get all communities with member count and latest message.
     */
    public function index()
    {
        $communities = Community::withCount('messages')->get();
        return response()->json(['data' => $communities]);
    }

    /**
     * Get messages for a community (newest last, paginated).
     */
    public function messages(Request $request, $id)
    {
        $community = Community::findOrFail($id);
        $messages = $community->messages()
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'community' => $community,
            'data' => $messages->items(),
            'total' => $messages->total(),
        ]);
    }

    /**
     * Send a message to a community.
     */
    public function sendMessage(Request $request, $id)
    {
        Community::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();
        $message = CommunityMessage::create([
            'community_id' => $id,
            'user_id' => $userId,
            'text' => $request->input('text'),
        ]);

        $message->load('user:id,name');

        return response()->json([
            'message' => 'Message sent',
            'data' => $message,
        ], 201);
    }

    public function show($id)
    {
        $community = Community::withCount('messages')->findOrFail($id);
        return response()->json(['data' => $community]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'category' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Add fillable logic to Community model if needed
        $community = new Community();
        $community->name = $request->input('name');
        $community->description = $request->input('description');
        $community->category = $request->input('category');
        if ($request->has('icon')) {
            $community->icon = $request->input('icon');
        }
        $community->save();

        return response()->json(['data' => $community], 201);
    }

    public function join($id)
    {
        $community = Community::findOrFail($id);
        return response()->json(['message' => 'Joined community successfully', 'data' => $community]);
    }
}
