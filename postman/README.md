# Postman Collection - Chat API

Postman collection for testing the Chat API endpoints.

## 📦 Files

- `Chat-API.postman_collection.json` - Complete API collection with all endpoints
- `Chat-API.postman_environment.json` - Environment variables for development

## 🚀 Quick Start

### 1. Import Collection

**Option A: Import from File**
1. Open Postman
2. Click **Import** button
3. Select `Chat-API.postman_collection.json`
4. Click **Import**

**Option B: Import via URL (if hosted)**
```
https://www.getpostman.com/collections/YOUR_COLLECTION_ID
```

### 2. Import Environment

1. Click **Import** button
2. Select `Chat-API.postman_environment.json`
3. Click **Import**
4. Select the imported environment from dropdown (top-right)

### 3. Configure Variables

Update the following variables in your environment:

| Variable | Description | Example |
|----------|-------------|---------|
| `base_url` | API base URL | `http://localhost/api/chat` |
| `auth_token` | Bearer authentication token | `eyJ0eXAiOiJKV1Q...` |
| `room_id` | Room ID for testing | `1` |
| `message_id` | Message ID for testing | `1` |
| `employee_id` | Employee ID for testing | `1` |

## 📋 Collection Structure

### Folders

1. **Rooms**
   - Get All Rooms
   - Create Private Room
   - Create Group Room

2. **Messages**
   - Get Messages
   - Send Message
   - Edit Message
   - Delete Message
   - Search Messages

3. **Reactions**
   - Add Reaction (toggle)

4. **Room Management**
   - Mark as Read
   - Get Members
   - Add Member
   - Remove Member

5. **Online Users**
   - Get Online Users

## 🔐 Authentication

The collection uses **Bearer Token** authentication.

### Get Auth Token

1. Login via web interface or API
2. Copy the Bearer token from response or session
3. Set `auth_token` variable in environment
4. All requests will automatically include the token

**Example:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

## 🧪 Testing Workflow

### Basic Workflow

1. **Authenticate** (get token from login endpoint)
2. **Get Rooms** - View all available rooms
3. **Create Private Room** - Start 1-on-1 chat
4. **Send Message** - Send a test message
5. **Get Messages** - Retrieve message history
6. **Add Reaction** - React with emoji
7. **Mark as Read** - Mark messages as read

### Advanced Workflow

1. **Create Group Room** - Create multi-user room
2. **Get Members** - View room participants
3. **Add Member** - Invite new participant
4. **Search Messages** - Find specific messages
5. **Edit Message** - Modify recent message
6. **Remove Member** - Remove participant
7. **Delete Message** - Delete message

## 📝 Example Requests

### Create Private Room

```http
POST {{base_url}}/rooms/private
Content-Type: application/json
Authorization: Bearer {{auth_token}}

{
  "employee_id": 7
}
```

### Send Message

```http
POST {{base_url}}/rooms/{{room_id}}/messages
Content-Type: application/json
Authorization: Bearer {{auth_token}}

{
  "message": "Hello, world!",
  "reply_to": null
}
```

### Add Reaction

```http
POST {{base_url}}/messages/{{message_id}}/reactions
Content-Type: application/json
Authorization: Bearer {{auth_token}}

{
  "emoji": "👍"
}
```

## 🌍 Environments

### Development

```json
{
  "base_url": "http://localhost/api/chat",
  "auth_token": "YOUR_DEV_TOKEN"
}
```

### Staging

```json
{
  "base_url": "https://staging.pontoeletronico.com.br/api/chat",
  "auth_token": "YOUR_STAGING_TOKEN"
}
```

### Production

```json
{
  "base_url": "https://pontoeletronico.com.br/api/chat",
  "auth_token": "YOUR_PRODUCTION_TOKEN"
}
```

## 🔍 Testing Tips

### Variables

Use Postman variables for dynamic data:

```javascript
// In Tests tab
pm.environment.set("room_id", pm.response.json().room.id);
pm.environment.set("message_id", pm.response.json().message.id);
```

### Pre-request Scripts

Set up data before request:

```javascript
// Generate timestamp
pm.environment.set("timestamp", new Date().toISOString());

// Random emoji
const emojis = ["👍", "❤️", "😂", "😮", "😢"];
const randomEmoji = emojis[Math.floor(Math.random() * emojis.length)];
pm.environment.set("emoji", randomEmoji);
```

### Tests

Validate responses:

```javascript
// Status code
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

// Response time
pm.test("Response time < 500ms", function () {
    pm.expect(pm.response.responseTime).to.be.below(500);
});

// JSON structure
pm.test("Response has success field", function () {
    pm.response.to.have.jsonBody("success");
});

// Save room ID for next request
if (pm.response.json().success) {
    pm.environment.set("room_id", pm.response.json().room.id);
}
```

## 🐛 Troubleshooting

### 401 Unauthorized

**Problem**: Token is invalid or expired

**Solution**:
1. Get new token from login endpoint
2. Update `auth_token` in environment
3. Retry request

### 403 Forbidden

**Problem**: Insufficient permissions

**Solution**:
1. Check user role (admin, gestor, funcionario)
2. Ensure user is room member
3. Verify endpoint permissions

### 404 Not Found

**Problem**: Resource doesn't exist

**Solution**:
1. Verify `room_id` or `message_id` is correct
2. Use "Get All Rooms" to find valid IDs
3. Check if resource was deleted

### 429 Rate Limited

**Problem**: Too many requests

**Solution**:
1. Wait before retrying
2. Check `retry_after` header
3. Implement exponential backoff

## 📚 Additional Resources

- [API Documentation](../API-CHAT.md)
- [WebSocket Documentation](../WEBSOCKET-CHAT.md)
- [Postman Documentation](https://learning.postman.com/docs/)

## 🔄 Changelog

### Version 1.0.0 (2024-01-16)
- Initial release
- 15 API endpoints
- Development environment
- Complete test coverage

---

**Developed for Sistema de Ponto Eletrônico**
**Compliance**: LGPD (Lei 13.709/2018) | MTE 671/2021
