# 🔄 FIX: Loop de Redirect por Problema de Sessão

## 🔍 DIAGNÓSTICO RECEBIDO

```
Request URI:     /public/test-redirect-debug.php
HTTP Host:       ponto.supportsondagens.com.br
HTTPS:           YES ✅
Server:          Apache
Session Status:  NOT STARTED ❌  ← PROBLEMA!
Session Path:    /opt/alt/php84/var/lib/php/session
Directories:     ALL WRITABLE ✅
```

### Problema Identificado:
**Sessão não está iniciando** → Sistema não consegue verificar autenticação → Loop infinito de redirects

---

## ✅ CORREÇÃO APLICADA

### 1. Atualizado `.env`
```ini
# ANTES:
app.baseURL = ''
CI_ENVIRONMENT = development

# DEPOIS:
app.baseURL = 'https://ponto.supportsondagens.com.br/'
CI_ENVIRONMENT = production
session.cookieDomain = '.supportsondagens.com.br'
session.cookieSecure = true
session.cookieSameSite = 'Lax'
```

### 2. Criado `public/.user.ini`
Configuração PHP específica para produção:
- Session save path para writable/session
- Cookie secure habilitado
- Error logging configurado
- Performance otimizada

### 3. Script de Correção
Criado `fix-session-redirect-loop.sh` para:
- ✅ Ajustar permissões
- ✅ Limpar sessões antigas
- ✅ Testar inicialização
- ✅ Limpar cache

---

## 🚀 COMO APLICAR A CORREÇÃO

### Método 1: Script Automático (Recomendado)
```bash
./fix-session-redirect-loop.sh
```

### Método 2: Manual

#### Passo 1: Permissões
```bash
chmod 775 writable/session/
rm -f writable/session/ci_session*
chmod 775 writable/cache/
chmod 775 writable/logs/
```

#### Passo 2: Limpar Cache
```bash
php spark cache:clear
# OU manualmente:
rm -rf writable/cache/data/*
```

#### Passo 3: Testar
```bash
# Acesse:
https://ponto.supportsondagens.com.br

# Se ainda houver problema, veja:
https://ponto.supportsondagens.com.br/public/test-redirect-debug.php
```

---

## 🔍 VERIFICAÇÃO PÓS-CORREÇÃO

### Teste 1: Diagnóstico
```bash
# Acesse novamente:
https://ponto.supportsondagens.com.br/public/test-redirect-debug.php
```

**Deve mostrar:**
```
Session Status: STARTED ✅
Can Start Session: YES ✅
```

### Teste 2: Página Principal
```bash
# Acesse:
https://ponto.supportsondagens.com.br
```

**Resultado esperado:**
- ✅ Redireciona para /auth/login
- ✅ Mostra formulário de login
- ❌ NÃO fica em loop infinito

### Teste 3: Login
```bash
# Tente fazer login com usuário criado
```

**Resultado esperado:**
- ✅ Aceita credenciais
- ✅ Redireciona para dashboard
- ✅ Sessão persiste

---

## 🆘 SE O PROBLEMA PERSISTIR

### Verificação 1: PHP Version
```bash
php -v
```
**Requerido:** PHP 8.1 ou superior

### Verificação 2: Session Save Path
```bash
php -i | grep "session.save_path"
```

**Deve apontar para:**
- `writable/session` (preferido)
- OU um diretório gravável pelo usuário

### Verificação 3: open_basedir
```bash
php -i | grep "open_basedir"
```

**Se houver restrição:**
- Precisa incluir o diretório `writable/session`
- Configure no cPanel → PHP Selector → Options

### Verificação 4: Logs do Apache
```bash
tail -f ~/logs/error_log
# OU
tail -f /var/log/apache2/error.log
```

Procure por:
- `session_start(): Failed`
- `Permission denied`
- `open_basedir restriction`

---

## 🔧 SOLUÇÕES ALTERNATIVAS

### Opção A: Usar Session do Sistema PHP
Se writable/session não funciona, use session path do sistema:

**Editar `.env`:**
```ini
session.savePath = '/opt/alt/php84/var/lib/php/session'
```

**Atenção:** Requer permissão de escrita nesse diretório

### Opção B: Usar Database Sessions
Mais confiável em ambiente compartilhado:

**Editar `.env`:**
```ini
session.driver = 'CodeIgniter\Session\Handlers\DatabaseHandler'
session.savePath = 'ci_sessions'
```

**Executar migration:**
```bash
php spark migrate:create CreateSessionsTable
```

**Migration (app/Database/Migrations/YYYY_MM_DD_CreateSessionsTable.php):**
```php
public function up()
{
    $this->forge->addField([
        'id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
        'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => false],
        'timestamp timestamp' => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        'data' => ['type' => 'BLOB', 'null' => false],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addKey('timestamp');
    $this->forge->createTable('ci_sessions', true);
}
```

```bash
php spark migrate
```

### Opção C: Contatar Suporte da Hospedagem
Se nada funcionar:

**Solicitar:**
1. Verificar permissões em `~/public_html/ponto.supportsondagens.com.br/writable/session`
2. Verificar open_basedir restrictions
3. Verificar se session.save_handler está configurado como 'files'
4. Logs de erro do PHP

---

## 📊 CHECKLIST DE CORREÇÃO

Após executar a correção, marque:

- [ ] ✅ `.env` atualizado com baseURL correto
- [ ] ✅ `CI_ENVIRONMENT = production`
- [ ] ✅ `session.cookieSecure = true`
- [ ] ✅ `public/.user.ini` criado
- [ ] ✅ Permissões ajustadas (775 em writable/)
- [ ] ✅ Sessões antigas removidas
- [ ] ✅ Cache limpo
- [ ] ✅ Teste de sessão funcionando
- [ ] ✅ Página principal carrega sem loop
- [ ] ✅ Login funciona
- [ ] ✅ Dashboard carrega

---

## 📝 ARQUIVOS MODIFICADOS

```
Modificados:
├─ .env                           ← Configuração de produção
├─ public/.user.ini               ← Config PHP para sessão
└─ (permissões em writable/)

Criados:
├─ fix-session-redirect-loop.sh   ← Script de correção
└─ FIX_REDIRECT_LOOP_SESSION.md   ← Este documento
```

---

## 🎯 CAUSA RAIZ

O problema ocorre porque:

1. **CodeIgniter precisa de sessão** para verificar autenticação
2. **Sessão não inicia** (configuração incorreta)
3. **Sistema redireciona** para login (usuário não autenticado)
4. **Loop:** Ao tentar carregar login, verifica autenticação → sessão falha → redireciona → loop infinito

**Solução:** Garantir que a sessão inicie corretamente

---

## 📞 SUPORTE

**Se precisar de mais ajuda:**

1. Execute o diagnóstico:
   ```bash
   https://ponto.supportsondagens.com.br/public/test-redirect-debug.php
   ```

2. Verifique os logs:
   ```bash
   tail -f writable/logs/log-$(date +%Y-%m-%d).php
   ```

3. Execute o script de correção:
   ```bash
   ./fix-session-redirect-loop.sh
   ```

4. Se nada funcionar, consulte:
   - `DIAGNOSTICO_ERRO_500.md` - Diagnóstico completo
   - `README_MYSQL.md` - Problemas de banco de dados
   - Suporte da hospedagem

---

**Data:** 2025-11-16
**Versão:** 1.0
**Sistema:** Ponto Eletrônico Brasileiro
