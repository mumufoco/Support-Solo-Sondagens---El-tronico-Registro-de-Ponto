# 🔒 Guia de Testes de Segurança
## Sistema de Registro de Ponto Eletrônico

**Versão:** 1.0
**Data:** 18/11/2024
**Status:** Todas as 18 vulnerabilidades críticas corrigidas

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Preparação do Ambiente](#preparação-do-ambiente)
3. [Testes de Autenticação](#testes-de-autenticação)
4. [Testes de Autorização](#testes-de-autorização)
5. [Testes de Injeção](#testes-de-injeção)
6. [Testes de XSS](#testes-de-xss)
7. [Testes de CSRF](#testes-de-csrf)
8. [Testes de Upload de Arquivos](#testes-de-upload-de-arquivos)
9. [Testes de Criptografia](#testes-de-criptografia)
10. [Testes de Session Management](#testes-de-session-management)
11. [Testes de APIs](#testes-de-apis)
12. [Testes de Biometria](#testes-de-biometria)
13. [Ferramentas Recomendadas](#ferramentas-recomendadas)
14. [Checklist Final](#checklist-final)

---

## 🎯 Visão Geral

Este guia documenta os procedimentos de teste de segurança para validar as **18 correções críticas** implementadas no sistema de registro de ponto eletrônico.

### Vulnerabilidades Corrigidas

#### CRITICAL (18/18 ✅)
1. ✅ SQL Injection em Relatórios
2. ✅ SQL Injection em Timesheet Queries
3. ✅ Hardcoded Database Credentials
4. ✅ Weak Password Requirements
5. ✅ Biometric Data Storage (Encryption)
6. ✅ Path Traversal em File Access
7. ✅ Insecure Direct Object Reference (IDOR) em Timesheet
8. ✅ Insecure Direct Object Reference (IDOR) em Employee
9. ✅ Insecure Direct Object Reference (IDOR) em Leave Requests
10. ✅ Insecure Direct Object Reference (IDOR) em Reports
11. ✅ Session Fixation
12. ✅ Missing CSRF Protection
13. ✅ Cleartext Transmission of Sensitive Info
14. ✅ Open Redirect
15. ✅ Inadequate Logging
16. ✅ Information Exposure via Error Messages
17. ✅ Remember Me Cookie Security
18. ✅ Race Conditions em Database Operations

#### HIGH (Todos corrigidos ✅)
- ✅ Missing Content Security Policy
- ✅ File Upload MIME Validation
- ✅ Session Security Headers
- ✅ Log Injection
- ✅ Cookie Security Flags

---

## 🔧 Preparação do Ambiente

### Requisitos

```bash
# Software necessário
- PHP 8.4+
- MySQL 8.0+
- CodeIgniter 4
- Composer
- Git

# Ferramentas de teste
- Burp Suite Community/Professional
- OWASP ZAP
- SQLMap
- Postman/Insomnia
- cURL
```

### Configuração do Ambiente de Testes

```bash
# 1. Clone o repositório
git clone [repository-url]
cd Support-Solo-Sondagens---El-tronico-Registro-de-Ponto

# 2. Instale as dependências
composer install

# 3. Configure o ambiente
cp env.example .env

# 4. Configure o banco de dados de testes
# Edite o arquivo .env com credenciais de teste
nano .env

# 5. Execute as migrations
php spark migrate

# 6. Execute os seeders (se disponíveis)
php spark db:seed TestDataSeeder

# 7. Inicie o servidor de desenvolvimento
php spark serve
```

### Dados de Teste

Crie usuários com diferentes níveis de privilégio:

```sql
-- Admin
INSERT INTO employees (name, email, password, role, active)
VALUES ('Admin Test', 'admin@test.com', '[hash_bcrypt]', 'admin', 1);

-- Gestor
INSERT INTO employees (name, email, password, role, active)
VALUES ('Manager Test', 'manager@test.com', '[hash_bcrypt]', 'gestor', 1);

-- Funcionário
INSERT INTO employees (name, email, password, role, active)
VALUES ('Employee Test', 'employee@test.com', '[hash_bcrypt]', 'funcionario', 1);

-- Conta inativa
INSERT INTO employees (name, email, password, role, active)
VALUES ('Inactive Test', 'inactive@test.com', '[hash_bcrypt]', 'funcionario', 0);
```

---

## 🔐 Testes de Autenticação

### 1. Teste de Força de Senha (Fix #4)

**Objetivo:** Validar que senhas fracas são rejeitadas

```bash
# Teste 1: Senha curta (< 12 caracteres)
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@test.com", "password": "Abc@123"}'
# Esperado: Erro - senha muito curta

# Teste 2: Senha sem maiúsculas
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@test.com", "password": "abcdefgh@123"}'
# Esperado: Erro - falta letra maiúscula

# Teste 3: Senha sem caractere especial
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@test.com", "password": "Abcdefgh1234"}'
# Esperado: Erro - falta caractere especial

# Teste 4: Senha válida
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@test.com", "password": "Abc@12345678"}'
# Esperado: Sucesso ou erro de credenciais (mas não erro de formato)
```

**Validação:**
- ✅ Senha com menos de 12 caracteres é rejeitada
- ✅ Senha sem letra maiúscula é rejeitada
- ✅ Senha sem letra minúscula é rejeitada
- ✅ Senha sem número é rejeitada
- ✅ Senha sem caractere especial é rejeitada
- ✅ Mensagem de erro não revela qual requisito falhou (segurança por obscuridade)

### 2. Teste de Brute Force Protection

**Objetivo:** Validar proteção contra tentativas de login em massa

```bash
# Script para testar rate limiting
for i in {1..6}; do
  echo "Tentativa $i"
  curl -X POST http://localhost:8080/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email": "test@test.com", "password": "WrongPassword@123"}' \
    -c cookies.txt
  sleep 1
done
# Esperado: Após 5 tentativas, receber erro de bloqueio
```

**Validação:**
- ✅ Após 5 tentativas falhadas, IP é bloqueado por 15 minutos
- ✅ Bloqueio é por combinação IP+Email (não apenas IP)
- ✅ Tentativas são registradas no audit log
- ✅ Mensagem genérica de erro (não revela se email existe)

### 3. Teste de Remember Me Seguro (Fix #17)

**Objetivo:** Validar implementação segura do "Lembrar-me"

```bash
# Teste 1: Login com remember me
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@test.com", "password": "ValidPassword@123", "remember": "1"}' \
  -c cookies.txt -v

# Verificar que cookie remember_token foi criado com:
# - HttpOnly flag
# - Secure flag (em produção)
# - SameSite=Strict
# - Expiration = 30 dias

# Teste 2: Tentar modificar o cookie
# Edite cookies.txt manualmente e tente acessar página protegida
curl -X GET http://localhost:8080/dashboard \
  -b cookies.txt

# Esperado: Token inválido deve ser rejeitado e deletado
```

**Validação:**
- ✅ Cookie usa formato `selector:verifier`
- ✅ Verifier é hasheado (SHA-256) antes de salvar no DB
- ✅ Comparação usa `hash_equals()` (constant-time)
- ✅ Token inválido é deletado do banco (anti-brute force)
- ✅ Cookie tem flags de segurança corretas
- ✅ Auto-login é registrado no audit log
- ✅ Session regeneration após auto-login

### 4. Teste de Session Fixation (Fix #11)

**Objetivo:** Validar que session ID muda após login

```bash
# Teste 1: Capturar session ID antes do login
curl -X GET http://localhost:8080/auth/login -c session_before.txt -v

# Teste 2: Fazer login
curl -X POST http://localhost:8080/auth/login \
  -b session_before.txt \
  -c session_after.txt \
  -d "email=test@test.com&password=ValidPassword@123"

# Teste 3: Comparar session IDs
# session_before.txt deve ter session ID diferente de session_after.txt
```

**Validação:**
- ✅ Session ID muda após login bem-sucedido
- ✅ Session ID muda após mudança de privilégios
- ✅ Session antiga é invalidada
- ✅ Novo session ID é gerado com `session_regenerate(true)`

---

## 🛡️ Testes de Autorização

### 5. Teste de IDOR - Timesheet (Fix #7)

**Objetivo:** Validar que usuários não podem acessar timesheets de outros

```bash
# Teste 1: Login como funcionário (ID 3)
curl -X POST http://localhost:8080/auth/login \
  -d "email=employee@test.com&password=ValidPassword@123" \
  -c employee_cookies.txt

# Teste 2: Tentar acessar timesheet de outro funcionário (ID 2)
curl -X GET http://localhost:8080/timesheet/view/2 \
  -b employee_cookies.txt

# Esperado: 403 Forbidden ou redirecionamento

# Teste 3: Tentar editar timesheet de outro funcionário
curl -X POST http://localhost:8080/timesheet/update/2 \
  -b employee_cookies.txt \
  -d "hours=8&date=2024-01-01"

# Esperado: 403 Forbidden
```

**Validação:**
- ✅ Funcionário só acessa seus próprios registros
- ✅ Gestor acessa registros de sua equipe
- ✅ Admin acessa todos os registros
- ✅ Tentativas de acesso não autorizado são logadas

### 6. Teste de IDOR - Employees (Fix #8)

**Objetivo:** Validar que funcionários não podem ver/editar outros funcionários

```bash
# Login como funcionário
curl -X POST http://localhost:8080/auth/login \
  -d "email=employee@test.com&password=ValidPassword@123" \
  -c employee_cookies.txt

# Tentar acessar perfil de outro funcionário
curl -X GET http://localhost:8080/employees/view/2 \
  -b employee_cookies.txt

# Esperado: 403 Forbidden

# Tentar editar outro funcionário
curl -X POST http://localhost:8080/employees/update/2 \
  -b employee_cookies.txt \
  -d "name=Hacker&email=hacker@evil.com"

# Esperado: 403 Forbidden
```

**Validação:**
- ✅ Funcionário só vê/edita próprio perfil
- ✅ Gestor vê/edita membros da equipe
- ✅ Admin vê/edita todos os funcionários
- ✅ Verificação de permissão em TODAS as operações (view, edit, delete)

### 7. Teste de IDOR - Leave Requests (Fix #9)

**Objetivo:** Validar autorização em solicitações de férias

```bash
# Login como funcionário
curl -X POST http://localhost:8080/auth/login \
  -d "email=employee@test.com&password=ValidPassword@123" \
  -c employee_cookies.txt

# Tentar aprovar solicitação de outro funcionário
curl -X POST http://localhost:8080/leave-requests/approve/5 \
  -b employee_cookies.txt

# Esperado: 403 Forbidden (apenas gestores/admins aprovam)

# Tentar visualizar solicitação de outro funcionário
curl -X GET http://localhost:8080/leave-requests/view/5 \
  -b employee_cookies.txt

# Esperado: 403 Forbidden
```

**Validação:**
- ✅ Funcionário vê apenas suas próprias solicitações
- ✅ Gestor vê/aprova solicitações da equipe
- ✅ Admin vê/aprova todas as solicitações
- ✅ Aprovação/rejeição requer privilégios corretos

### 8. Teste de IDOR - Reports (Fix #10)

**Objetivo:** Validar que relatórios respeitam hierarquia

```bash
# Login como funcionário
curl -X POST http://localhost:8080/auth/login \
  -d "email=employee@test.com&password=ValidPassword@123" \
  -c employee_cookies.txt

# Tentar gerar relatório de toda a empresa
curl -X GET "http://localhost:8080/reports/generate?type=company&format=pdf" \
  -b employee_cookies.txt

# Esperado: 403 Forbidden ou relatório apenas com dados próprios

# Tentar acessar relatório de outro departamento
curl -X GET "http://localhost:8080/reports/view/123" \
  -b employee_cookies.txt

# Esperado: 403 Forbidden
```

**Validação:**
- ✅ Funcionário vê apenas relatórios pessoais
- ✅ Gestor vê relatórios da equipe/departamento
- ✅ Admin vê relatórios de toda a empresa
- ✅ Filtros de data/departamento respeitam permissões

---

## 💉 Testes de Injeção

### 9. Teste de SQL Injection - Relatórios (Fix #1)

**Objetivo:** Validar que inputs são sanitizados em queries de relatórios

```bash
# Teste 1: SQL Injection via parâmetro de data
curl -X GET "http://localhost:8080/reports/generate?start_date=2024-01-01' OR '1'='1&end_date=2024-12-31" \
  -b admin_cookies.txt

# Esperado: Erro de validação ou relatório vazio (não executa SQL malicioso)

# Teste 2: SQL Injection via parâmetro de departamento
curl -X GET "http://localhost:8080/reports/generate?department=1; DROP TABLE employees;--" \
  -b admin_cookies.txt

# Esperado: Erro de validação

# Teste 3: UNION-based SQL Injection
curl -X GET "http://localhost:8080/reports/generate?employee_id=1 UNION SELECT password FROM employees--" \
  -b admin_cookies.txt

# Esperado: Erro de validação
```

**Ferramentas:** SQLMap

```bash
# Teste automatizado com SQLMap
sqlmap -u "http://localhost:8080/reports/generate?start_date=2024-01-01&end_date=2024-12-31" \
  --cookie="ponto_session=xxxxx" \
  --level=5 \
  --risk=3 \
  --batch

# Esperado: Nenhuma injeção detectada
```

**Validação:**
- ✅ Todas as queries usam prepared statements
- ✅ Inputs de data são validados (formato YYYY-MM-DD)
- ✅ IDs são validados como inteiros
- ✅ Nenhuma concatenação direta de strings em SQL

### 10. Teste de SQL Injection - Timesheet (Fix #2)

**Objetivo:** Validar queries de timesheet contra injeção

```bash
# Teste 1: Injeção via filtro de busca
curl -X GET "http://localhost:8080/timesheet/search?query=test' OR 1=1--" \
  -b employee_cookies.txt

# Esperado: Erro de validação ou busca sem resultados

# Teste 2: Injeção via ID de registro
curl -X POST http://localhost:8080/timesheet/delete \
  -b employee_cookies.txt \
  -d "id=1 OR 1=1"

# Esperado: Erro de validação (ID deve ser inteiro)
```

**Validação:**
- ✅ Prepared statements em todas as queries
- ✅ Validação de tipos de dados
- ✅ Escape de caracteres especiais quando necessário

### 11. Teste de Path Traversal (Fix #6)

**Objetivo:** Validar que acesso a arquivos é controlado

```bash
# Teste 1: Tentar acessar arquivo do sistema
curl -X GET "http://localhost:8080/files/view?path=../../../../etc/passwd" \
  -b employee_cookies.txt

# Esperado: 403 Forbidden ou erro

# Teste 2: Tentar acessar arquivo .env
curl -X GET "http://localhost:8080/files/view?path=../.env" \
  -b employee_cookies.txt

# Esperado: 403 Forbidden

# Teste 3: Tentar acessar arquivo de outro usuário
curl -X GET "http://localhost:8080/files/view?path=../uploads/other_user_file.pdf" \
  -b employee_cookies.txt

# Esperado: 403 Forbidden
```

**Validação:**
- ✅ Paths são normalizados (realpath)
- ✅ Apenas arquivos dentro de diretórios permitidos são acessíveis
- ✅ Caracteres `../` são bloqueados
- ✅ Verificação de permissões por usuário

---

## 🌐 Testes de XSS

### 12. Teste de Cross-Site Scripting

**Objetivo:** Validar que outputs são escapados

```bash
# Teste 1: XSS Refletido em busca
curl -X GET "http://localhost:8080/search?q=<script>alert('XSS')</script>" \
  -b employee_cookies.txt

# Verificar resposta HTML - script deve estar escapado como:
# &lt;script&gt;alert('XSS')&lt;/script&gt;

# Teste 2: XSS Persistente em comentários
curl -X POST http://localhost:8080/timesheet/comment \
  -b employee_cookies.txt \
  -d "comment=<img src=x onerror=alert('XSS')>&timesheet_id=1"

# Verificar que comentário é salvo escapado no banco

# Teste 3: XSS via atributo HTML
curl -X POST http://localhost:8080/profile/update \
  -b employee_cookies.txt \
  -d "name=John\" onload=\"alert('XSS')\""

# Verificar que aspas são escapadas
```

**Ferramentas:** OWASP ZAP

```bash
# Scan automático de XSS
zap-cli quick-scan --spider \
  --ajax-spider \
  --scanners xss \
  http://localhost:8080
```

**Validação:**
- ✅ Todos os outputs usam `esc()` helper do CodeIgniter
- ✅ Content-Type correto (text/html, application/json)
- ✅ CSP headers bloqueiam scripts inline
- ✅ Inputs HTML são sanitizados com HTMLPurifier

---

## 🔐 Testes de CSRF

### 13. Teste de Cross-Site Request Forgery (Fix #12)

**Objetivo:** Validar proteção CSRF em formulários

```bash
# Teste 1: Submeter formulário sem token CSRF
curl -X POST http://localhost:8080/timesheet/create \
  -b employee_cookies.txt \
  -d "date=2024-01-01&hours=8"

# Esperado: 403 Forbidden - Token CSRF ausente

# Teste 2: Submeter com token CSRF inválido
curl -X POST http://localhost:8080/timesheet/create \
  -b employee_cookies.txt \
  -d "date=2024-01-01&hours=8&csrf_token=invalid_token_123"

# Esperado: 403 Forbidden - Token CSRF inválido

# Teste 3: Criar página HTML maliciosa
cat > csrf_attack.html <<EOF
<html>
  <body>
    <form action="http://localhost:8080/timesheet/delete" method="POST">
      <input type="hidden" name="id" value="1" />
      <input type="submit" value="Clique aqui" />
    </form>
    <script>document.forms[0].submit();</script>
  </body>
</html>
EOF

# Abrir em navegador com sessão ativa
# Esperado: Requisição bloqueada por:
# 1. Token CSRF ausente
# 2. SameSite=Strict cookie não enviado
```

**Validação:**
- ✅ Todos os formulários incluem token CSRF
- ✅ Token é validado no servidor
- ✅ Token expira com a sessão
- ✅ SameSite=Strict previne envio cross-origin
- ✅ CORS configurado corretamente

---

## 📁 Testes de Upload de Arquivos

### 14. Teste de File Upload Validation

**Objetivo:** Validar MIME type e extensões de arquivos

```bash
# Teste 1: Upload de arquivo PHP disfarçado de imagem
echo "<?php system(\$_GET['cmd']); ?>" > malicious.php
mv malicious.php malicious.jpg

curl -X POST http://localhost:8080/upload \
  -b employee_cookies.txt \
  -F "file=@malicious.jpg"

# Esperado: Erro - MIME type inválido

# Teste 2: Upload de executável
curl -X POST http://localhost:8080/upload \
  -b employee_cookies.txt \
  -F "file=@malware.exe"

# Esperado: Erro - Extensão não permitida

# Teste 3: Upload de arquivo muito grande
dd if=/dev/zero of=large_file.jpg bs=1M count=100
curl -X POST http://localhost:8080/upload \
  -b employee_cookies.txt \
  -F "file=@large_file.jpg"

# Esperado: Erro - Arquivo excede tamanho máximo
```

**Validação:**
- ✅ Validação de MIME type com `finfo_file()`
- ✅ Validação de extensão whitelist
- ✅ Limite de tamanho (ex: 5MB para imagens)
- ✅ Nome do arquivo é sanitizado
- ✅ Arquivos salvos fora do webroot
- ✅ getimagesize() para imagens

---

## 🔒 Testes de Criptografia

### 15. Teste de Biometric Data Encryption (Fix #5)

**Objetivo:** Validar que dados biométricos são criptografados

```sql
-- Verificar que template_data está criptografado
SELECT id, employee_id,
       LEFT(template_data, 50) as template_preview
FROM biometric_templates
LIMIT 5;

-- Esperado: template_data não deve ser legível (deve estar em base64 ou hex)
-- Não deve conter padrões reconhecíveis de JSON ou arrays
```

**Teste de Descriptografia:**

```php
// Script de teste (executar via php spark shell)
$encryptionKey = env('ENCRYPTION_KEY');
$templateEncrypted = '[valor_do_banco]';

$decrypted = decrypt_biometric_data($templateEncrypted, $encryptionKey);
// Esperado: Array com dados biométricos

$reEncrypted = encrypt_biometric_data($decrypted, $encryptionKey);
// Esperado: Valor diferente do original (devido ao IV randômico)
```

**Validação:**
- ✅ Algoritmo: AES-256-CBC ou superior
- ✅ IV randômico por registro
- ✅ HMAC-SHA256 para integridade
- ✅ Chave nunca em código (apenas .env)
- ✅ Dados em trânsito via HTTPS
- ✅ Logs não expõem dados biométricos

### 16. Teste de Password Hashing

**Objetivo:** Validar que senhas usam bcrypt forte

```sql
-- Verificar formato de hashes de senha
SELECT id, email,
       LEFT(password, 10) as password_preview,
       LENGTH(password) as hash_length
FROM employees
LIMIT 5;

-- Esperado:
-- - Prefixo: $2y$ (bcrypt)
-- - Tamanho: 60 caracteres
```

**Teste de Custo:**

```php
// Verificar custo do bcrypt
$hash = '[hash_do_banco]';
$info = password_get_info($hash);
echo "Algoritmo: " . $info['algoName'] . "\n";
echo "Custo: " . ($info['options']['cost'] ?? 'N/A') . "\n";

// Esperado:
// - Algoritmo: bcrypt
// - Custo: >= 12
```

**Validação:**
- ✅ Algoritmo: bcrypt (password_hash)
- ✅ Custo: >= 12 (recomendado: 12-14)
- ✅ Nunca usar MD5, SHA1, ou hash simples
- ✅ Senhas antigas são rehashadas no próximo login

---

## 🍪 Testes de Session Management

### 17. Teste de Session Security

**Objetivo:** Validar configurações de sessão

```bash
# Teste 1: Verificar flags de cookie de sessão
curl -v http://localhost:8080/auth/login 2>&1 | grep -i "set-cookie"

# Esperado:
# Set-Cookie: pe_ponto_session=xxxxx; path=/; HttpOnly; Secure; SameSite=Strict

# Teste 2: Tentar roubar sessão de outro IP
# 1. Login do IP A
curl -X POST http://localhost:8080/auth/login \
  -d "email=test@test.com&password=ValidPassword@123" \
  -c cookies_ip_a.txt

# 2. Usar cookie no IP B (usar proxy ou VPN)
curl -X GET http://localhost:8080/dashboard \
  -b cookies_ip_a.txt \
  --proxy http://different-ip-proxy:8080

# Esperado: Sessão invalidada (se matchIP=true)
```

**Validação:**
- ✅ HttpOnly flag (JavaScript não acessa)
- ✅ Secure flag em produção (HTTPS only)
- ✅ SameSite=Strict (CSRF protection)
- ✅ Session timeout (2 horas)
- ✅ Session regeneration após login
- ✅ matchIP=true (opção de segurança extra)

### 18. Teste de Session Timeout

**Objetivo:** Validar expiração de sessão inativa

```bash
# 1. Fazer login
curl -X POST http://localhost:8080/auth/login \
  -d "email=test@test.com&password=ValidPassword@123" \
  -c cookies.txt

# 2. Esperar mais de 2 horas (ou alterar sessionExpiration no config)
sleep 7201  # 2 horas + 1 segundo

# 3. Tentar acessar página protegida
curl -X GET http://localhost:8080/dashboard \
  -b cookies.txt

# Esperado: Redirect para /auth/login com mensagem "Sessão expirada"
```

**Validação:**
- ✅ Sessão expira após inatividade configurada
- ✅ Last activity timestamp é atualizado
- ✅ Mensagem clara de timeout
- ✅ Redirect preserva URL pretendida (redirect_url)

---

## 🔗 Testes de Open Redirect (Fix #14)

### 19. Teste de Redirecionamento Seguro

**Objetivo:** Validar que redirecionamentos são seguros

```bash
# Teste 1: Redirecionamento externo via parâmetro
curl -X GET "http://localhost:8080/auth/login?redirect=https://evil.com" \
  -L -v

# Esperado: Redirecionamento bloqueado, vai para /dashboard

# Teste 2: Redirecionamento com protocolo diferente
curl -X GET "http://localhost:8080/auth/login?redirect=javascript:alert('XSS')" \
  -L -v

# Esperado: Redirecionamento bloqueado

# Teste 3: Redirecionamento válido (interno)
curl -X GET "http://localhost:8080/auth/login?redirect=/dashboard/reports" \
  -L -v

# Esperado: Após login, redireciona para /dashboard/reports
```

**Validação:**
- ✅ Apenas URLs internas são permitidas
- ✅ Validação de scheme (http/https)
- ✅ Validação de host (deve ser igual ao base_url)
- ✅ Paths bloqueados: /auth/login, /auth/logout
- ✅ Query parameters suspeitos bloqueados

---

## 📊 Testes de APIs

### 20. Teste de Rate Limiting

**Objetivo:** Validar proteção contra abuso de APIs

```bash
# Script de teste de rate limiting
for i in {1..101}; do
  echo "Request $i"
  curl -X GET http://localhost:8080/api/timesheet \
    -H "Authorization: Bearer [token]"
  sleep 0.1
done

# Esperado: Após 100 requests/minuto, receber 429 Too Many Requests
```

**Validação:**
- ✅ Limite de requests por IP
- ✅ Limite de requests por usuário
- ✅ Headers de rate limit (X-RateLimit-*)
- ✅ Resposta 429 quando excedido
- ✅ Retry-After header presente

### 21. Teste de API Authentication

**Objetivo:** Validar autenticação de APIs

```bash
# Teste 1: Request sem autenticação
curl -X GET http://localhost:8080/api/employees

# Esperado: 401 Unauthorized

# Teste 2: Request com token inválido
curl -X GET http://localhost:8080/api/employees \
  -H "Authorization: Bearer invalid_token_123"

# Esperado: 401 Unauthorized

# Teste 3: Request com token válido
curl -X GET http://localhost:8080/api/employees \
  -H "Authorization: Bearer [valid_token]"

# Esperado: 200 OK com dados
```

**Validação:**
- ✅ Token obrigatório para endpoints protegidos
- ✅ Token expira após período configurado
- ✅ Refresh token para renovação
- ✅ Invalidação de tokens ao logout

---

## 🔍 Testes de Biometria

### 22. Teste de Biometric Verification

**Objetivo:** Validar segurança da verificação biométrica

```bash
# Teste 1: Verificação com template inválido
curl -X POST http://localhost:8080/biometric/verify \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": 1,
    "template": "invalid_data"
  }'

# Esperado: Erro de validação

# Teste 2: Verificação cross-employee
curl -X POST http://localhost:8080/biometric/verify \
  -H "Content-Type: application/json" \
  -b employee_cookies.txt \
  -d '{
    "employee_id": 999,
    "template": "[template_data]"
  }'

# Esperado: 403 Forbidden (apenas admin pode verificar outros)

# Teste 3: Verificação com anti-spoofing
curl -X POST http://localhost:8080/biometric/verify \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": 1,
    "template": "[template_data]",
    "liveness_check": false
  }'

# Esperado: Erro - liveness check obrigatório
```

**Validação:**
- ✅ Templates criptografados em banco
- ✅ HTTPS obrigatório para transmissão
- ✅ Rate limiting em verificações
- ✅ Liveness detection ativo
- ✅ Logs de tentativas de verificação
- ✅ LGPD compliance (consentimento, direito ao esquecimento)

---

## 📝 Testes de Logging e Auditoria

### 23. Teste de Audit Logging (Fix #15)

**Objetivo:** Validar que eventos de segurança são logados

```sql
-- Verificar logs de autenticação
SELECT * FROM audit_logs
WHERE action IN ('LOGIN', 'LOGOUT', 'LOGIN_FAILED', 'AUTO_LOGIN_REMEMBER_ME')
ORDER BY created_at DESC
LIMIT 20;

-- Verificar logs de alterações sensíveis
SELECT * FROM audit_logs
WHERE table_name = 'employees'
  AND action IN ('UPDATE', 'DELETE')
ORDER BY created_at DESC
LIMIT 20;

-- Verificar logs de acesso negado
SELECT * FROM audit_logs
WHERE severity = 'warning'
  AND action LIKE '%DENIED%'
ORDER BY created_at DESC;
```

**Validação:**
- ✅ Login bem-sucedido é logado
- ✅ Login falhado é logado (sem revelar se email existe)
- ✅ Logout é logado
- ✅ Mudanças em dados sensíveis são logadas
- ✅ Tentativas de acesso não autorizado são logadas
- ✅ IPs e User Agents são capturados
- ✅ Dados sensíveis são sanitizados nos logs

### 24. Teste de Log Injection (Fix HIGH)

**Objetivo:** Validar que logs não contêm injeções

```bash
# Teste 1: Tentar injetar quebra de linha no log
curl -X POST http://localhost:8080/auth/login \
  -d "email=attacker@test.com%0A[ERROR] FAKE LOG ENTRY&password=Test@123456"

# Verificar logs - não deve ter quebra de linha literal

# Teste 2: Tentar injetar ANSI escape codes
curl -X POST http://localhost:8080/auth/login \
  -d "email=test@test.com\e[31mRED_TEXT\e[0m&password=Test@123456"

# Verificar logs - escape codes devem ser sanitizados
```

**Validação:**
- ✅ Função `sanitize_for_log()` remove \n, \r, \0
- ✅ ANSI escape codes são removidos
- ✅ Dados sensíveis são redatados
- ✅ Formato consistente de logs

---

## 🔐 Testes de Information Disclosure

### 25. Teste de Error Messages (Fix #16)

**Objetivo:** Validar que erros não expõem informações sensíveis

```bash
# Teste 1: Erro de login
curl -X POST http://localhost:8080/auth/login \
  -d "email=nonexistent@test.com&password=Test@123456"

# Esperado: "E-mail ou senha inválidos" (genérico)
# NÃO esperado: "E-mail não encontrado" (revela informação)

# Teste 2: Erro de validação
curl -X POST http://localhost:8080/timesheet/create \
  -b employee_cookies.txt \
  -d "date=invalid&hours=abc"

# Esperado: Mensagens de validação sem detalhes técnicos

# Teste 3: Erro 500 em produção
# Forçar erro (ex: divisão por zero em código)
curl -X GET http://localhost:8080/broken-endpoint

# Esperado em PRODUCTION:
# - Mensagem genérica "Erro interno do servidor"
# - SEM stack trace
# - SEM caminhos de arquivos
# - SEM consultas SQL
```

**Verificar Headers:**

```bash
curl -v http://localhost:8080/

# NÃO deve expor:
# - Server: Apache/2.4.41 (Ubuntu)
# - X-Powered-By: PHP/8.4.14
# - X-Debug-Token: xxxxx
```

**Validação:**
- ✅ Mensagens de erro genéricas em produção
- ✅ Stack traces desabilitados em produção
- ✅ Dados sensíveis em `sensitiveDataInTrace`
- ✅ Server headers removidos
- ✅ Debug mode OFF em produção

---

## 🔧 Ferramentas Recomendadas

### Scanners de Vulnerabilidades

1. **OWASP ZAP** (Gratuito)
   ```bash
   # Instalação
   wget https://github.com/zaproxy/zaproxy/releases/download/v2.14.0/ZAP_2_14_0_unix.sh
   sh ZAP_2_14_0_unix.sh

   # Scan básico
   zap.sh -cmd -quickurl http://localhost:8080 -quickout report.html
   ```

2. **Burp Suite Community** (Gratuito)
   - Download: https://portswigger.net/burp/communitydownload
   - Útil para interceptar e modificar requests

3. **SQLMap** (Gratuito)
   ```bash
   # Instalação
   sudo apt install sqlmap

   # Teste de SQL injection
   sqlmap -u "http://localhost:8080/reports?id=1" --cookie="session=xxx"
   ```

4. **Nikto** (Gratuito)
   ```bash
   # Instalação
   sudo apt install nikto

   # Scan de vulnerabilidades web
   nikto -h http://localhost:8080
   ```

### Ferramentas de Análise Estática

1. **PHPStan** (Análise estática de código PHP)
   ```bash
   composer require --dev phpstan/phpstan
   vendor/bin/phpstan analyse app
   ```

2. **PHP_CodeSniffer** (Padrões de código)
   ```bash
   composer require --dev squizlabs/php_codesniffer
   vendor/bin/phpcs --standard=PSR12 app
   ```

3. **RIPS** (Análise de segurança PHP)
   - https://www.ripstech.com/

### Ferramentas de Monitoramento

1. **Fail2Ban** (Proteção contra brute force)
   ```bash
   sudo apt install fail2ban
   # Configurar filtros para logs de aplicação
   ```

2. **ELK Stack** (Logs centralizados)
   - Elasticsearch + Logstash + Kibana
   - Para análise avançada de logs

3. **Sentry** (Tracking de erros)
   - https://sentry.io/
   - Integração com CodeIgniter

---

## ✅ Checklist Final

### Autenticação e Autorização
- [ ] Senhas fortes obrigatórias (12+ chars, maiúscula, minúscula, número, especial)
- [ ] Brute force protection (5 tentativas, 15 min bloqueio)
- [ ] Remember Me seguro (selector/verifier, hash, constant-time)
- [ ] Session fixation prevenido (regeneration após login)
- [ ] Session timeout configurado (2 horas)
- [ ] IDOR prevenido em todos os endpoints
- [ ] Role-based access control funciona corretamente

### Injeções e XSS
- [ ] SQL Injection bloqueado (prepared statements)
- [ ] XSS bloqueado (output escaping, CSP)
- [ ] Path traversal bloqueado (validação de paths)
- [ ] Log injection bloqueado (sanitização)
- [ ] CSRF protection ativo (tokens, SameSite)

### Criptografia e Dados Sensíveis
- [ ] Senhas hasheadas com bcrypt (cost >= 12)
- [ ] Dados biométricos criptografados (AES-256-CBC)
- [ ] HTTPS obrigatório em produção
- [ ] Credenciais em .env (não hardcoded)
- [ ] Chaves de criptografia fortes e únicas

### File Upload
- [ ] MIME type validado (finfo_file)
- [ ] Extensão whitelist aplicada
- [ ] Tamanho de arquivo limitado
- [ ] Nome de arquivo sanitizado
- [ ] Arquivos salvos fora do webroot

### Headers e Cookies
- [ ] CSP headers configurados
- [ ] HSTS habilitado
- [ ] X-Frame-Options: DENY
- [ ] X-Content-Type-Options: nosniff
- [ ] Cookie flags (HttpOnly, Secure, SameSite)
- [ ] Server/X-Powered-By headers removidos

### Logging e Monitoramento
- [ ] Audit logging de eventos de segurança
- [ ] Logs não expõem dados sensíveis
- [ ] Erros logados mas não exibidos em produção
- [ ] Tentativas de ataque são detectadas e logadas

### Compliance
- [ ] LGPD compliance (dados biométricos)
- [ ] Consentimento para coleta de dados
- [ ] Direito ao esquecimento implementado
- [ ] Política de privacidade disponível

### Race Conditions
- [ ] Table locking em operações críticas
- [ ] Transações atômicas para updates relacionados
- [ ] Verificação de estado antes de updates

### Configurações
- [ ] Debug mode OFF em produção
- [ ] Error reporting configurado corretamente
- [ ] Permissões de arquivos corretas (644/755)
- [ ] Banco de dados com usuário limitado
- [ ] Backup automático configurado

---

## 🚀 Próximos Passos

1. **Testes de Penetração Profissional**
   - Contratar consultoria especializada
   - Teste de caixa preta e caixa branca

2. **Bug Bounty Program**
   - Considerar programa de recompensas para pesquisadores
   - Plataformas: HackerOne, Bugcrowd

3. **Treinamento de Equipe**
   - OWASP Top 10 training
   - Secure coding practices
   - Code review de segurança

4. **Monitoramento Contínuo**
   - WAF (Web Application Firewall)
   - SIEM (Security Information and Event Management)
   - Alertas automatizados

5. **Atualizações Regulares**
   - Manter PHP, CodeIgniter e dependências atualizadas
   - Monitorar CVEs de tecnologias usadas
   - Patch management process

---

## 📞 Contato e Suporte

**Em caso de vulnerabilidade encontrada:**
1. NÃO divulgar publicamente
2. Reportar para: security@[empresa].com
3. Aguardar confirmação e prazo para correção
4. Divulgação responsável após correção

**Recursos Adicionais:**
- OWASP Testing Guide: https://owasp.org/www-project-web-security-testing-guide/
- OWASP Cheat Sheets: https://cheatsheetseries.owasp.org/
- CWE Top 25: https://cwe.mitre.org/top25/

---

**Última Atualização:** 18/11/2024
**Versão do Sistema:** 2.0 (Pós-Auditoria de Segurança)
**Status:** ✅ Todas as 18 vulnerabilidades críticas corrigidas
