# 🔧 Correção do Erro 500 - Navegação

**Data:** 16 de Novembro de 2025
**Problema:** Todas as navegações retornam erro 500
**Causa Raiz:** Configuração do .env para Docker mas aplicação rodando fora do Docker

---

## 🔍 Diagnóstico Realizado

### Logs Analisados: `storage/logs/log-2025-11-16.log`

**Erros Críticos Identificados:**

1. **Redis não acessível**
   ```
   Cache: RedisException occurred with message
   (php_network_getaddresses: getaddrinfo for redis failed:
   Temporary failure in name resolution)
   ```

2. **MySQL não acessível**
   ```
   Unable to connect to the database.
   Main connection [MySQLi]: php_network_getaddresses:
   getaddrinfo for mysql failed: Temporary failure in name resolution
   ```

### Causa Raiz

O arquivo `.env` está configurado com hostnames Docker:
- `database.default.hostname = mysql` ❌
- `REDIS_HOST = redis` ❌
- `cache.redis.host = redis` ❌

Mas a aplicação está rodando **fora do Docker**, então precisa usar `localhost` ou `127.0.0.1`.

---

## ✅ Solução 1: Usar Arquivo .env para Localhost (Sem Docker)

### Passo 1: Backup do .env atual

```bash
cp .env .env.docker.backup
```

### Passo 2: Editar o .env

Abra o arquivo `.env` e altere as seguintes linhas:

```bash
nano .env
```

**ALTERAÇÕES NECESSÁRIAS:**

```env
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = development

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
app.baseURL = 'http://localhost:8080/'
# OU use a porta que você está usando, ex: http://localhost:80/
app.indexPage = ''
app.forceGlobalSecureRequests = false  # IMPORTANTE: false para HTTP local
app.appTimezone = 'America/Sao_Paulo'
app.defaultLocale = 'pt-BR'

# ENCRYPTION - Generate with: php spark key:generate
encryption.key = ''  # Deixe vazio se não tiver gerado ainda

#--------------------------------------------------------------------
# DATABASE (Localhost - Sem Docker)
#--------------------------------------------------------------------
database.default.hostname = localhost  # MUDANÇA: mysql → localhost
database.default.database = ponto_eletronico
database.default.username = root       # MUDANÇA: use seu usuário MySQL local
database.default.password =            # MUDANÇA: senha do seu MySQL local
database.default.DBDriver = MySQLi
database.default.port = 3306
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_unicode_ci

# Environment variables (compatibilidade)
DB_HOST = localhost                    # MUDANÇA: mysql → localhost
DB_DATABASE = ponto_eletronico
DB_USERNAME = root                     # MUDANÇA: use seu usuário MySQL local
DB_PASSWORD =                          # MUDANÇA: senha do seu MySQL local

#--------------------------------------------------------------------
# CACHE (File Handler - Sem Redis)
#--------------------------------------------------------------------
cache.handler = 'file'                 # MUDANÇA: redis → file
# cache.redis.host = redis             # COMENTAR: não usar Redis
# cache.redis.password =               # COMENTAR: não usar Redis
# cache.redis.port = 6379              # COMENTAR: não usar Redis

# REDIS_HOST = localhost               # COMENTAR: não usar Redis
# REDIS_PORT = 6379                    # COMENTAR: não usar Redis
# REDIS_PASSWORD =                     # COMENTAR: não usar Redis

#--------------------------------------------------------------------
# SESSION (File Handler - Sem Redis)
#--------------------------------------------------------------------
session.driver = 'CodeIgniter\Session\Handlers\FileHandler'  # MUDANÇA
session.cookieName = 'ponto_session'
session.expiration = 7200
# session.savePath está configurado em app/Config/App.php
session.matchIP = false
session.timeToUpdate = 300
session.regenerateDestroy = false

#--------------------------------------------------------------------
# SECURITY
#--------------------------------------------------------------------
security.csrfProtection = 'cookie'
security.tokenRandomize = true
security.tokenName = 'csrf_token_name'
security.headerName = 'X-CSRF-TOKEN'
security.cookieName = 'csrf_cookie_name'
security.expires = 7200
security.regenerate = true

#--------------------------------------------------------------------
# DEEPFACE API (Desabilitar se não tiver rodando)
#--------------------------------------------------------------------
DEEPFACE_API_URL = 'http://localhost:5000'  # MUDANÇA: deepface → localhost
DEEPFACE_API_KEY = 'dev-key'
DEEPFACE_THRESHOLD = 0.40
DEEPFACE_MODEL = 'VGG-Face'
```

**Salvar:** `Ctrl + X`, depois `Y`, depois `Enter`

### Passo 3: Gerar Chave de Encriptação (Se ainda não tiver)

```bash
php spark key:generate
```

Isso irá gerar automaticamente uma chave e atualizar o `.env`.

### Passo 4: Verificar Permissões

```bash
chmod -R 775 storage/
chmod -R 775 writable/
chown -R www-data:www-data storage/ writable/
# OU use seu usuário web server
```

### Passo 5: Criar Banco de Dados MySQL Local

```bash
# Conectar ao MySQL
mysql -u root -p

# Criar banco de dados
CREATE DATABASE IF NOT EXISTS ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Criar usuário (opcional)
CREATE USER IF NOT EXISTS 'ponto_user'@'localhost' IDENTIFIED BY 'sua_senha_aqui';
GRANT ALL PRIVILEGES ON ponto_eletronico.* TO 'ponto_user'@'localhost';
FLUSH PRIVILEGES;

EXIT;
```

### Passo 6: Executar Migrations

```bash
php spark migrate
```

### Passo 7: Popular Banco (Seeders)

```bash
php spark db:seed AdminSeeder
```

### Passo 8: Testar Aplicação

```bash
# Se usando PHP built-in server
php spark serve

# Acesse: http://localhost:8080
```

---

## ✅ Solução 2: Usar Docker Corretamente

Se você quer usar Docker, o `.env` atual está correto, mas você precisa:

### 1. Iniciar Docker

```bash
# No diretório do projeto
docker compose up -d

# Verificar se todos os containers estão rodando
docker compose ps
```

### 2. Acessar via Browser

```bash
# A aplicação estará em:
http://localhost
# ou
http://localhost:80
```

### 3. Ver Logs

```bash
docker compose logs -f app
```

---

## 🔍 Verificação Rápida

### Teste de Conectividade MySQL

```bash
# Se usando localhost
mysql -h localhost -u root -p -e "SELECT 1;"

# Se usando Docker
docker compose exec mysql mysql -u ponto_user -p -e "SELECT 1;"
```

### Teste de Conectividade Redis (se usar)

```bash
# Se usando localhost
redis-cli ping
# Esperado: PONG

# Se usando Docker
docker compose exec redis redis-cli ping
# Esperado: PONG
```

### Ver Logs da Aplicação

```bash
# Logs CodeIgniter
tail -f storage/logs/log-$(date +%Y-%m-%d).log

# Se usando Docker
docker compose exec app tail -f storage/logs/log-$(date +%Y-%m-%d).log
```

---

## 📋 Checklist de Resolução

- [ ] Backup do `.env` criado
- [ ] `.env` editado com configurações corretas (localhost ou Docker)
- [ ] Chave de encriptação gerada
- [ ] Banco de dados MySQL criado
- [ ] Permissões de `storage/` e `writable/` ajustadas
- [ ] Migrations executadas
- [ ] Seeders executados (AdminSeeder)
- [ ] Aplicação testada no browser (sem erro 500)
- [ ] Login funcionando

---

## ⚠️ Problemas Comuns

### Erro: "Unable to connect to the database"

**Solução:** Verifique se MySQL está rodando e se as credenciais no `.env` estão corretas.

```bash
# Verificar MySQL
sudo systemctl status mysql
# OU
sudo service mysql status
```

### Erro: "Cache: RedisException"

**Solução:** Se não estiver usando Redis, altere `.env`:

```env
cache.handler = 'file'
session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
```

### Erro: "encryption.key is not set"

**Solução:**

```bash
php spark key:generate
```

### Erro 500 persiste

**Solução:** Limpe cache e sessões:

```bash
rm -rf storage/cache/*
rm -rf storage/logs/*
rm -rf writable/cache/*
rm -rf writable/session/*

# Recriar com permissões
chmod -R 775 storage/ writable/
```

---

## 🎯 Configuração Recomendada por Ambiente

### Desenvolvimento Local (Sem Docker)

```env
CI_ENVIRONMENT = development
database.default.hostname = localhost
cache.handler = 'file'
session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
app.forceGlobalSecureRequests = false
```

### Produção com Docker

```env
CI_ENVIRONMENT = production
database.default.hostname = mysql
cache.handler = 'redis'
cache.redis.host = redis
session.driver = 'CodeIgniter\Session\Handlers\RedisHandler'
app.forceGlobalSecureRequests = true
```

---

## 📞 Suporte Adicional

Se o erro persistir:

1. **Verifique os logs:** `storage/logs/log-YYYY-MM-DD.log`
2. **Ative debug:** No `.env` adicione `CI_ENVIRONMENT = development`
3. **Limpe cache:** `php spark cache:clear`
4. **Reinstale vendor:** `composer install`

---

**Última Atualização:** 16/Nov/2025
**Status:** ✅ Solução completa documentada
