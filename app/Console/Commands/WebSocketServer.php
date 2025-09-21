<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\WebSocket\ChatServer;

class WebSocketServer extends Command
{
    protected $signature = 'websocket:serve {--port=8080}';
    protected $description = 'Start the WebSocket server for real-time chat';

    public function handle()
    {
        $port = $this->option('port');
        
        $this->info("Starting WebSocket server on port {$port}...");
        
        // Initialize Laravel application context
        $server = new ChatServer();
        $server->start($port);
    }
}
