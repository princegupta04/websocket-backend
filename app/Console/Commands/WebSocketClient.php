<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Loop;
use React\Socket\Connector;
use App\Models\User;

class WebSocketClient extends Command
{
    protected $signature = 'websocket:client {--host=127.0.0.1} {--port=8080}';
    protected $description = 'Test WebSocket client to verify server functionality';

    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');
        
        $this->info("Connecting to WebSocket server at {$host}:{$port}...");
        
        $loop = Loop::get();
        $connector = new Connector($loop);
        
        // Get a test user and token
        $user = User::first();
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return 1;
        }
        
        $token = $user->createToken('websocket-client-test')->plainTextToken;
        
        $connector->connect("tcp://{$host}:{$port}")
            ->then(function ($connection) use ($token, $user) {
                $this->info('Connected! Performing WebSocket handshake...');
                
                // Send WebSocket handshake
                $handshake = "GET / HTTP/1.1\r\n" .
                           "Host: 127.0.0.1:8080\r\n" .
                           "Upgrade: websocket\r\n" .
                           "Connection: Upgrade\r\n" .
                           "Sec-WebSocket-Key: " . base64_encode(random_bytes(16)) . "\r\n" .
                           "Sec-WebSocket-Version: 13\r\n" .
                           "\r\n";
                
                $connection->write($handshake);
                
                $handshakeCompleted = false;
                
                $connection->on('data', function ($data) use (&$handshakeCompleted, $connection, $token, $user) {
                    if (!$handshakeCompleted) {
                        if (strpos($data, '101 Switching Protocols') !== false) {
                            $this->info('Handshake successful!');
                            $handshakeCompleted = true;
                            
                            // Send authentication
                            $this->info('Sending authentication...');
                            $authMsg = json_encode([
                                'type' => 'auth',
                                'token' => $token
                            ]);
                            $connection->write($this->createWebSocketFrame($authMsg));
                            
                            // Send a test message after 1 second
                            Loop::get()->addTimer(1, function () use ($connection) {
                                $this->info('Sending test message...');
                                $msg = json_encode([
                                    'type' => 'message',
                                    'message' => 'Hello from WebSocket client test!'
                                ]);
                                $connection->write($this->createWebSocketFrame($msg));
                            });
                        }
                    } else {
                        // Parse WebSocket frames
                        $frames = $this->parseWebSocketFrames($data);
                        foreach ($frames as $frame) {
                            if ($frame['opcode'] === 1) {
                                $message = json_decode($frame['payload'], true);
                                $this->info('Received: ' . json_encode($message, JSON_PRETTY_PRINT));
                            }
                        }
                    }
                });
                
                $connection->on('close', function () {
                    $this->info('Connection closed');
                });
                
                // Close after 5 seconds
                Loop::get()->addTimer(5, function () use ($connection) {
                    $this->info('Closing connection...');
                    $connection->close();
                    Loop::get()->stop();
                });
                
            }, function (\Exception $e) {
                $this->error('Connection failed: ' . $e->getMessage());
                return 1;
            });
        
        $loop->run();
        return 0;
    }
    
    private function createWebSocketFrame($data, $opcode = 0x1)
    {
        $length = strlen($data);
        $frame = '';

        // First byte: FIN (1) + RSV (000) + Opcode
        $frame .= chr(0x80 | $opcode);

        // Add masking bit for client frames
        if ($length < 126) {
            $frame .= chr(0x80 | $length);
        } elseif ($length < 65536) {
            $frame .= chr(0x80 | 126) . pack('n', $length);
        } else {
            $frame .= chr(0x80 | 127) . pack('J', $length);
        }

        // Add masking key
        $maskingKey = random_bytes(4);
        $frame .= $maskingKey;

        // Mask the payload
        for ($i = 0; $i < $length; $i++) {
            $frame .= chr(ord($data[$i]) ^ ord($maskingKey[$i % 4]));
        }

        return $frame;
    }
    
    private function parseWebSocketFrames($data)
    {
        $frames = [];
        $offset = 0;
        $dataLength = strlen($data);

        while ($offset < $dataLength) {
            if ($dataLength - $offset < 2) {
                break;
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
                break;
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
}
