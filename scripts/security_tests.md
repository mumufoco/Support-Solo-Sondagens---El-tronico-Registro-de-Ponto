# Testes de Segurança - OWASP Top 10

## Ferramentas Necessárias

```bash
# OWASP ZAP (Docker)
docker pull owasp/zap2docker-stable

# SQLMap
pip install sqlmap

# Nikto
apt-get install nikto
```

## 1. OWASP ZAP Baseline Scan

```bash
# Scan básico de vulnerabilidades
docker run -t owasp/zap2docker-stable zap-baseline.py \
    -t http://localhost:8080 \
    -r zap_report.html

# Analisa: XSS, SQLi, CSRF, Headers de Segurança
```

**Verificações:**
- ✅ HTTPS habilitado em produção
- ✅ Headers de segurança (CSP, HSTS, X-Frame-Options)
- ✅ Proteção contra XSS (escapar output)
- ✅ Proteção contra SQL Injection (prepared statements)
- ✅ CSRF tokens em formulários

## 2. SQL Injection Tests (SQLMap)

```bash
# Testar endpoint de busca
sqlmap -u "http://localhost:8080/api/employees/search?q=test" \
    --level=3 \
    --risk=2 \
    --batch

# Deve retornar: 0 vulnerabilidades detectadas
```

**Validações:**
- ✅ Uso de PDO/MySQLi com prepared statements
- ✅ Validação de inputs
- ✅ Escapar queries quando necessário

## 3. Nikto Web Server Scan

```bash
nikto -h http://localhost:8080 -C all -output nikto_report.html
```

**Verificações:**
- ✅ Sem informações sensíveis em headers
- ✅ Diretórios sensíveis protegidos (.git, .env, vendor)
- ✅ Versão do servidor não exposta
- ✅ SSL/TLS configurado corretamente

## 4. Testes Manuais de Segurança

### 4.1. CSRF Token
```bash
# Tentar submeter form sem token CSRF
curl -X POST http://localhost:8080/api/punch \
    -H "Authorization: Bearer TOKEN" \
    -d '{"employee_id": 1}' \
    # Deve retornar 403 Forbidden
```

### 4.2. Rate Limiting
```bash
# Fazer 101 requests em 1 minuto
for i in {1..101}; do
    curl http://localhost:8080/api/recognize \
        -H "Content-Type: application/json" \
        -d '{"image": "..."}' &
done

# 101ª request deve retornar 429 Too Many Requests
```

### 4.3. Acesso Não Autorizado
```bash
# Tentar acessar /admin sem login
curl http://localhost:8080/admin

# Deve retornar 302 Redirect para /login ou 401 Unauthorized
```

### 4.4. Validação de JWT
```bash
# Token inválido
curl http://localhost:8080/api/employees \
    -H "Authorization: Bearer invalid.token.here"

# Deve retornar 401 Unauthorized
```

### 4.5. Path Traversal
```bash
# Tentar acessar arquivo fora do permitido
curl "http://localhost:8080/uploads/../../.env"

# Deve retornar 403 ou 404
```

### 4.6. File Upload Security
```bash
# Tentar fazer upload de PHP malicioso
curl -X POST http://localhost:8080/api/justifications \
    -F "file=@malicious.php" \
    -H "Authorization: Bearer TOKEN"

# Deve rejeitar: apenas jpg, png, pdf permitidos
```

## 5. Checklist de Segurança

### Autenticação
- ✅ Hash Argon2id para senhas
- ✅ Proteção brute force (5 tentativas)
- ✅ Sessões com regeneração de ID
- ✅ Tokens JWT assinados

### Autorização
- ✅ Verificar permissões em cada endpoint
- ✅ Usuário só acessa seus próprios dados
- ✅ Gestor acessa apenas sua equipe
- ✅ Admin tem acesso total

### Dados Sensíveis
- ✅ Dados biométricos criptografados (AES-256)
- ✅ Senhas nunca em plaintext
- ✅ Logs não contêm informações sensíveis
- ✅ .env não versionado

### Comunicação
- ✅ HTTPS obrigatório em produção
- ✅ Cookies com flags Secure e HttpOnly
- ✅ Headers de segurança configurados

### LGPD/GDPR
- ✅ Consentimento explícito para biometria
- ✅ Exportação de dados implementada
- ✅ Direito ao esquecimento (delete/anonimizar)
- ✅ Logs de auditoria por 10 anos

## 6. Relatório Final

```
Target: 0 vulnerabilidades críticas ou altas
       Corrigir todas médias
       Documentar baixas como "risco aceito"
```

### Exemplo de Relatório

```markdown
## Resultados dos Testes de Segurança

**Data:** 2025-01-15
**Versão:** 2.0.0

### OWASP ZAP
- Críticas: 0
- Altas: 0
- Médias: 2 (headers CSP não configurados - CORRIGIDO)
- Baixas: 5 (aceitável)

### SQLMap
- Vulnerabilidades SQL: 0 ✅

### Nikto
- Issues encontrados: 3 (informativos apenas)

### Testes Manuais
- CSRF Protection: ✅ PASS
- Rate Limiting: ✅ PASS (bloqueio em 100 req/min)
- Autorização: ✅ PASS
- JWT Validation: ✅ PASS
- Path Traversal: ✅ PASS
- File Upload: ✅ PASS

**Status:** APROVADO PARA PRODUÇÃO ✅
```

## 7. Monitoramento Contínuo

```bash
# Executar scans semanalmente em staging
0 2 * * 1 /usr/local/bin/security_scan.sh
```

Ferramentas adicionais:
- **Snyk** - Scan de dependências
- **SonarQube** - Análise de código
- **Dependabot** - Atualizar vulnerabilidades
