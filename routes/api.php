<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Test endpoint to verify API is working
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'timestamp' => now(),
        'csrf_required' => false
    ]);
});

// Public routes (no authentication required)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', function (Request $request) {
        return $request->user();
    });
    Route::post('logout', [AuthController::class, 'logout']);
    
    // Chat routes
    Route::get('messages', [ChatController::class, 'fetchMessages']);
    Route::post('messages', [ChatController::class, 'sendMessage']);
    Route::get('messages/history', [ChatController::class, 'getMessages']);
    
    // WebSocket info
    Route::get('websocket/info', function (Request $request) {
        return response()->json([
            'url' => 'ws://127.0.0.1:8080',
            'token' => $request->user()->currentAccessToken()->plainTextToken ?? null,
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name
            ]
        ]);
    });
    
    // Health check
    Route::get('health', function () {
        return response()->json(['status' => 'ok', 'timestamp' => now()]);
    });
});