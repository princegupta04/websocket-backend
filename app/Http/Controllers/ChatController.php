<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    /**
     * Fetch all messages with user information
     */
    public function fetchMessages(): JsonResponse
    {
        $messages = Message::with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'messages' => $messages,
            'count' => $messages->count()
        ]);
    }

    /**
     * Send a new message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = $request->user()->messages()->create([
            'message' => $request->input('message'),
        ]);

        // Load user relationship
        $message->load('user:id,name');

        // Note: WebSocket broadcasting is handled separately by the WebSocket server
        // broadcast(new MessageSent($message))->toOthers(); // Removed

        return response()->json([
            'message' => $message,
            'status' => 'Message sent successfully'
        ], 201);
    }

    /**
     * Get message history for pagination
     */
    public function getMessages(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);

        $messages = Message::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'messages' => $messages,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => Message::count()
            ]
        ]);
    }
}
