<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestWebSocket extends Command
{
    protected $signature = 'websocket:test';
    protected $description = 'Test WebSocket server connectivity';

    public function handle()
    {
        $this->info('Testing WebSocket server...');
        
        // Create a test user token for testing
        $user = User::first();
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return;
        }
        
        $token = $user->createToken('websocket-test')->plainTextToken;
        
        $this->info("Test user: {$user->name}");
        $this->info("Test token: {$token}");
        $this->info("WebSocket URL: ws://127.0.0.1:8080");
        $this->info("");
        $this->info("You can use these credentials to test your React frontend:");
        $this->info("1. Connect to ws://127.0.0.1:8080");
        $this->info("2. Send auth message: {\"type\":\"auth\",\"token\":\"{$token}\"}");
        $this->info("3. Send chat message: {\"type\":\"message\",\"message\":\"Hello World!\"}");
        
        return 0;
    }
}
