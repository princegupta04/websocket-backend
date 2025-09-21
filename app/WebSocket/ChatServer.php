<?php

namespace App\WebSocket;

use React\Socket\SocketServer;
use React\EventLoop\Loop;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class ChatServer
{
    protected $clients = [];
    protected $users = [];
    protected $loop;

    public function __construct()
    {
        $this->loop = Loop::get();
    }

    public function start($port = 8080)
    {
        $server = new SocketServer("0.0.0.0:$port", [], $this->loop);
        
        $server->on('connection', function ($connection) {
            $this->handleConnection($connection);
        });

        echo "WebSocket server started on port $port
";
        echo "Waiting for connections...
";
        $this->loop->run();
    }

    protected function handleConnection($connection)
    {
        $resourceId = spl_object_id($connection);
        $buffer = '';
        $handshakeCompleted = false;
        
        echo "New connection attempt: $resourceId
";

        $connection->on('data', function ($data) use ($connection, $resourceId, &$buffer, &$handshakeCompleted) {
            if (!$handshakeCompleted) {
                $buffer .= $data;
                
                // Check if we have a complete HTTP request
                if (strpos($buffer, "

") !== false) {
                    if ($this->performHandshake($connection, $buffer)) {
                        $handshakeCompleted = true;
                        $this->clients[$resourceId] = [
                            'connection' => $connection,
                            'user' => null,
                            'authenticated' => false
                        ];
                        
                        echo "WebSocket handshake completed for: $resourceId
";
                        
                        // Send welcome message
                        $this->sendToClient($connection, [
                            'type' => 'connected',
                            'message' => 'Connected to chat server',
                            'timestamp' => now()->toISOString()
                        ]);
                    } else {
                        echo "Handshake failed for: $resourceId
";
                        $connection->close();
                        return;
                    }
                }
            } else {
                $this->handleWebSocketMessage($connection, $data, $resourceId);
            }
        });

        $connection->on('close', function () use ($resourceId) {
            $this->handleDisconnection($resourceId);
        });

        $connection->on('error', function (\Exception $e) use ($resourceId) {
            echo "Connection error for $resourceId: " . $e->getMessage() . "
";
            $this->handleDisconnection($resourceId);
        });
    }

    protected function performHandshake($connection, $request)
    {
        // Parse HTTP headers
        $lines = explode("
", $request);
        $headers = [];
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }

        // Check if it's a WebSocket upgrade request
        if (!isset($headers['upgrade']) || strtolower($headers['upgrade']) !== 'websocket') {
            echo "Missing or invalid Upgrade header
";
            return false;
        }

        if (!isset($headers['sec-websocket-key'])) {
            echo "Missing Sec-WebSocket-Key header
";
            return false;
        }

        // Generate accept key
        $key = $headers['sec-websocket-key'];
        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        // Send handshake response
        $response = "HTTP/1.1 101 Switching Protocols
" .
                   "Upgrade: websocket
" .
                   "Connection: Upgrade
" .
                   "Sec-WebSocket-Accept: $acceptKey
" .
                   "
";

        $connection->write($response);
        return true;
    }

    protected function handleWebSocketMessage($connection, $rawData, $resourceId)
    {
        $frames = $this->parseWebSocketFrames($rawData);
        
        foreach ($frames as $frame) {
            if ($frame['opcode'] === 1) { // Text frame
                $message = json_decode($frame['payload'], true);
                
                if (!$message) {
                    continue;
                }

                echo "Received message from $resourceId: " . $frame['payload'] . "
";

                switch ($message['type']) {
                    case 'auth':
                        $this->handleAuth($connection, $message, $resourceId);
                        break;
                    case 'message':
                        $this->handleChatMessage($connection, $message, $resourceId);
                        break;
                    case 'typing':
                        $this->handleTyping($connection, $message, $resourceId);
                        break;
                    case 'ping':
                        $this->sendToClient($connection, ['type' => 'pong']);
                        break;
                }
            } elseif ($frame['opcode'] === 8) { // Close frame
                $connection->close();
            } elseif ($frame['opcode'] === 9) { // Ping frame
                $this->sendToClient($connection, ['type' => 'pong']);
            }
        }
    }

    protected function handleAuth($connection, $message, $resourceId)
    {
        if (!isset($message['token'])) {
            $this->sendToClient($connection, [
                'type' => 'auth_error',
                'message' => 'Token required'
            ]);
            return;
        }

        $user = $this->authenticateUser($message['token']);
        
        if (!$user) {
            $this->sendToClient($connection, [
                'type' => 'auth_error',
                'message' => 'Invalid token'
            ]);
            return;
        }

        $this->clients[$resourceId]['user'] = $user;
        $this->clients[$resourceId]['authenticated'] = true;
        
        echo "User authenticated: {$user->name} ($resourceId)
";
        
        $this->sendToClient($connection, [
            'type' => 'auth_success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ]
        ]);

        // Notify others about user joining
        $this->broadcast([
            'type' => 'user_joined',
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ],
            'timestamp' => now()->toISOString()
        ], $resourceId);
    }

    protected function handleChatMessage($connection, $message, $resourceId)
    {
        if (!isset($this->clients[$resourceId]) || !$this->clients[$resourceId]['authenticated']) {
            $this->sendToClient($connection, [
                'type' => 'error',
                'message' => 'Not authenticated'
            ]);
            return;
        }

        $user = $this->clients[$resourceId]['user'];

        // Save message to database
        $chatMessage = Message::create([
            'user_id' => $user->id,
            'message' => $message['message'],
        ]);

        // Load user relationship
        $chatMessage->load('user');

        echo "Message saved from {$user->name}: {$message['message']}
";

        // Broadcast to all authenticated clients
        $this->broadcast([
            'type' => 'message',
            'message' => [
                'id' => $chatMessage->id,
                'user_id' => $chatMessage->user_id,
                'message' => $chatMessage->message,
                'created_at' => $chatMessage->created_at->toISOString(),
                'user' => [
                    'id' => $chatMessage->user->id,
                    'name' => $chatMessage->user->name
                ]
            ]
        ]);
    }

    protected function handleTyping($connection, $message, $resourceId)
    {
        if (!isset($this->clients[$resourceId]) || !$this->clients[$resourceId]['authenticated']) {
            return;
        }

        $user = $this->clients[$resourceId]['user'];

        // Broadcast typing indicator to others (not sender)
        $this->broadcast([
            'type' => 'typing',
            'userId' => $user->id,
            'userName' => $user->name,
            'isTyping' => $message['isTyping'] ?? false
        ], $resourceId);
    }

    protected function handleDisconnection($resourceId)
    {
        echo "Connection closed: $resourceId
";
        
        if (isset($this->clients[$resourceId]) && $this->clients[$resourceId]['authenticated']) {
            $user = $this->clients[$resourceId]['user'];
            
            // Notify others about user leaving
            $this->broadcast([
                'type' => 'user_left',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name
                ],
                'timestamp' => now()->toISOString()
            ], $resourceId);
        }
        
        if (isset($this->clients[$resourceId])) {
            unset($this->clients[$resourceId]);
        }
    }

    protected function broadcast($data, $excludeResourceId = null)
    {
        $frame = $this->createWebSocketFrame(json_encode($data));
        $count = 0;
        
        foreach ($this->clients as $resourceId => $client) {
            if ($resourceId !== $excludeResourceId && $client['authenticated']) {
                $client['connection']->write($frame);
                $count++;
            }
        }
        
        echo "Broadcasted message to $count clients
";
    }

    protected function sendToClient($connection, $data)
    {
        $frame = $this->createWebSocketFrame(json_encode($data));
        $connection->write($frame);
    }

    protected function authenticateUser($token)
    {
        try {
            // Use Laravel Sanctum for token verification
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            return $personalAccessToken ? $personalAccessToken->tokenable : null;
        } catch (\Exception $e) {
            echo "Authentication failed: " . $e->getMessage() . "
";
            return null;
        }
    }

    protected function parseWebSocketFrames($data)
    {
        $frames = [];
        $offset = 0;
        $dataLength = strlen($data);

        while ($offset < $dataLength) {
            if ($dataLength - $offset < 2) {
                break; // Not enough data for a frame header
            }

            $firstByte = ord($data[$offset]);
            $secondByte = ord($data[$offset + 1]);

            $fin = ($firstByte >> 7) & 1;
            $opcode = $firstByte & 0xF;
            $masked = ($secondByte >> 7) & 1;
            $payloadLength = $secondByte & 0x7F;

            $headerLength = 2;

            if ($payloadLength === 126) {
                if ($dataLength - $offset < 4) break;
                $payloadLength = unpack('n', substr($data, $offset + 2, 2))[1];
                $headerLength = 4;
            } elseif ($payloadLength === 127) {
                if ($dataLength - $offset < 10) break;
                $payloadLength = unpack('J', substr($data, $offset + 2, 8))[1];
                $headerLength = 10;
            }

            if ($masked) {
                $headerLength += 4;
            }

            if ($dataLength - $offset < $headerLength + $payloadLength) {
                break; // Not enough data for complete frame
            }

            $payload = '';
            if ($payloadLength > 0) {
                if ($masked) {
                    $maskingKey = substr($data, $offset + $headerLength - 4, 4);
                    $encodedPayload = substr($data, $offset + $headerLength, $payloadLength);
                    
                    for ($i = 0; $i < $payloadLength; $i++) {
                        $payload .= chr(ord($encodedPayload[$i]) ^ ord($maskingKey[$i % 4]));
                    }
                } else {
                    $payload = substr($data, $offset + $headerLength, $payloadLength);
                }
            }

            $frames[] = [
                'fin' => $fin,
                'opcode' => $opcode,
                'payload' => $payload
            ];

            $offset += $headerLength + $payloadLength;
        }

        return $frames;
    }

    protected function createWebSocketFrame($data, $opcode = 0x1)
    {
        $length = strlen($data);
        $frame = '';

        // First byte: FIN (1) + RSV (000) + Opcode
        $frame .= chr(0x80 | $opcode);

        // Payload length (server to client frames are not masked)
        if ($length < 126) {
            $frame .= chr($length);
        } elseif ($length < 65536) {
            $frame .= chr(126) . pack('n', $length);
        } else {
            $frame .= chr(127) . pack('J', $length);
        }

        $frame .= $data;
        
        return $frame;
    }
}
