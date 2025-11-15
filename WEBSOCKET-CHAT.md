# WebSocket Chat System - Sistema de Ponto Eletrônico

Documentação completa do sistema de chat em tempo real com Workerman WebSocket.

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Arquitetura](#arquitetura)
3. [Database Schema](#database-schema)
4. [WebSocket Server](#websocket-server)
5. [Backend API](#backend-api)
6. [Frontend Integration](#frontend-integration)
7. [Message Types](#message-types)
8. [Deployment](#deployment)
9. [Security](#security)
10. [Troubleshooting](#troubleshooting)

---

## Visão Geral

### Features

✅ **Real-time Messaging**
- Instant message delivery
- Typing indicators
- Read receipts
- Online/offline status

✅ **Room Types**
- **Private**: 1-on-1 conversations
- **Group**: Multi-user chat rooms
- **Department**: Department-wide broadcasts
- **Broadcast**: Company-wide announcements

✅ **Message Features**
- Text messages (up to 5000 characters)
- File attachments (images, documents)
- Reply to messages
- Edit messages (15-minute window)
- Delete messages
- Emoji reactions (👍, ❤️, 😂, etc.)

✅ **Advanced Features**
- Multi-device support
- Message search
- Member management
- Permission system (admin/member)
- Auto-cleanup inactive connections

---

## Arquitetura

### System Architecture

```
┌─────────────┐     WebSocket      ┌──────────────┐
│   Browser   │ ←──────────────→   │   Workerman  │
│  (Client)   │   ws://host:2346   │    Server    │
└─────────────┘                    └──────────────┘
                                           │
                                           ↓
                                    ┌──────────────┐
                                    │    MySQL     │
                                    │   Database   │
                                    └──────────────┘
```

### Technology Stack

- **WebSocket Server**: Workerman (PHP)
- **Database**: MySQL 8.0
- **Backend**: CodeIgniter 4 + PHP 8.2
- **Frontend**: JavaScript ES6+ + WebSocket API
- **Caching**: Redis (optional)

### Port Configuration

- **WebSocket**: `ws://0.0.0.0:2346`
- **Web Application**: `http://localhost:80`
- **HTTPS/WSS**: `wss://domain.com:2346` (production)

---

## Database Schema

### Tables

#### 1. `chat_rooms`
Chat room configuration.

```sql
CREATE TABLE chat_rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('private', 'group', 'department', 'broadcast'),
    department VARCHAR(100),
    created_by INT,
    active BOOLEAN DEFAULT TRUE,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (created_by) REFERENCES employees(id)
);
```

#### 2. `chat_room_members`
Room membership and permissions.

```sql
CREATE TABLE chat_room_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    employee_id INT NOT NULL,
    role ENUM('member', 'admin') DEFAULT 'member',
    last_read_at DATETIME,
    joined_at DATETIME,
    UNIQUE KEY (room_id, employee_id),
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);
```

#### 3. `chat_messages`
Message storage.

```sql
CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('text', 'file', 'image', 'system') DEFAULT 'text',
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    file_size INT,
    reply_to INT,
    edited_at DATETIME,
    deleted_at DATETIME,
    created_at DATETIME,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id),
    FOREIGN KEY (sender_id) REFERENCES employees(id),
    FOREIGN KEY (reply_to) REFERENCES chat_messages(id)
);
```

#### 4. `chat_message_reactions`
Emoji reactions.

```sql
CREATE TABLE chat_message_reactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    employee_id INT NOT NULL,
    emoji VARCHAR(10) NOT NULL,
    created_at DATETIME,
    UNIQUE KEY (message_id, employee_id, emoji),
    FOREIGN KEY (message_id) REFERENCES chat_messages(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);
```

#### 5. `chat_online_users`
Online status tracking.

```sql
CREATE TABLE chat_online_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    connection_id VARCHAR(64) UNIQUE NOT NULL,
    status ENUM('online', 'away', 'busy', 'offline') DEFAULT 'online',
    last_activity DATETIME,
    created_at DATETIME,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);
```

---

## WebSocket Server

### Installation

```bash
# Install Workerman
composer require workerman/workerman

# Install optional dependencies
composer require workerman/gateway-worker  # For distributed architecture
```

### Starting the Server

```bash
# Start server
php websocket-server.php start

# Start in daemon mode
php websocket-server.php start -d

# Stop server
php websocket-server.php stop

# Restart server
php websocket-server.php restart

# Check status
php websocket-server.php status

# Reload (graceful restart)
php websocket-server.php reload
```

### Configuration

File: `websocket-server.php`

```php
// WebSocket server configuration
$wsServer = new Worker('websocket://0.0.0.0:2346');
$wsServer->count = 4;  // Number of processes
$wsServer->name = 'PontoEletronicoChat';
```

### Process Management

```bash
# View processes
ps aux | grep websocket-server

# Kill all processes
pkill -f websocket-server

# Monitor logs
tail -f /tmp/workerman.log
```

---

## Message Types

### 1. Authentication

**Client → Server**:
```json
{
    "type": "auth",
    "token": "Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Server → Client**:
```json
{
    "type": "auth_success",
    "user_id": 123
}
```

### 2. Send Message

**Client → Server**:
```json
{
    "type": "message",
    "room_id": 456,
    "message": "Hello, world!",
    "reply_to": 789  // optional
}
```

**Server → Client** (broadcast to room):
```json
{
    "type": "message",
    "message_id": 1001,
    "room_id": 456,
    "sender_id": 123,
    "sender_name": "João Silva",
    "message": "Hello, world!",
    "reply_to": 789,
    "timestamp": "2024-01-16 10:30:00"
}
```

### 3. Typing Indicator

**Client → Server**:
```json
{
    "type": "typing",
    "room_id": 456,
    "typing": true
}
```

**Server → Client** (broadcast to room):
```json
{
    "type": "typing",
    "room_id": 456,
    "employee_id": 123,
    "typing": true
}
```

### 4. Read Receipt

**Client → Server**:
```json
{
    "type": "read",
    "room_id": 456
}
```

### 5. Status Change

**Client → Server**:
```json
{
    "type": "status",
    "status": "away"  // online, away, busy, offline
}
```

**Server → Client** (broadcast to all):
```json
{
    "type": "user_status",
    "employee_id": 123,
    "status": "away"
}
```

### 6. Reaction

**Client → Server**:
```json
{
    "type": "reaction",
    "message_id": 1001,
    "emoji": "👍"
}
```

**Server → Client** (broadcast to room):
```json
{
    "type": "reaction",
    "message_id": 1001,
    "room_id": 456,
    "employee_id": 123,
    "emoji": "👍",
    "action": "added"  // or "removed"
}
```

### 7. Ping/Pong

**Client → Server**:
```json
{
    "type": "ping"
}
```

**Server → Client**:
```json
{
    "type": "pong"
}
```

### 8. Join/Leave Room

**Client → Server**:
```json
{
    "type": "join_room",
    "room_id": 456
}
```

```json
{
    "type": "leave_room",
    "room_id": 456
}
```

### 9. Error Messages

**Server → Client**:
```json
{
    "type": "error",
    "error": "Not authenticated"
}
```

---

## Backend API

### ChatService Methods

```php
use App\Services\ChatService;

$chatService = new ChatService();

// Get or create private room
$result = $chatService->getOrCreatePrivateRoom($employee1Id, $employee2Id);

// Create group room
$result = $chatService->createGroupRoom($creatorId, 'Team Chat', [$id1, $id2, $id3]);

// Get employee rooms
$rooms = $chatService->getEmployeeRooms($employeeId);

// Get room messages
$result = $chatService->getRoomMessages($roomId, $employeeId, $limit = 50, $offset = 0);

// Send message
$result = $chatService->sendMessage($roomId, $senderId, 'Hello!', $replyTo = null);

// Mark as read
$chatService->markAsRead($roomId, $employeeId);

// Add reaction
$chatService->addReaction($messageId, $employeeId, '👍');

// Edit message
$result = $chatService->editMessage($messageId, $employeeId, 'Updated message');

// Delete message
$result = $chatService->deleteMessage($messageId, $employeeId);

// Search messages
$result = $chatService->searchMessages($roomId, $employeeId, 'search query');

// Get online users
$users = $chatService->getOnlineUsers();

// Get room members
$result = $chatService->getRoomMembers($roomId, $employeeId);

// Add member
$result = $chatService->addMember($roomId, $employeeId, $newMemberId);

// Remove member
$result = $chatService->removeMember($roomId, $employeeId, $memberToRemove);
```

---

## Frontend Integration

### JavaScript WebSocket Client

```javascript
// Connect to WebSocket server
const ws = new WebSocket('ws://localhost:2346');

// Connection opened
ws.addEventListener('open', (event) => {
    console.log('Connected to WebSocket server');

    // Authenticate
    ws.send(JSON.stringify({
        type: 'auth',
        token: 'Bearer ' + getAuthToken()
    }));
});

// Listen for messages
ws.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);

    switch(data.type) {
        case 'auth_success':
            console.log('Authenticated as user:', data.user_id);
            break;

        case 'message':
            displayMessage(data);
            break;

        case 'typing':
            showTypingIndicator(data);
            break;

        case 'user_status':
            updateUserStatus(data);
            break;

        case 'reaction':
            updateReaction(data);
            break;

        case 'error':
            console.error('WebSocket error:', data.error);
            break;
    }
});

// Send message
function sendMessage(roomId, message, replyTo = null) {
    ws.send(JSON.stringify({
        type: 'message',
        room_id: roomId,
        message: message,
        reply_to: replyTo
    }));
}

// Send typing indicator
function sendTyping(roomId, isTyping) {
    ws.send(JSON.stringify({
        type: 'typing',
        room_id: roomId,
        typing: isTyping
    }));
}

// Mark as read
function markAsRead(roomId) {
    ws.send(JSON.stringify({
        type: 'read',
        room_id: roomId
    }));
}

// Add reaction
function addReaction(messageId, emoji) {
    ws.send(JSON.stringify({
        type: 'reaction',
        message_id: messageId,
        emoji: emoji
    }));
}

// Heartbeat (ping every 30 seconds)
setInterval(() => {
    if (ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ type: 'ping' }));
    }
}, 30000);

// Auto-reconnect on disconnect
ws.addEventListener('close', () => {
    console.log('Disconnected from WebSocket server');
    setTimeout(() => {
        location.reload(); // Or implement custom reconnect logic
    }, 3000);
});
```

---

## Deployment

### Docker Configuration

Add to `docker-compose.yml`:

```yaml
  websocket:
    build:
      context: .
      dockerfile: Dockerfile.websocket
    ports:
      - "2346:2346"
    volumes:
      - ./:/var/www/html
    depends_on:
      - mysql
      - redis
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
    command: php websocket-server.php start
    restart: unless-stopped
```

### Nginx Configuration

For WSS (WebSocket Secure):

```nginx
# WebSocket proxy
location /ws/ {
    proxy_pass http://localhost:2346;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;

    # Timeouts
    proxy_read_timeout 3600s;
    proxy_send_timeout 3600s;
}
```

### Supervisor Configuration

For auto-restart:

```ini
[program:websocket-server]
command=/usr/bin/php /var/www/html/websocket-server.php start
directory=/var/www/html
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/websocket-server.log
stderr_logfile=/var/log/websocket-server-error.log
```

---

## Security

### Authentication

- **Token-based**: Bearer JWT or API tokens
- **Session-based**: PHP session validation
- **Validation**: Every message requires authentication

### Permissions

- **Private rooms**: Only members can send/receive
- **Group rooms**: Admins can add/remove members
- **Department rooms**: Department-based access
- **Broadcast rooms**: Admin-only sending

### Rate Limiting

```php
// Implement rate limiting in websocket-server.php
$rateLimit = 10; // 10 messages per second
$messageCounts = [];

// Check rate limit
if (isset($messageCounts[$connection->id])) {
    if ($messageCounts[$connection->id] > $rateLimit) {
        sendError($connection, 'Rate limit exceeded');
        return;
    }
    $messageCounts[$connection->id]++;
} else {
    $messageCounts[$connection->id] = 1;
}
```

### Input Validation

- **Message length**: Max 5000 characters
- **HTML escaping**: All messages sanitized
- **SQL injection**: Parameterized queries
- **XSS protection**: Content Security Policy

---

## Troubleshooting

### Server Won't Start

```bash
# Check if port is in use
netstat -tulpn | grep 2346
lsof -i :2346

# Kill existing process
pkill -f websocket-server

# Check permissions
ls -la websocket-server.php

# Check logs
tail -f /tmp/workerman.log
```

### Connection Issues

```bash
# Test WebSocket connection
wscat -c ws://localhost:2346

# Check firewall
sudo ufw allow 2346/tcp

# Verify server is running
php websocket-server.php status
```

### High Memory Usage

```bash
# Monitor memory
ps aux --sort=-rss | grep websocket

# Reduce worker count
# In websocket-server.php:
$wsServer->count = 2;  // Reduce from 4 to 2

# Restart server
php websocket-server.php restart
```

### Messages Not Delivering

1. Check database connection
2. Verify room membership
3. Check connection authentication
4. Monitor server logs
5. Test with simple ping/pong

---

## Performance Optimization

### Recommendations

- **Worker Count**: 4-8 workers (1-2 per CPU core)
- **Max Connections**: 10,000 per worker
- **Message Queue**: Use Redis for message buffering
- **Database**: Index on `room_id`, `sender_id`, `created_at`
- **Cleanup**: Run cleanup every 60 seconds

### Scaling

For large deployments:

1. **Load Balancing**: Multiple WebSocket servers
2. **Redis**: Shared state between workers
3. **Gateway-Worker**: Distributed architecture
4. **Database**: Read replicas for message history
5. **CDN**: File attachments on CDN

---

## Monitoring

### Health Check

```bash
# Check if server is running
curl http://localhost:2346/health

# Check connections
php websocket-server.php status

# Monitor logs
tail -f /tmp/workerman.log | grep ERROR
```

### Metrics

- Active connections
- Messages per second
- Average latency
- Error rate
- Memory usage

---

## References

- [Workerman Documentation](https://www.workerman.net/)
- [WebSocket API](https://developer.mozilla.org/en-US/docs/Web/API/WebSocket)
- [CodeIgniter 4 Documentation](https://codeigniter.com/user_guide/)

---

**Developed for Sistema de Ponto Eletrônico**
**Compliance**: LGPD (Lei 13.709/2018) | MTE 671/2021
