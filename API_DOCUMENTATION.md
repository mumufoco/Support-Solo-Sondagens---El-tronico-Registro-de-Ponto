# 📚 Documentação da API - Sistema de Ponto Eletrônico

## 📋 Visão Geral

API RESTful para gerenciamento de ponto eletrônico conforme Portaria MTE 671/2021, CLT e LGPD.

**Base URL:** `https://ponto.supportsondagens.com.br/api`

**Documentação OpenAPI/Swagger:** Acesse `/openapi.yaml` ou visualize em [Swagger Editor](https://editor.swagger.io/)

---

## 🔐 Autenticação

Todos os endpoints (exceto `/auth/login`) requerem autenticação via Bearer Token (JWT).

### Obter Token

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "usuario@empresa.com",
  "password": "SenhaForte123!@#"
}
```

**Resposta:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao.silva@empresa.com",
    "role": "funcionario"
  }
}
```

### Usar Token

Inclua o token no header de todas as requisições:

```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## 📊 Rate Limiting

A API implementa rate limiting para prevenir abuso:

| Endpoint | Limite |
|----------|--------|
| `/api/auth/login` | 5 tentativas / 5 minutos |
| `/api/punch/*` | 10 requisições / minuto |
| `/api/*` (geral) | 60 requisições / minuto |

**Headers de Rate Limit:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1634567890
```

**Resposta quando limite excedido (429):**
```json
{
  "success": false,
  "error": "Muitas requisições. Tente novamente mais tarde."
}
```

---

## 🛣️ Principais Endpoints

### 1. Autenticação

#### Login
```http
POST /api/auth/login
```

#### Refresh Token
```http
POST /api/auth/refresh
Authorization: Bearer <token>
```

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer <token>
```

---

### 2. Funcionários

#### Listar Funcionários
```http
GET /api/employees?page=1&limit=20&active=true
Authorization: Bearer <token>
```

**Permissões:** Admin, Gestor

**Parâmetros de Query:**
- `page` (int): Página (padrão: 1)
- `limit` (int): Itens por página (padrão: 20)
- `active` (bool): Filtrar por ativos

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "João Silva",
      "email": "joao.silva@empresa.com",
      "department": "TI",
      "position": "Desenvolvedor",
      "active": true
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 100,
    "items_per_page": 20
  }
}
```

#### Buscar Funcionário
```http
GET /api/employees/{id}
Authorization: Bearer <token>
```

#### Criar Funcionário
```http
POST /api/employees
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Maria Santos",
  "email": "maria@empresa.com",
  "cpf": "123.456.789-00",
  "password": "SenhaForte123!@#",
  "role": "funcionario",
  "department": "RH",
  "position": "Analista"
}
```

**Permissões:** Admin, Gestor

---

### 3. Registro de Ponto

#### Registrar Ponto
```http
POST /api/punch
Authorization: Bearer <token>
Content-Type: application/json

{
  "type": "entrada",
  "latitude": -23.5505199,
  "longitude": -46.6333094,
  "photo": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
  "fingerprint": "template_biometrico_base64"
}
```

**Tipos válidos:**
- `entrada` - Entrada no trabalho
- `saida` - Saída do trabalho
- `pausa_inicio` - Início do intervalo
- `pausa_fim` - Fim do intervalo

**Resposta:**
```json
{
  "success": true,
  "message": "Ponto de entrada registrado com sucesso",
  "data": {
    "id": 12345,
    "employee_id": 1,
    "type": "entrada",
    "timestamp": "2024-11-17T08:00:00-03:00",
    "latitude": -23.5505199,
    "longitude": -46.6333094,
    "method": "app"
  }
}
```

#### Meus Registros
```http
GET /api/punch/my?date=2024-11-17
Authorization: Bearer <token>
```

---

### 4. Dashboard

#### Estatísticas
```http
GET /api/dashboard/stats
Authorization: Bearer <token>
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "total_employees": 150,
    "active_today": 142,
    "pending_approvals": 8,
    "hours_worked_today": 1250.5,
    "late_arrivals": 3
  }
}
```

---

### 5. Notificações

#### Listar Notificações
```http
GET /api/notifications?unread=true
Authorization: Bearer <token>
```

#### Marcar como Lida
```http
PUT /api/notifications/{id}/read
Authorization: Bearer <token>
```

#### Registrar Token Push
```http
POST /api/notifications/subscribe
Authorization: Bearer <token>
Content-Type: application/json

{
  "token": "firebase_device_token",
  "device_type": "android",
  "device_fingerprint": "unique_device_id"
}
```

---

## ❌ Códigos de Erro

| Código | Descrição |
|--------|-----------|
| 200 | Sucesso |
| 201 | Criado com sucesso |
| 400 | Requisição inválida |
| 401 | Não autenticado |
| 403 | Acesso negado (sem permissão) |
| 404 | Recurso não encontrado |
| 422 | Dados de validação inválidos |
| 429 | Rate limit excedido |
| 500 | Erro interno do servidor |

---

## 📝 Exemplos de Uso

### cURL

```bash
# Login
curl -X POST https://ponto.supportsondagens.com.br/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@empresa.com","password":"senha123"}'

# Registrar Ponto
curl -X POST https://ponto.supportsondagens.com.br/api/punch \
  -H "Authorization: Bearer TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{"type":"entrada","latitude":-23.5505,"longitude":-46.6333}'
```

### JavaScript (Fetch API)

```javascript
// Login
const login = async () => {
  const response = await fetch('https://ponto.supportsondagens.com.br/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email: 'user@empresa.com',
      password: 'senha123'
    })
  });
  const data = await response.json();
  return data.token;
};

// Registrar Ponto
const punchClock = async (token) => {
  const response = await fetch('https://ponto.supportsondagens.com.br/api/punch', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      type: 'entrada',
      latitude: -23.5505199,
      longitude: -46.6333094
    })
  });
  return await response.json();
};
```

### Python (requests)

```python
import requests

# Login
def login():
    response = requests.post(
        'https://ponto.supportsondagens.com.br/api/auth/login',
        json={'email': 'user@empresa.com', 'password': 'senha123'}
    )
    return response.json()['token']

# Registrar Ponto
def punch_clock(token):
    headers = {'Authorization': f'Bearer {token}'}
    data = {
        'type': 'entrada',
        'latitude': -23.5505199,
        'longitude': -46.6333094
    }
    response = requests.post(
        'https://ponto.supportsondagens.com.br/api/punch',
        headers=headers,
        json=data
    )
    return response.json()
```

---

## 🔒 Segurança

### Headers de Segurança

Todas as respostas incluem headers de segurança:

```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000
```

### HTTPS Obrigatório

Todo tráfego HTTP é redirecionado para HTTPS automaticamente.

### Token JWT

- Expiração: 2 horas
- Algoritmo: HS256
- Refresh token disponível

---

## 📞 Suporte

- **Email:** admin@supportsondagens.com.br
- **Documentação:** https://ponto.supportsondagens.com.br/docs
- **Status da API:** https://ponto.supportsondagens.com.br/api/health

---

## 📄 Licença

MIT License - Support Solo Sondagens LTDA
