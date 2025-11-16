<?php

/**
 * Workerman WebSocket Server
 *
 * Real-time chat server for Sistema de Ponto Eletrônico
 *
 * Usage:
 *   php websocket-server.php start
 *   php websocket-server.php stop
 *   php websocket-server.php restart
 *   php websocket-server.php status
 */

require_once __DIR__ . '/vendor/autoload.php';

use CodeIgniter\Boot;
use Config\Paths;
use Workerman\Worker;
use Workerman\Timer;

// Load CodeIgniter environment
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

require __DIR__ . '/app/Config/Paths.php';
$paths = new Paths();

// Define path constants
define('APPPATH', realpath(FCPATH . '../app') . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath($paths->systemDirectory) . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath($paths->writableDirectory) . DIRECTORY_SEPARATOR);

// Load .env file
require_once SYSTEMPATH . 'Config/DotEnv.php';
$dotenv = new \CodeIgniter\Config\DotEnv(ROOTPATH);
$dotenv->load();

// Define environment
if (!defined('ENVIRONMENT')) {
    $env = $_ENV['CI_ENVIRONMENT'] ?? $_SERVER['CI_ENVIRONMENT'] ?? getenv('CI_ENVIRONMENT') ?: 'production';
    define('ENVIRONMENT', $env);
}

// Bootstrap CodeIgniter for console
require $paths->systemDirectory . '/Boot.php';
Boot::bootConsole($paths);

// Create WebSocket server
$wsServer = new Worker('websocket://0.0.0.0:2346');

// Number of processes
$wsServer->count = 4;

// Server name
$wsServer->name = 'PontoEletronicoChat';

// Connection storage
$connections = [];
$employees    = [];

/**
 * On server start
 */
$wsServer->onWorkerStart = function ($worker) {
    echo "WebSocket Server started on ws://0.0.0.0:2346\n";

    // Cleanup inactive connections every 60 seconds
    Timer::add(60, function () use (&$connections, &$employees) {
        $db = \Config\Database::connect();
        $builder = $db->table('chat_online_users');

        // Remove connections inactive for more than 5 minutes
        $cutoff = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $builder->where('last_activity <', $cutoff)->delete();

        echo "[CLEANUP] Inactive connections cleaned up\n";
    });
};

/**
 * On client connection
 */
$wsServer->onConnect = function ($connection) {
    echo "[CONNECT] New connection from {$connection->getRemoteIp()}\n";
    $connection->employee_id = null;
    $connection->authenticated = false;
};

/**
 * On receive message
 */
$wsServer->onMessage = function ($connection, $data) use (&$connections, &$employees) {
    try {
        $message = json_decode($data, true);

        if (!$message || !isset($message['type'])) {
            sendError($connection, 'Invalid message format');

            return;
        }

        // Handle authentication
        if ($message['type'] === 'auth') {
            handleAuthentication($connection, $message, $connections, $employees);

            return;
        }

        // Check if authenticated
        if (!$connection->authenticated) {
            sendError($connection, 'Not authenticated');

            return;
        }

        // Handle different message types
        switch ($message['type']) {
            case 'ping':
                handlePing($connection);

                break;

            case 'message':
                handleChatMessage($connection, $message, $connections, $employees);

                break;

            case 'typing':
                handleTyping($connection, $message, $connections, $employees);

                break;

            case 'read':
                handleReadReceipt($connection, $message, $connections, $employees);

                break;

            case 'status':
                handleStatusChange($connection, $message, $connections, $employees);

                break;

            case 'reaction':
                handleReaction($connection, $message, $connections, $employees);

                break;

            case 'join_room':
                handleJoinRoom($connection, $message, $connections, $employees);

                break;

            case 'leave_room':
                handleLeaveRoom($connection, $message, $connections, $employees);

                break;

            default:
                sendError($connection, 'Unknown message type');
        }
    } catch (\Exception $e) {
        echo "[ERROR] {$e->getMessage()}\n";
        sendError($connection, 'Server error: ' . $e->getMessage());
    }
};

/**
 * On client close connection
 */
$wsServer->onClose = function ($connection) use (&$connections, &$employees) {
    echo "[DISCONNECT] Connection closed\n";

    if ($connection->authenticated && $connection->employee_id) {
        // Remove from online users
        $db = \Config\Database::connect();
        $builder = $db->table('chat_online_users');
        $builder->where('connection_id', $connection->id)->delete();

        // Remove from local storage
        unset($connections[$connection->id]);

        if (isset($employees[$connection->employee_id])) {
            $key = array_search($connection->id, $employees[$connection->employee_id]);
            if ($key !== false) {
                unset($employees[$connection->employee_id][$key]);
            }
        }

        // Broadcast offline status
        broadcastStatus($connection->employee_id, 'offline', $connections);

        echo "[INFO] Employee {$connection->employee_id} disconnected\n";
    }
};

/**
 * Handle authentication
 */
function handleAuthentication($connection, $message, &$connections, &$employees)
{
    if (!isset($message['token'])) {
        sendError($connection, 'Token required');

        return;
    }

    // Validate token (Bearer or session)
    $employeeId = validateToken($message['token']);

    if (!$employeeId) {
        sendError($connection, 'Invalid token');

        return;
    }

    // Authenticate connection
    $connection->employee_id = $employeeId;
    $connection->authenticated = true;

    // Store connection
    $connections[$connection->id] = $connection;

    if (!isset($employees[$employeeId])) {
        $employees[$employeeId] = [];
    }
    $employees[$employeeId][] = $connection->id;

    // Save to database
    $db = \Config\Database::connect();
    $builder = $db->table('chat_online_users');
    $builder->insert([
        'employee_id'   => $employeeId,
        'connection_id' => $connection->id,
        'status'        => 'online',
        'last_activity' => date('Y-m-d H:i:s'),
        'created_at'    => date('Y-m-d H:i:s'),
    ]);

    // Send success
    send($connection, [
        'type'    => 'auth_success',
        'user_id' => $employeeId,
    ]);

    // Broadcast online status
    broadcastStatus($employeeId, 'online', $connections);

    echo "[AUTH] Employee {$employeeId} authenticated\n";
}

/**
 * Handle chat message
 */
function handleChatMessage($connection, $message, &$connections, &$employees)
{
    if (!isset($message['room_id'], $message['message'])) {
        sendError($connection, 'room_id and message required');

        return;
    }

    $roomId = $message['room_id'];
    $text = $message['message'];
    $replyTo = $message['reply_to'] ?? null;

    // Save message to database
    $db = \Config\Database::connect();
    $builder = $db->table('chat_messages');

    $messageId = $builder->insert([
        'room_id'    => $roomId,
        'sender_id'  => $connection->employee_id,
        'message'    => $text,
        'type'       => 'text',
        'reply_to'   => $replyTo,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    if (!$messageId) {
        sendError($connection, 'Failed to save message');

        return;
    }

    // Get sender info
    $senderBuilder = $db->table('employees');
    $sender = $senderBuilder->where('id', $connection->employee_id)->get()->getRow();

    // Get room members
    $memberBuilder = $db->table('chat_room_members');
    $members = $memberBuilder->where('room_id', $roomId)->get()->getResult();

    // Broadcast to room members
    $broadcastMessage = [
        'type'          => 'message',
        'message_id'    => $messageId,
        'room_id'       => $roomId,
        'sender_id'     => $connection->employee_id,
        'sender_name'   => $sender->name ?? 'Unknown',
        'message'       => $text,
        'reply_to'      => $replyTo,
        'timestamp'     => date('Y-m-d H:i:s'),
    ];

    foreach ($members as $member) {
        if (isset($employees[$member->employee_id])) {
            foreach ($employees[$member->employee_id] as $connId) {
                if (isset($connections[$connId])) {
                    send($connections[$connId], $broadcastMessage);
                }
            }
        }
    }

    echo "[MESSAGE] Employee {$connection->employee_id} sent message to room {$roomId}\n";
}

/**
 * Handle typing indicator
 */
function handleTyping($connection, $message, &$connections, &$employees)
{
    if (!isset($message['room_id'], $message['typing'])) {
        return;
    }

    $roomId = $message['room_id'];
    $typing = (bool) $message['typing'];

    // Get room members
    $db = \Config\Database::connect();
    $memberBuilder = $db->table('chat_room_members');
    $members = $memberBuilder->where('room_id', $roomId)->get()->getResult();

    // Broadcast typing status to room members (except sender)
    $broadcastMessage = [
        'type'        => 'typing',
        'room_id'     => $roomId,
        'employee_id' => $connection->employee_id,
        'typing'      => $typing,
    ];

    foreach ($members as $member) {
        if ($member->employee_id === $connection->employee_id) {
            continue; // Skip sender
        }

        if (isset($employees[$member->employee_id])) {
            foreach ($employees[$member->employee_id] as $connId) {
                if (isset($connections[$connId])) {
                    send($connections[$connId], $broadcastMessage);
                }
            }
        }
    }
}

/**
 * Handle read receipt
 */
function handleReadReceipt($connection, $message, &$connections, &$employees)
{
    if (!isset($message['room_id'])) {
        return;
    }

    $roomId = $message['room_id'];

    // Update last_read_at in database
    $db = \Config\Database::connect();
    $builder = $db->table('chat_room_members');
    $builder->where('room_id', $roomId)
        ->where('employee_id', $connection->employee_id)
        ->set(['last_read_at' => date('Y-m-d H:i:s')])
        ->update();

    echo "[READ] Employee {$connection->employee_id} read messages in room {$roomId}\n";
}

/**
 * Handle status change
 */
function handleStatusChange($connection, $message, &$connections, &$employees)
{
    if (!isset($message['status'])) {
        return;
    }

    $status = $message['status'];

    // Update in database
    $db = \Config\Database::connect();
    $builder = $db->table('chat_online_users');
    $builder->where('connection_id', $connection->id)
        ->set([
            'status'        => $status,
            'last_activity' => date('Y-m-d H:i:s'),
        ])
        ->update();

    // Broadcast status
    broadcastStatus($connection->employee_id, $status, $connections);
}

/**
 * Handle message reaction
 */
function handleReaction($connection, $message, &$connections, &$employees)
{
    if (!isset($message['message_id'], $message['emoji'])) {
        return;
    }

    $messageId = $message['message_id'];
    $emoji = $message['emoji'];

    $db = \Config\Database::connect();

    // Toggle reaction
    $reactionBuilder = $db->table('chat_message_reactions');
    $existing = $reactionBuilder
        ->where('message_id', $messageId)
        ->where('employee_id', $connection->employee_id)
        ->where('emoji', $emoji)
        ->get()
        ->getRow();

    if ($existing) {
        // Remove reaction
        $reactionBuilder->delete(['id' => $existing->id]);
        $action = 'removed';
    } else {
        // Add reaction
        $reactionBuilder->insert([
            'message_id'  => $messageId,
            'employee_id' => $connection->employee_id,
            'emoji'       => $emoji,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $action = 'added';
    }

    // Get room ID for broadcast
    $messageBuilder = $db->table('chat_messages');
    $msg = $messageBuilder->where('id', $messageId)->get()->getRow();

    if ($msg) {
        // Broadcast reaction to room members
        $memberBuilder = $db->table('chat_room_members');
        $members = $memberBuilder->where('room_id', $msg->room_id)->get()->getResult();

        $broadcastMessage = [
            'type'        => 'reaction',
            'message_id'  => $messageId,
            'room_id'     => $msg->room_id,
            'employee_id' => $connection->employee_id,
            'emoji'       => $emoji,
            'action'      => $action,
        ];

        foreach ($members as $member) {
            if (isset($employees[$member->employee_id])) {
                foreach ($employees[$member->employee_id] as $connId) {
                    if (isset($connections[$connId])) {
                        send($connections[$connId], $broadcastMessage);
                    }
                }
            }
        }
    }
}

/**
 * Handle join room
 */
function handleJoinRoom($connection, $message, &$connections, &$employees)
{
    // Placeholder for future room joining logic
    send($connection, ['type' => 'joined_room', 'room_id' => $message['room_id'] ?? null]);
}

/**
 * Handle leave room
 */
function handleLeaveRoom($connection, $message, &$connections, &$employees)
{
    // Placeholder for future room leaving logic
    send($connection, ['type' => 'left_room', 'room_id' => $message['room_id'] ?? null]);
}

/**
 * Handle ping
 */
function handlePing($connection)
{
    // Update activity
    $db = \Config\Database::connect();
    $builder = $db->table('chat_online_users');
    $builder->where('connection_id', $connection->id)
        ->set(['last_activity' => date('Y-m-d H:i:s')])
        ->update();

    send($connection, ['type' => 'pong']);
}

/**
 * Broadcast status change
 */
function broadcastStatus($employeeId, $status, &$connections)
{
    $message = [
        'type'        => 'user_status',
        'employee_id' => $employeeId,
        'status'      => $status,
    ];

    foreach ($connections as $conn) {
        if ($conn->authenticated) {
            send($conn, $message);
        }
    }
}

/**
 * Validate authentication token
 */
function validateToken($token)
{
    try {
        $db = \Config\Database::connect();

        // Try to validate as Bearer token (JWT or API token)
        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);

            // Check API tokens table
            $builder = $db->table('employee_api_tokens');
            $tokenData = $builder->where('token', hash('sha256', $token))
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->get()
                ->getRow();

            if ($tokenData) {
                return $tokenData->employee_id;
            }
        }

        // Try to validate as session ID
        $session = \Config\Services::session();
        $session->start();

        if ($session->has('employee_id')) {
            return $session->get('employee_id');
        }

        return null;
    } catch (\Exception $e) {
        echo "[ERROR] Token validation failed: {$e->getMessage()}\n";

        return null;
    }
}

/**
 * Send message to connection
 */
function send($connection, $data)
{
    $connection->send(json_encode($data));
}

/**
 * Send error to connection
 */
function sendError($connection, $message)
{
    send($connection, [
        'type'  => 'error',
        'error' => $message,
    ]);
}

// Run server
Worker::runAll();
