#!/usr/bin/env php
<?php
/**
 * WebSocket Server for Real-time Chat
 * Sistema de Ponto Eletrônico
 *
 * Uses Workerman for WebSocket handling
 * Port: 8080
 * Workers: 4
 *
 * Usage:
 *   Start:   php scripts/websocket_server.php start
 *   Stop:    php scripts/websocket_server.php stop
 *   Restart: php scripts/websocket_server.php restart
 *   Status:  php scripts/websocket_server.php status
 *   Debug:   php scripts/websocket_server.php start -d (foreground mode)
 */

use Workerman\Worker;
use Workerman\Timer;
use Workerman\Lib\Timer as LibTimer;

// Load CodeIgniter bootstrap
require_once __DIR__ . '/../vendor/autoload.php';

// CodeIgniter paths
define('FCPATH', __DIR__ . '/../public/');
define('SYSTEMPATH', __DIR__ . '/../vendor/codeigniter4/framework/system/');
define('APPPATH', __DIR__ . '/../app/');
define('WRITEPATH', __DIR__ . '/../writable/');
define('ROOTPATH', __DIR__ . '/../');

// Load environment
$dotenv = \Dotenv\Dotenv::createImmutable(ROOTPATH);
$dotenv->safeLoad();

/**
 * WebSocket Server Configuration
 */
$wsServer = new Worker('websocket://0.0.0.0:8080');
$wsServer->count = 4; // 4 worker processes
$wsServer->name = 'ChatWebSocket';

/**
 * Global connection storage
 * Structure: [
 *   'user_id' => [
 *     'connection_id' => $connection,
 *     ...
 *   ]
 * ]
 */
$wsServer->connections = [];

/**
 * User metadata storage
 * Structure: [
 *   'connection_id' => [
 *     'user_id' => 123,
 *     'employee_id' => 456,
 *     'authenticated' => true,
 *     'last_activity' => timestamp
 *   ]
 * ]
 */
$wsServer->users = [];

/**
 * Typing state tracking
 * Structure: [
 *   'room_id' => [
 *     'user_id' => timestamp
 *   ]
 * ]
 */
$wsServer->typing = [];

/**
 * Database connection (lazy loaded)
 */
$wsServer->db = null;

/**
 * Worker Start Event
 * Initialize connections array and start heartbeat timer
 */
$wsServer->onWorkerStart = function($worker) {
    echo "[Worker {$worker->id}] Started at " . date('Y-m-d H:i:s') . "\n";

    // Initialize storage arrays
    $worker->connections = [];
    $worker->users = [];
    $worker->typing = [];

    // Start heartbeat timer (ping every 30 seconds)
    Timer::add(30, function() use ($worker) {
        foreach ($worker->connections as $userId => $userConnections) {
            foreach ($userConnections as $connId => $connection) {
                try {
                    $connection->send(json_encode([
                        'type' => 'ping',
                        'timestamp' => time()
                    ]));
                } catch (\Exception $e) {
                    echo "[Heartbeat] Error pinging connection {$connId}: {$e->getMessage()}\n";
                }
            }
        }
    });

    // Clean up old typing indicators (every 5 seconds)
    Timer::add(5, function() use ($worker) {
        $now = time();
        foreach ($worker->typing as $roomId => $users) {
            foreach ($users as $userId => $timestamp) {
                if ($now - $timestamp > 3) {
                    unset($worker->typing[$roomId][$userId]);

                    // Broadcast typing stopped
                    $worker->broadcastToRoom($roomId, [
                        'type' => 'typing',
                        'room_id' => $roomId,
                        'user_id' => $userId,
                        'typing' => false
                    ], $userId);
                }
            }
        }
    });

    echo "[Worker {$worker->id}] Heartbeat and cleanup timers initialized\n";
};

/**
 * New Connection Event
 * Send authentication requirement
 */
$wsServer->onConnect = function($connection) use ($wsServer) {
    $connId = $connection->id;
    echo "[Connect] New connection: {$connId}\n";

    // Send authentication requirement
    $connection->send(json_encode([
        'type' => 'auth_required',
        'message' => 'Please authenticate with JWT token',
        'timestamp' => time()
    ]));

    // Set connection timeout (30 seconds to authenticate)
    $connection->authTimeout = Timer::add(30, function() use ($connection, $connId) {
        if (!isset($connection->authenticated) || !$connection->authenticated) {
            echo "[Auth] Timeout for connection {$connId}\n";
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Authentication timeout'
            ]));
            $connection->close();
        }
    }, [], false); // Execute only once
};

/**
 * Message Received Event
 * Handle different message types
 */
$wsServer->onMessage = function($connection, $data) use ($wsServer) {
    $connId = $connection->id;

    try {
        $message = json_decode($data, true);

        if (!$message || !isset($message['type'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Invalid message format'
            ]));
            return;
        }

        $type = $message['type'];
        echo "[Message] Type: {$type} from connection {$connId}\n";

        // Route message based on type
        switch ($type) {
            case 'auth':
                handleAuth($wsServer, $connection, $message);
                break;

            case 'message':
                handleMessage($wsServer, $connection, $message);
                break;

            case 'typing':
                handleTyping($wsServer, $connection, $message);
                break;

            case 'read':
                handleRead($wsServer, $connection, $message);
                break;

            case 'pong':
                handlePong($wsServer, $connection, $message);
                break;

            case 'join_room':
                handleJoinRoom($wsServer, $connection, $message);
                break;

            case 'leave_room':
                handleLeaveRoom($wsServer, $connection, $message);
                break;

            default:
                $connection->send(json_encode([
                    'type' => 'error',
                    'message' => "Unknown message type: {$type}"
                ]));
        }

    } catch (\Exception $e) {
        echo "[Error] Message handling error: {$e->getMessage()}\n";
        $connection->send(json_encode([
            'type' => 'error',
            'message' => 'Internal server error',
            'details' => $e->getMessage()
        ]));
    }
};

/**
 * Connection Close Event
 * Clean up user data and broadcast offline status
 */
$wsServer->onClose = function($connection) use ($wsServer) {
    $connId = $connection->id;

    // Cancel auth timeout if exists
    if (isset($connection->authTimeout)) {
        Timer::del($connection->authTimeout);
    }

    // Get user info before cleanup
    $userId = $connection->userId ?? null;
    $employeeId = $connection->employeeId ?? null;

    echo "[Close] Connection closed: {$connId}" . ($userId ? " (User: {$userId})" : "") . "\n";

    // Remove from connections array
    if ($userId && isset($wsServer->connections[$userId])) {
        unset($wsServer->connections[$userId][$connId]);

        // If no more connections for this user, remove user entirely
        if (empty($wsServer->connections[$userId])) {
            unset($wsServer->connections[$userId]);

            // Update online status in database
            if ($employeeId) {
                updateOnlineStatus($wsServer, $employeeId, 'offline');
            }

            // Broadcast user offline to contacts
            broadcastUserStatus($wsServer, $userId, 'offline');
        }
    }

    // Remove from users metadata
    unset($wsServer->users[$connId]);
};

/**
 * Worker Error Event
 */
$wsServer->onError = function($connection, $code, $msg) {
    echo "[Error] Connection {$connection->id} error {$code}: {$msg}\n";
};

/**
 * Handle Authentication
 */
function handleAuth($worker, $connection, $message) {
    $connId = $connection->id;

    if (!isset($message['token'])) {
        $connection->send(json_encode([
            'type' => 'auth_error',
            'message' => 'Token required'
        ]));
        return;
    }

    $token = $message['token'];

    // Validate JWT token
    $userData = validateJWT($token);

    if (!$userData) {
        echo "[Auth] Invalid token for connection {$connId}\n";
        $connection->send(json_encode([
            'type' => 'auth_error',
            'message' => 'Invalid or expired token'
        ]));
        $connection->close();
        return;
    }

    $userId = $userData['user_id'] ?? $userData['id'];
    $employeeId = $userData['employee_id'] ?? $userId;

    // Store user info in connection
    $connection->userId = $userId;
    $connection->employeeId = $employeeId;
    $connection->authenticated = true;
    $connection->lastActivity = time();

    // Add to connections array
    if (!isset($worker->connections[$userId])) {
        $worker->connections[$userId] = [];
    }
    $worker->connections[$userId][$connId] = $connection;

    // Add to users metadata
    $worker->users[$connId] = [
        'user_id' => $userId,
        'employee_id' => $employeeId,
        'authenticated' => true,
        'last_activity' => time()
    ];

    // Cancel auth timeout
    if (isset($connection->authTimeout)) {
        Timer::del($connection->authTimeout);
    }

    // Update online status in database
    updateOnlineStatus($worker, $employeeId, 'online', $connId);

    // Send success response
    $connection->send(json_encode([
        'type' => 'auth_success',
        'user_id' => $userId,
        'employee_id' => $employeeId,
        'timestamp' => time()
    ]));

    // Broadcast user online to contacts
    broadcastUserStatus($worker, $userId, 'online');

    echo "[Auth] User {$userId} authenticated on connection {$connId}\n";
}

/**
 * Handle Chat Message
 */
function handleMessage($worker, $connection, $message) {
    if (!$connection->authenticated) {
        $connection->send(json_encode([
            'type' => 'error',
            'message' => 'Not authenticated'
        ]));
        return;
    }

    $roomId = $message['room_id'] ?? null;
    $messageText = $message['message'] ?? '';
    $replyTo = $message['reply_to'] ?? null;

    if (!$roomId || !$messageText) {
        $connection->send(json_encode([
            'type' => 'error',
            'message' => 'room_id and message are required'
        ]));
        return;
    }

    $senderId = $connection->employeeId;

    // Save message to database
    $db = getDatabase($worker);

    $messageId = $db->table('chat_messages')->insert([
        'room_id' => $roomId,
        'sender_id' => $senderId,
        'message' => $messageText,
        'type' => 'text',
        'reply_to' => $replyTo,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    if (!$messageId) {
        $connection->send(json_encode([
            'type' => 'error',
            'message' => 'Failed to save message'
        ]));
        return;
    }

    // Get sender info
    $sender = $db->table('employees')
        ->select('id, name')
        ->where('id', $senderId)
        ->get()
        ->getRow();

    // Prepare message data
    $messageData = [
        'type' => 'message',
        'message_id' => $messageId,
        'room_id' => $roomId,
        'sender_id' => $senderId,
        'sender_name' => $sender->name ?? 'Unknown',
        'message' => $messageText,
        'reply_to' => $replyTo,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Get room members
    $members = $db->table('chat_room_members')
        ->select('employee_id')
        ->where('room_id', $roomId)
        ->get()
        ->getResult();

    // Send to online members
    $offlineMembers = [];
    foreach ($members as $member) {
        $memberId = $member->employee_id;

        if ($memberId == $senderId) {
            continue; // Skip sender
        }

        // Check if member is online
        if (isset($worker->connections[$memberId])) {
            // Send to all connections of this user
            foreach ($worker->connections[$memberId] as $conn) {
                $conn->send(json_encode($messageData));
            }
        } else {
            $offlineMembers[] = $memberId;
        }
    }

    // Queue push notifications for offline members
    if (!empty($offlineMembers)) {
        queuePushNotifications($worker, $offlineMembers, [
            'title' => $sender->name ?? 'Nova mensagem',
            'body' => mb_substr($messageText, 0, 100),
            'room_id' => $roomId,
            'message_id' => $messageId
        ]);
    }

    // Send confirmation to sender
    $connection->send(json_encode([
        'type' => 'message_sent',
        'message_id' => $messageId,
        'room_id' => $roomId,
        'timestamp' => date('Y-m-d H:i:s')
    ]));

    echo "[Message] Room {$roomId}: User {$senderId} sent message {$messageId}\n";
}

/**
 * Handle Typing Indicator
 */
function handleTyping($worker, $connection, $message) {
    if (!$connection->authenticated) {
        return;
    }

    $roomId = $message['room_id'] ?? null;
    $typing = $message['typing'] ?? false;

    if (!$roomId) {
        return;
    }

    $userId = $connection->userId;

    // Update typing state
    if ($typing) {
        if (!isset($worker->typing[$roomId])) {
            $worker->typing[$roomId] = [];
        }
        $worker->typing[$roomId][$userId] = time();
    } else {
        unset($worker->typing[$roomId][$userId]);
    }

    // Broadcast to room members (except sender)
    $worker->broadcastToRoom($roomId, [
        'type' => 'typing',
        'room_id' => $roomId,
        'employee_id' => $connection->employeeId,
        'typing' => $typing
    ], $userId);

    echo "[Typing] Room {$roomId}: User {$userId} " . ($typing ? 'started' : 'stopped') . " typing\n";
}

/**
 * Handle Read Receipt
 */
function handleRead($worker, $connection, $message) {
    if (!$connection->authenticated) {
        return;
    }

    $roomId = $message['room_id'] ?? null;

    if (!$roomId) {
        return;
    }

    $employeeId = $connection->employeeId;

    // Update last_read_at in database
    $db = getDatabase($worker);

    $db->table('chat_room_members')
        ->where('room_id', $roomId)
        ->where('employee_id', $employeeId)
        ->update(['last_read_at' => date('Y-m-d H:i:s')]);

    // Broadcast read receipt to room
    $worker->broadcastToRoom($roomId, [
        'type' => 'read',
        'room_id' => $roomId,
        'employee_id' => $employeeId,
        'timestamp' => date('Y-m-d H:i:s')
    ], $connection->userId);

    echo "[Read] Room {$roomId}: User {$employeeId} marked as read\n";
}

/**
 * Handle Pong Response
 */
function handlePong($worker, $connection, $message) {
    $connection->lastActivity = time();
    // No response needed
}

/**
 * Handle Join Room
 */
function handleJoinRoom($worker, $connection, $message) {
    if (!$connection->authenticated) {
        return;
    }

    $roomId = $message['room_id'] ?? null;

    if (!$roomId) {
        return;
    }

    if (!isset($connection->rooms)) {
        $connection->rooms = [];
    }

    if (!in_array($roomId, $connection->rooms)) {
        $connection->rooms[] = $roomId;
    }

    echo "[Room] User {$connection->userId} joined room {$roomId}\n";
}

/**
 * Handle Leave Room
 */
function handleLeaveRoom($worker, $connection, $message) {
    if (!$connection->authenticated) {
        return;
    }

    $roomId = $message['room_id'] ?? null;

    if (!$roomId || !isset($connection->rooms)) {
        return;
    }

    $key = array_search($roomId, $connection->rooms);
    if ($key !== false) {
        unset($connection->rooms[$key]);
    }

    echo "[Room] User {$connection->userId} left room {$roomId}\n";
}

/**
 * Broadcast to Room Members
 */
$wsServer->broadcastToRoom = function($roomId, $data, $excludeUserId = null) use ($wsServer) {
    $db = getDatabase($wsServer);

    // Get room members
    $members = $db->table('chat_room_members')
        ->select('employee_id')
        ->where('room_id', $roomId)
        ->get()
        ->getResult();

    $message = json_encode($data);

    foreach ($members as $member) {
        $memberId = $member->employee_id;

        if ($excludeUserId && $memberId == $excludeUserId) {
            continue;
        }

        if (isset($wsServer->connections[$memberId])) {
            foreach ($wsServer->connections[$memberId] as $conn) {
                $conn->send($message);
            }
        }
    }
};

/**
 * Validate JWT Token
 */
function validateJWT($token) {
    // Simple token validation
    // In production, use proper JWT library

    // Remove "Bearer " prefix if exists
    $token = str_replace('Bearer ', '', $token);

    // For now, accept session tokens
    // In production, implement proper JWT validation with secret key

    if (empty($token)) {
        return null;
    }

    // Mock validation - return user data
    // TODO: Implement proper JWT validation
    return [
        'user_id' => 1,
        'employee_id' => 1,
        'exp' => time() + 3600
    ];
}

/**
 * Get Database Connection
 */
function getDatabase($worker) {
    if ($worker->db === null) {
        // Create CodeIgniter database connection
        $config = new \Config\Database();
        $worker->db = \Config\Database::connect();
    }

    return $worker->db;
}

/**
 * Update Online Status
 */
function updateOnlineStatus($worker, $employeeId, $status, $connectionId = null) {
    $db = getDatabase($worker);

    if ($status === 'online') {
        // Insert or update online status
        $existing = $db->table('chat_online_users')
            ->where('employee_id', $employeeId)
            ->where('connection_id', $connectionId)
            ->get()
            ->getRow();

        if ($existing) {
            $db->table('chat_online_users')
                ->where('id', $existing->id)
                ->update([
                    'status' => 'online',
                    'last_activity' => date('Y-m-d H:i:s')
                ]);
        } else {
            $db->table('chat_online_users')->insert([
                'employee_id' => $employeeId,
                'connection_id' => $connectionId,
                'status' => 'online',
                'last_activity' => date('Y-m-d H:i:s')
            ]);
        }
    } else {
        // Remove from online users
        $db->table('chat_online_users')
            ->where('employee_id', $employeeId)
            ->delete();
    }
}

/**
 * Broadcast User Status
 */
function broadcastUserStatus($worker, $userId, $status) {
    $db = getDatabase($worker);

    // Get user's contacts (people they have rooms with)
    $contacts = $db->table('chat_room_members as m1')
        ->select('m2.employee_id')
        ->join('chat_room_members as m2', 'm1.room_id = m2.room_id')
        ->where('m1.employee_id', $userId)
        ->where('m2.employee_id !=', $userId)
        ->distinct()
        ->get()
        ->getResult();

    $message = json_encode([
        'type' => 'user_status',
        'user_id' => $userId,
        'status' => $status,
        'timestamp' => time()
    ]);

    foreach ($contacts as $contact) {
        $contactId = $contact->employee_id;

        if (isset($worker->connections[$contactId])) {
            foreach ($worker->connections[$contactId] as $conn) {
                $conn->send($message);
            }
        }
    }
}

/**
 * Queue Push Notifications
 */
function queuePushNotifications($worker, $userIds, $data) {
    // TODO: Implement push notification queue
    // For now, just log
    echo "[Push] Queuing notifications for " . count($userIds) . " offline users\n";
}

// Run the worker
Worker::runAll();
