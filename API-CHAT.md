# Chat API Documentation

Complete REST API documentation for the WebSocket Chat System.

## Base URL

```
https://pontoeletronico.com.br/api/chat
```

## Authentication

All API endpoints require authentication via Bearer token or session cookie.

### Bearer Token

```http
Authorization: Bearer YOUR_TOKEN_HERE
```

### Session Cookie

```http
Cookie: ci_session=YOUR_SESSION_ID
```

---

## Endpoints Overview

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/rooms` | Get all rooms for authenticated user |
| POST | `/rooms/private` | Create private 1-on-1 room |
| POST | `/rooms/group` | Create group room |
| GET | `/rooms/{id}/messages` | Get messages in room |
| POST | `/rooms/{id}/messages` | Send message to room |
| POST | `/rooms/{id}/read` | Mark room as read |
| GET | `/rooms/{id}/search` | Search messages in room |
| GET | `/rooms/{id}/members` | Get room members |
| POST | `/rooms/{id}/members` | Add member to room |
| DELETE | `/rooms/{id}/members/{memberId}` | Remove member from room |
| PUT | `/messages/{id}` | Edit message |
| DELETE | `/messages/{id}` | Delete message |
| POST | `/messages/{id}/reactions` | Add/remove reaction |
| GET | `/online` | Get online users |

---

## Rooms

### Get All Rooms

Get all chat rooms for the authenticated user.

```http
GET /api/chat/rooms
```

**Response:**

```json
{
  "success": true,
  "rooms": [
    {
      "id": 1,
      "name": "Private Chat",
      "type": "private",
      "department": null,
      "created_by": 5,
      "active": true,
      "created_at": "2024-01-16 10:30:00",
      "updated_at": "2024-01-16 10:30:00",
      "message_count": 42,
      "last_message_at": "2024-01-16 14:25:33",
      "unread_count": 3
    },
    {
      "id": 2,
      "name": "Team Development",
      "type": "group",
      "department": null,
      "created_by": 1,
      "active": true,
      "created_at": "2024-01-15 09:00:00",
      "updated_at": "2024-01-15 09:00:00",
      "message_count": 156,
      "last_message_at": "2024-01-16 15:10:00",
      "unread_count": 0
    }
  ]
}
```

---

### Create Private Room

Create a private 1-on-1 chat room. If room already exists, returns existing room.

```http
POST /api/chat/rooms/private
```

**Request Body:**

```json
{
  "employee_id": 7
}
```

**Response:**

```json
{
  "success": true,
  "room": {
    "id": 15,
    "name": "Private Chat",
    "type": "private",
    "department": null,
    "created_by": 3,
    "active": true,
    "created_at": "2024-01-16 16:00:00",
    "updated_at": "2024-01-16 16:00:00"
  }
}
```

**Error Response:**

```json
{
  "success": false,
  "message": "ID do funcionário é obrigatório."
}
```

---

### Create Group Room

Create a group chat room with multiple members.

```http
POST /api/chat/rooms/group
```

**Request Body:**

```json
{
  "name": "Project Alpha Team",
  "members": [5, 7, 9, 12]
}
```

**Response:**

```json
{
  "success": true,
  "room": {
    "id": 16,
    "name": "Project Alpha Team",
    "type": "group",
    "department": null,
    "created_by": 3,
    "active": true,
    "created_at": "2024-01-16 16:05:00",
    "updated_at": "2024-01-16 16:05:00"
  }
}
```

**Validation Errors:**

```json
{
  "success": false,
  "message": {
    "name": "O nome da sala é obrigatório.",
    "members": "Membros são obrigatórios."
  }
}
```

---

## Messages

### Get Messages

Get messages in a room with pagination.

```http
GET /api/chat/rooms/{roomId}/messages?limit=50&offset=0&before_id=100
```

**Query Parameters:**

- `limit` (optional): Number of messages to return (default: 50)
- `offset` (optional): Offset for pagination (default: 0)
- `before_id` (optional): Get messages before this message ID

**Response:**

```json
{
  "success": true,
  "messages": [
    {
      "id": 101,
      "room_id": 1,
      "sender_id": 5,
      "sender_name": "João Silva",
      "sender_email": "joao@example.com",
      "message": "Olá, tudo bem?",
      "type": "text",
      "file_path": null,
      "file_name": null,
      "file_size": null,
      "reply_to": null,
      "reply_message": null,
      "reply_sender_name": null,
      "edited_at": null,
      "created_at": "2024-01-16 14:20:00",
      "reactions": {
        "👍": 2,
        "❤️": 1
      }
    },
    {
      "id": 100,
      "room_id": 1,
      "sender_id": 3,
      "sender_name": "Maria Santos",
      "sender_email": "maria@example.com",
      "message": "Sim! E você?",
      "type": "text",
      "file_path": null,
      "file_name": null,
      "file_size": null,
      "reply_to": 101,
      "reply_message": "Olá, tudo bem?",
      "reply_sender_name": "João Silva",
      "edited_at": null,
      "created_at": "2024-01-16 14:21:15",
      "reactions": {}
    }
  ]
}
```

**Error Response:**

```json
{
  "success": false,
  "message": "Você não é membro desta sala."
}
```

---

### Send Message

Send a text message to a room.

```http
POST /api/chat/rooms/{roomId}/messages
```

**Request Body:**

```json
{
  "message": "Hello, world!",
  "reply_to": 101
}
```

**Parameters:**

- `message` (required): Message text (max 5000 characters)
- `reply_to` (optional): Message ID to reply to

**Response:**

```json
{
  "success": true,
  "message": {
    "id": 102,
    "room_id": 1,
    "sender_id": 3,
    "message": "Hello, world!",
    "type": "text",
    "file_path": null,
    "file_name": null,
    "file_size": null,
    "reply_to": 101,
    "edited_at": null,
    "created_at": "2024-01-16 14:25:00"
  }
}
```

**Validation Errors:**

```json
{
  "success": false,
  "message": {
    "message": "A mensagem é obrigatória."
  }
}
```

---

### Edit Message

Edit an existing message (15-minute window).

```http
PUT /api/chat/messages/{messageId}
```

**Request Body:**

```json
{
  "message": "Updated message text"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Mensagem editada com sucesso."
}
```

**Error Response:**

```json
{
  "success": false,
  "message": "Você só pode editar mensagens enviadas nos últimos 15 minutos."
}
```

---

### Delete Message

Delete a message (own messages or admin).

```http
DELETE /api/chat/messages/{messageId}
```

**Response:**

```json
{
  "success": true,
  "message": "Mensagem excluída com sucesso."
}
```

**Error Response:**

```json
{
  "success": false,
  "message": "Você não pode excluir esta mensagem."
}
```

---

### Add Reaction

Add or remove an emoji reaction to a message (toggle).

```http
POST /api/chat/messages/{messageId}/reactions
```

**Request Body:**

```json
{
  "emoji": "👍"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Reação adicionada/removida com sucesso."
}
```

---

### Mark Room as Read

Mark all messages in a room as read.

```http
POST /api/chat/rooms/{roomId}/read
```

**Response:**

```json
{
  "success": true,
  "message": "Marcado como lido."
}
```

---

### Search Messages

Search messages in a room.

```http
GET /api/chat/rooms/{roomId}/search?q=hello
```

**Query Parameters:**

- `q` (required): Search query (minimum 3 characters)

**Response:**

```json
{
  "success": true,
  "messages": [
    {
      "id": 102,
      "room_id": 1,
      "sender_id": 3,
      "sender_name": "Maria Santos",
      "message": "Hello, world!",
      "type": "text",
      "created_at": "2024-01-16 14:25:00"
    },
    {
      "id": 95,
      "room_id": 1,
      "sender_id": 5,
      "sender_name": "João Silva",
      "message": "Hello everyone!",
      "type": "text",
      "created_at": "2024-01-16 12:10:00"
    }
  ]
}
```

---

## Members

### Get Room Members

Get all members of a room.

```http
GET /api/chat/rooms/{roomId}/members
```

**Response:**

```json
{
  "success": true,
  "members": [
    {
      "id": 1,
      "room_id": 1,
      "employee_id": 3,
      "name": "Maria Santos",
      "email": "maria@example.com",
      "department": "TI",
      "role": "admin",
      "last_read_at": "2024-01-16 14:25:00",
      "joined_at": "2024-01-15 10:00:00",
      "is_online": true
    },
    {
      "id": 2,
      "room_id": 1,
      "employee_id": 5,
      "name": "João Silva",
      "email": "joao@example.com",
      "department": "RH",
      "role": "member",
      "last_read_at": "2024-01-16 14:20:00",
      "joined_at": "2024-01-15 10:00:00",
      "is_online": false
    }
  ]
}
```

---

### Add Member

Add a member to a room (admin/creator only).

```http
POST /api/chat/rooms/{roomId}/members
```

**Request Body:**

```json
{
  "employee_id": 7
}
```

**Response:**

```json
{
  "success": true,
  "message": "Membro adicionado com sucesso."
}
```

**Error Response:**

```json
{
  "success": false,
  "message": "Você não tem permissão para adicionar membros."
}
```

---

### Remove Member

Remove a member from a room (admin/creator only).

```http
DELETE /api/chat/rooms/{roomId}/members/{memberId}
```

**Response:**

```json
{
  "success": true,
  "message": "Membro removido com sucesso."
}
```

**Error Response:**

```json
{
  "success": false,
  "message": "Você não tem permissão para remover membros."
}
```

---

## Online Users

### Get Online Users

Get list of currently online users.

```http
GET /api/chat/online
```

**Response:**

```json
{
  "success": true,
  "users": [
    {
      "id": 1,
      "employee_id": 3,
      "name": "Maria Santos",
      "email": "maria@example.com",
      "department": "TI",
      "connection_id": "abc123xyz",
      "status": "online",
      "last_activity": "2024-01-16 14:30:00",
      "created_at": "2024-01-16 08:00:00"
    },
    {
      "id": 2,
      "employee_id": 7,
      "name": "Pedro Costa",
      "email": "pedro@example.com",
      "department": "Vendas",
      "connection_id": "def456uvw",
      "status": "away",
      "last_activity": "2024-01-16 14:25:00",
      "created_at": "2024-01-16 09:15:00"
    }
  ]
}
```

---

## Error Codes

| HTTP Code | Description |
|-----------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request (validation errors) |
| 401 | Unauthorized (not authenticated) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 500 | Internal Server Error |

---

## Rate Limiting

API requests are rate limited to:

- **60 requests per minute** per user
- **Burst**: 20 additional requests

When rate limit is exceeded:

```json
{
  "success": false,
  "message": "Rate limit exceeded. Please try again later.",
  "retry_after": 45
}
```

---

## Examples

### cURL Examples

#### Get Rooms

```bash
curl -X GET https://pontoeletronico.com.br/api/chat/rooms \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Send Message

```bash
curl -X POST https://pontoeletronico.com.br/api/chat/rooms/1/messages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Hello, world!",
    "reply_to": 101
  }'
```

#### Create Group

```bash
curl -X POST https://pontoeletronico.com.br/api/chat/rooms/group \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Project Team",
    "members": [5, 7, 9]
  }'
```

---

### JavaScript Examples

#### Using Fetch API

```javascript
// Get rooms
async function getRooms() {
  const response = await fetch('/api/chat/rooms', {
    headers: {
      'Authorization': 'Bearer ' + authToken
    }
  });
  const data = await response.json();
  return data.rooms;
}

// Send message
async function sendMessage(roomId, message) {
  const response = await fetch(`/api/chat/rooms/${roomId}/messages`, {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + authToken,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ message })
  });
  return await response.json();
}

// Add reaction
async function addReaction(messageId, emoji) {
  const response = await fetch(`/api/chat/messages/${messageId}/reactions`, {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + authToken,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ emoji })
  });
  return await response.json();
}
```

---

### PHP Examples

#### Using Guzzle

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://pontoeletronico.com.br/api/chat/',
    'headers' => [
        'Authorization' => 'Bearer ' . $authToken,
        'Content-Type' => 'application/json',
    ]
]);

// Get rooms
$response = $client->get('rooms');
$rooms = json_decode($response->getBody(), true);

// Send message
$response = $client->post('rooms/1/messages', [
    'json' => [
        'message' => 'Hello, world!'
    ]
]);

// Create private room
$response = $client->post('rooms/private', [
    'json' => [
        'employee_id' => 7
    ]
]);
```

---

## WebSocket Integration

For real-time updates, use WebSocket connection alongside REST API:

```javascript
// Connect to WebSocket
const ws = new WebSocket('ws://pontoeletronico.com.br:2346');

// Authenticate
ws.onopen = () => {
  ws.send(JSON.stringify({
    type: 'auth',
    token: authToken
  }));
};

// Listen for messages
ws.onmessage = (event) => {
  const data = JSON.parse(event.data);

  if (data.type === 'message') {
    // New message received
    console.log('New message:', data);
  }
};

// Send message via WebSocket (faster than REST)
ws.send(JSON.stringify({
  type: 'message',
  room_id: 1,
  message: 'Hello via WebSocket!'
}));
```

**Recommendation**: Use REST API for initial data loading and CRUD operations, use WebSocket for real-time updates.

---

## Best Practices

1. **Pagination**: Always use `limit` and `offset` for message history
2. **Caching**: Cache room list and member list locally
3. **WebSocket First**: Use WebSocket for sending messages for lower latency
4. **Error Handling**: Always check `success` field in response
5. **Rate Limiting**: Implement exponential backoff on 429 errors
6. **Offline Support**: Queue messages when offline, send when reconnected
7. **Security**: Never expose Bearer tokens in client-side code
8. **Validation**: Validate input client-side before sending to API

---

## Changelog

### Version 1.0.0 (2024-01-16)
- Initial release
- 15 REST API endpoints
- Room management
- Message CRUD operations
- Member management
- Reaction support
- Online users tracking

---

**Developed for Sistema de Ponto Eletrônico**
**Compliance**: LGPD (Lei 13.709/2018) | MTE 671/2021
