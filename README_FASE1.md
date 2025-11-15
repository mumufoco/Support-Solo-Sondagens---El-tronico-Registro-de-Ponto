# Fase 1: Setup Inicial - CONCLUÍDO ✅

## Sistema de Ponto Eletrônico

Implementação completa da Fase 1 conforme `plano_Inicial_R2` (Semana 2-3).

---

## 📋 Checklist da Fase 1

### ✅ Comando 1.1: Estrutura Base do Projeto

- [x] Estrutura de diretórios CodeIgniter 4
- [x] `composer.json` configurado com dependências:
  - `codeigniter4/framework` ^4.4
  - `codeigniter4/shield` ^1.0 (autenticação)
  - `phpoffice/phpspreadsheet` ^1.29 (Excel)
  - `tecnickcom/tcpdf` ^6.6 (PDF)
  - `guzzlehttp/guzzle` ^7.8 (HTTP requests)
  - `chillerlan/php-qrcode` ^5.0 (QR Code)
  - `minishlink/web-push` ^8.0 (Push Notifications)
- [x] `.env.example` com todas variáveis necessárias

### ✅ Comando 1.2: Banco de Dados e Migrations

**10 Migrations Principais:**

1. ✅ `employees` - Cadastro de funcionários
2. ✅ `time_punches` - Registros de ponto
3. ✅ `biometric_templates` - Templates biométricos
4. ✅ `justifications` - Justificativas de faltas/atrasos
5. ✅ `geofences` - Cercas virtuais (geolocalização)
6. ✅ `chat_messages` - Mensagens antigas (legacy)
7. ✅ `warnings` - Advertências
8. ✅ `user_consents` - Consentimentos LGPD
9. ✅ `audit_logs` - Logs de auditoria
10. ✅ `notifications` - Notificações do sistema

**Migrations Adicionais:**

11. ✅ `settings` - Configurações do sistema
12. ✅ `chat_rooms` - Salas de chat
13. ✅ `chat_room_members` - Membros das salas
14. ✅ `chat_messages` (nova) - Mensagens de chat
15. ✅ `chat_message_reactions` - Reações em mensagens
16. ✅ `chat_online_users` - Usuários online
17. ✅ `push_subscriptions` - Inscrições push notifications

### ✅ Comando 1.3: Seeders

- [x] `AdminUserSeeder` - Cria admin padrão
  - Email: `admin@ponto.com.br`
  - Senha: `Admin@123` (Argon2ID)
  - CPF: `111.111.111-11`
  - Role: `admin`
  - Código único gerado automaticamente
  - Consentimento LGPD criado
  - Audit log registrado

- [x] `SettingsSeeder` - Configurações iniciais
  - Horário expediente: 08:00-18:00
  - Jornada diária: 8 horas
  - Intervalo obrigatório: 60 min (>6h)
  - Raio cerca virtual: 100m
  - Max upload: 5MB
  - DeepFace threshold: 0.40
  - Notificações habilitadas

---

## 🚀 Como Usar

### 1. Instalar Dependências

```bash
composer install
```

### 2. Configurar Ambiente

```bash
# Copiar .env.example para .env
cp .env.example .env

# Editar .env com suas configurações
nano .env
```

**Configurações mínimas necessárias:**

```env
# Database
database.default.hostname = localhost
database.default.database = ponto_eletronico
database.default.username = root
database.default.password = sua_senha

# App
app.baseURL = 'http://localhost:8080/'
encryption.key = sua_chave_gerada

# DeepFace
DEEPFACE_API_URL = 'http://localhost:5000'
DEEPFACE_THRESHOLD = 0.40
```

### 3. Gerar Chave de Encriptação

```bash
php spark key:generate
```

### 4. Executar Migrations

```bash
# Executar todas migrations
php spark migrate

# Ver status
php spark migrate:status

# Rollback (se necessário)
php spark migrate:rollback
```

### 5. Executar Seeders

```bash
# Seeder do admin
php spark db:seed AdminUserSeeder

# Seeder de configurações
php spark db:seed SettingsSeeder

# Ou todos de uma vez
php spark db:seed DatabaseSeeder
```

### 6. Iniciar Servidor de Desenvolvimento

```bash
php spark serve
```

Acesse: `http://localhost:8080`

---

## 📊 Estrutura do Banco de Dados

### Tabelas Principais

| Tabela | Descrição | Registros |
|--------|-----------|-----------|
| `employees` | Funcionários | ~100-1000 |
| `time_punches` | Marcações de ponto | ~1M/ano |
| `biometric_templates` | Templates faciais | ~100-1000 |
| `justifications` | Justificativas | ~1K/mês |
| `geofences` | Cercas virtuais | ~1-10 |
| `chat_messages` | Mensagens chat | ~10K/mês |
| `warnings` | Advertências | ~100/ano |
| `user_consents` | Consentimentos LGPD | ~100-1000 |
| `audit_logs` | Logs auditoria | ~100K/ano |
| `notifications` | Notificações | ~10K/mês |
| `settings` | Configurações | ~50 |

### Índices Importantes

- **employees**: `email` (UNIQUE), `cpf` (UNIQUE), `unique_code` (UNIQUE)
- **time_punches**: `(employee_id, punch_time)`, `punch_time`, `nsr` (UNIQUE)
- **biometric_templates**: `(employee_id, biometric_type)`, `active`
- **audit_logs**: `(user_id, created_at)`, `(table_name, record_id)`

---

## 🔐 Credenciais Padrão

Após executar os seeders:

```
Admin Login:
  URL: http://localhost:8080/admin/login
  Email: admin@ponto.com.br
  Password: Admin@123

⚠️ IMPORTANTE: Altere a senha após primeiro login!
```

---

## 📂 Estrutura de Diretórios

```
/
├── app/
│   ├── Config/          # Configurações
│   ├── Controllers/     # Controllers
│   ├── Database/
│   │   ├── Migrations/  # 17 migrations
│   │   └── Seeds/       # 5 seeders
│   ├── Models/          # Models
│   ├── Services/        # Services (ChatService, PushNotificationService)
│   ├── Helpers/         # Helpers (file_upload_helper)
│   └── Views/           # Views (chat/)
│
├── public/
│   ├── assets/
│   │   └── js/
│   │       ├── chat.js                   # WebSocket client
│   │       └── push-notifications.js     # Push manager
│   └── sw.js                             # Service Worker
│
├── writable/            # Logs, cache, uploads
├── tests/               # Testes
│
├── composer.json        # Dependências PHP
├── .env.example         # Template de configuração
├── prototype_punch.html # Protótipo POC
└── test_deepface.py     # POC DeepFace
```

---

## 🧪 Testes

### Testar Migrations

```bash
# Criar banco de teste
mysql -u root -p -e "CREATE DATABASE ponto_eletronico_test;"

# Executar migrations no ambiente de teste
CI_ENVIRONMENT=testing php spark migrate

# Verificar tabelas criadas
mysql -u root -p ponto_eletronico_test -e "SHOW TABLES;"
```

### Testar Seeders

```bash
# Executar seeders
php spark db:seed AdminUserSeeder
php spark db:seed SettingsSeeder

# Verificar dados criados
mysql -u root -p ponto_eletronico -e "SELECT * FROM employees WHERE role='admin';"
mysql -u root -p ponto_eletronico -e "SELECT COUNT(*) FROM settings;"
```

---

## 🐛 Troubleshooting

### Erro: "Unknown database 'ponto_eletronico'"

```bash
mysql -u root -p -e "CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

### Erro: "SQLSTATE[42000]: Syntax error"

Verifique se está usando MySQL 8.0+:

```bash
mysql --version
```

### Erro: "Class 'App\Database\Seeds\AdminUserSeeder' not found"

```bash
composer dump-autoload
```

### Migrations não executam

```bash
# Limpar cache
php spark cache:clear

# Ver status detalhado
php spark migrate:status

# Forçar execução
php spark migrate --all
```

---

## 📝 Checklist de Validação

Antes de prosseguir para Fase 2, verifique:

- [ ] ✅ Composer install executado sem erros
- [ ] ✅ `.env` configurado corretamente
- [ ] ✅ Chave de encriptação gerada
- [ ] ✅ Banco de dados criado
- [ ] ✅ Todas migrations executadas (17 tabelas)
- [ ] ✅ Seeders executados
- [ ] ✅ Admin criado e consegue fazer login
- [ ] ✅ Configurações carregam corretamente
- [ ] ✅ Servidor roda sem erros

---

## 🎯 Próximos Passos

### Fase 2: Setup DeepFace API (Semana 4)

1. Criar microserviço DeepFace API em Python
2. Configurar como serviço systemd
3. Integrar PHP com DeepFace

### Fase 3: Autenticação e Perfis (Semana 5-6)

1. Implementar sistema de autenticação (Shield)
2. Criar dashboards por perfil (Admin, Gestor, Funcionário)

---

## 📚 Referências

- [CodeIgniter 4 Docs](https://codeigniter.com/user_guide/)
- [CodeIgniter Shield](https://shield.codeigniter.com/)
- [Portaria MTE 671/2021](http://www.normaslegais.com.br/legislacao/portariamte671_2021.htm)
- [LGPD Lei 13.709/2018](http://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm)

---

## ✅ Status da Fase 1

**CONCLUÍDO** - Todos os comandos da Fase 1 implementados com sucesso.

- ✅ Comando 1.1: Estrutura base criada
- ✅ Comando 1.2: Migrations criadas e testadas
- ✅ Comando 1.3: Seeders criados e testados

**Data de Conclusão**: 2025-01-15
**Commit**: `[hash]` - "Complete Fase 1: Setup Inicial"
