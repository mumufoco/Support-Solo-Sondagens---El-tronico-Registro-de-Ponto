# 🔧 DIAGNÓSTICO E CORREÇÕES - ERRO 500

## 🚨 EXECUÇÃO RÁPIDA

Execute no servidor de produção via SSH:

```bash
cd /home/supportson/public_html/ponto

# Opção 1: Pull das correções
git pull origin claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx

# Opção 2: Correção crítica automática
chmod +x fix-critical-errors.sh
./fix-critical-errors.sh

# Opção 3: Corrigir problema de SSL/Cookie
chmod +x fix-ssl-cookie.sh
./fix-ssl-cookie.sh
```

## 📋 PRIORIDADE CRÍTICA

### ✅ 1. Permissões dos Diretórios

**Problema:** `writable/session` não tem permissão de escrita

**Solução:**
```bash
chmod -R 775 writable/
chown -R www-data:www-data writable/
```

**Script:** `./fix-critical-errors.sh` faz isso automaticamente

---

### ✅ 2. Configuração de Session.cookie_secure

**Problema:** SSL não configurado mas `session.cookie_secure = true`

**Soluções:**

**Opção A - Instalar SSL (RECOMENDADO):**
1. Acesse cPanel
2. SSL/TLS Status → AutoSSL ou Let's Encrypt
3. Ative para `ponto.supportsondagens.com.br`
4. Aguarde 5-10 minutos

**Opção B - Desabilitar temporariamente (NÃO SEGURO):**
```bash
./fix-ssl-cookie.sh
# Escolha opção 2
```

---

### ✅ 3. Banco de Dados

**Testar conexão:**
```bash
mysql -h localhost -u supportson_support -p'Mumufoco@1990' supportson_suppPONTO
```

Se falhar:
- Verifique credenciais no `.env`
- Confirme que banco existe
- Verifique se MySQL está rodando: `systemctl status mysql`

---

### ✅ 4. Versão do PHP

**Requisito:** PHP >= 8.1

**Verificar:**
```bash
php -v
```

**Se < 8.1:**
- Via cPanel: MultiPHP Manager → Selecionar PHP 8.1+
- Ou contate o host

---

## 🔧 PRIORIDADE ALTA

### 5. Regenerar Autoloader

```bash
composer install --no-dev --optimize-autoloader
```

### 6. Verificar Logs

```bash
# Logs do sistema
tail -f writable/logs/log-$(date +%Y-%m-%d).log

# Logs de PHP
tail -f writable/logs/php-errors.log
```

### 7. Diagnóstico via Navegador

Acesse:
```
https://ponto.supportsondagens.com.br/fix-session-error.php
```

Este script:
- Cria diretórios faltando
- Ajusta permissões
- Corrige `.env`
- Cria `php-config-production.php`
- Testa criação de sessão

**IMPORTANTE:** Delete o arquivo após uso!

---

## 📊 RESUMO DE FALHAS

| # | Falha | Gravidade | Solução |
|---|-------|-----------|---------|
| 1 | Permissões `writable/` | 🔴 CRÍTICA | `./fix-critical-errors.sh` |
| 2 | `session.cookie_secure` sem SSL | 🔴 CRÍTICA | `./fix-ssl-cookie.sh` |
| 3 | Banco inacessível | 🔴 CRÍTICA | Verificar credenciais |
| 4 | PHP < 8.1 | 🔴 CRÍTICA | Atualizar via cPanel |
| 5 | Autoloader não gerado | 🟡 ALTA | `composer install` |
| 6 | `.env` incorreto | 🟡 ALTA | Verificar manualmente |
| 7 | `Paths.php` errado | 🟡 ALTA | Já corrigido no git |

---

## 🎯 CHECKLIST DE VERIFICAÇÃO

Antes de testar o sistema:

- [ ] `writable/session` existe e tem permissão 775
- [ ] `.env` tem `session.savePath = writable/session`
- [ ] `public/php-config-production.php` existe
- [ ] `app/Config/Paths.php` usa `writable` (não `storage`)
- [ ] `vendor/autoload.php` existe
- [ ] Banco de dados conecta
- [ ] PHP >= 8.1
- [ ] SSL instalado OU `cookie_secure = false` temporariamente

Depois de verificar tudo:

- [ ] Acesse: `https://ponto.supportsondagens.com.br/auth/login`
- [ ] Login funciona
- [ ] Sessão persiste após login

---

## 🆘 SE O ERRO PERSISTIR

### 1. Colete informações:

```bash
# Info do sistema
php -v
php -m | grep -E "intl|mbstring|json|mysqli"

# Logs recentes
tail -100 writable/logs/log-*.log > debug.txt

# Permissões
ls -la writable/

# Teste de sessão
php -r "echo ini_get('session.save_path');"
```

### 2. Verifique cada item:

**A. Arquivo `.env` correto?**
```bash
grep "session\.savePath\|cookieSecure\|baseURL" .env
```

Deve mostrar:
```
app.baseURL = 'https://ponto.supportsondagens.com.br/'
session.savePath = writable/session
session.cookieSecure = true    # OU false se sem SSL
```

**B. `php-config-production.php` existe?**
```bash
cat public/php-config-production.php | grep session.save_path
```

**C. `index.php` carrega o config?**
```bash
grep "php-config-production" public/index.php
```

**D. Paths.php correto?**
```bash
grep "writableDirectory" app/Config/Paths.php
```

Deve mostrar: `writable` (NÃO `storage`)

---

## 📞 SUPORTE

Se após todas as correções o erro persistir, envie:

1. Output de `./fix-critical-errors.sh`
2. Conteúdo de `debug.txt`
3. Screenshot do erro 500
4. Resultado de `php -v` e `php -m`

---

## 🔒 SEGURANÇA PÓS-CORREÇÃO

Após sistema funcionar:

1. **Instalar SSL** (se ainda não tem)
2. **Deletar arquivos de diagnóstico:**
   ```bash
   rm public/fix-session-error.php
   rm public/test-session-installer.php
   rm public/test-db-connection.php
   ```
3. **Proteger .env:**
   ```bash
   chmod 600 .env
   ```
4. **Configurar backups automáticos**

---

**Última atualização:** 2025-11-17
**Sistema:** Ponto Eletrônico - CodeIgniter 4.6.3
