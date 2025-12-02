# ANÁLISE COMPLETA DO LOOP DE LOGIN - TODAS AS LIGAÇÕES

## 🔴 PROBLEMAS CRÍTICOS ENCONTRADOS

### **1. CONFLITO DE NOME DE COOKIE DE SESSÃO**
**CRÍTICO** - Este é provavelmente o problema principal!

**Localização:**
- `app/Config/App.php:106` → `$sessionCookieName = 'ponto_session'`
- `app/Config/Session.php:38` → `$cookieName = 'ci_session'`

**Problema:**
Os dois arquivos definem nomes DIFERENTES para o cookie de sessão. CodeIgniter 4.5+ usa APENAS `Config/Session.php`, mas pode haver código legado tentando usar `App.php`.

**Impacto:**
- Navegador pode receber/enviar cookie com nome errado
- Sessão criada no LoginController pode não ser lida no AdminFilter
- Loop infinito porque sessão "desaparece" entre requests

---

### **2. CONFIGURAÇÕES DE SESSÃO DUPLICADAS**
**CRÍTICO** - Configurações conflitantes entre arquivos

**Em App.php (linhas 105-111):**
```php
public string $sessionDriver            = 'App\Session\Handlers\SafeFileHandler';
public string $sessionCookieName        = 'ponto_session';
public int    $sessionExpiration        = 7200;
public string $sessionSavePath          = WRITEPATH . 'session';
public bool   $sessionMatchIP           = false;
public int    $sessionTimeToUpdate      = 300;
public bool   $sessionRegenerateDestroy = false;
```

**Em Session.php (linhas 29-118):**
```php
public string $driver = SafeFileHandler::class;
public string $cookieName = 'ci_session';
public int $expiration = 7200;
public string $savePath = '';  // Set in constructor
public bool $matchIP = false;
public int $timeToUpdate = 300;
public bool $regenerateDestroy = false;
```

**Problema:**
CodeIgniter 4.5+ mudou para usar APENAS `Config/Session.php`. Ter configurações em `App.php` pode causar conflitos ou comportamento inesperado.

---

### **3. ROTA COM FILTROS MÚLTIPLOS**
**Moderado** - Pode estar causando verificações duplas

**Localização:** `app/Config/Routes.php:44`
```php
$routes->group('dashboard', ['filter' => 'auth'], static function ($routes) {
    $routes->get('admin', 'Dashboard\DashboardController::admin', ['filter' => 'admin']);
});
```

**Problema:**
A rota `/dashboard/admin` passa por:
1. **AuthFilter** (do grupo dashboard)
2. **AdminFilter** (específico da rota)

Se o AuthFilter falhar primeiro e redirecionar, o AdminFilter nunca é alcançado. Mas o loop mostra que ambos estão redirecionando.

**Fluxo atual:**
```
Login → Redirect /dashboard/admin
   ↓
AuthFilter verifica user_id → NÃO encontra
   ↓
Redirect /auth/login → LOOP!
```

---

### **4. COOKIE DOMAIN E PATH**
**Moderado** - Pode causar problemas em produção

**Localização:** `app/Config/App.php:120-121`
```php
public string $cookieDomain  = '';  // Vazio!
public string $cookiePath    = '/';
```

**Problema:**
Com `cookieDomain` vazio, o navegador usa o domínio exato da requisição. Em produção (`ponto.supportsondagens.com.br`), se houver redirects ou subdomínios, o cookie pode não ser enviado.

---

### **5. REGENERATE() ANTES DE SET()**
**Resolvido na tentativa anterior, mas pode precisar revisão**

**Localização:** `app/Controllers/Auth/LoginController.php:135-150`

O código atual faz:
```php
$this->session->regenerate();  // Linha 135
$this->session->set($sessionData);  // Linha 150
```

Isso está CORRETO, mas em versões antigas estava invertido.

---

## 📊 FLUXO COMPLETO DO PROBLEMA

```
┌─────────────────────────────────────────────────────────────┐
│ 1. USER SUBMITS LOGIN                                        │
│    POST /auth/login                                          │
│    email=admin@test.com, password=****                       │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. LoginController::authenticate()                           │
│    - Validates credentials ✓                                │
│    - Calls $this->session->regenerate()                     │
│    - Calls $this->session->set($sessionData)                │
│    - Session ID: abc123 (example)                           │
│    - Cookie set: ponto_session=abc123 (ou ci_session?)     │
│    - Returns redirect()->to('/dashboard/admin')             │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. BROWSER REDIRECT                                          │
│    GET /dashboard/admin                                      │
│    Cookie sent: ci_session=xyz789 (DIFERENTE!)             │
│    ❌ PROBLEMA: Cookie name mismatch?                       │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. AuthFilter::before()                                      │
│    - Calls session()                                         │
│    - Session ID: xyz789 (OLD SESSION!)                      │
│    - Checks $session->get('user_id')                        │
│    - Result: NULL (wrong session!)                          │
│    - Returns redirect()->to('/auth/login')                  │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. BROWSER REDIRECT TO LOGIN                                 │
│    GET /auth/login                                           │
│    - RateLimitFilter increments attempts                     │
│    - Attempt #45 (after many loops!)                        │
│    - Rate limit exceeded → LOGIN BLOCKED                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔍 HIPÓTESES SOBRE A CAUSA

### **Hipótese Principal: Cookie Name Mismatch**

1. LoginController usa configuração de `Session.php` → cookie: `ci_session`
2. Session criada com `session_id = abc123`
3. Cookie enviado ao navegador: `ci_session=abc123`
4. Navegador faz redirect GET /dashboard/admin
5. Navegador envia cookies existentes
6. SafeFileHandler tenta ler sessão, mas:
   - Espera cookie chamado `ponto_session` (de App.php)
   - Recebe cookie chamado `ci_session`
   - Não encontra, cria nova sessão vazia
7. AuthFilter não encontra `user_id` na sessão nova/vazia
8. Redirect para `/auth/login` → **LOOP**

### **Hipótese Secundária: SafeFileHandler Write Failure**

1. SafeFileHandler pode não estar escrevendo corretamente
2. Sessão criada em memória mas não persiste no disco
3. Próximo request lê arquivo vazio ou não existente
4. Session data lost → **LOOP**

---

## ✅ SOLUÇÕES A IMPLEMENTAR

### **Solução 1: Padronizar Nome do Cookie (CRÍTICO)**

**Ação:** Remover configurações de sessão de `App.php` e garantir que apenas `Session.php` seja usado.

**Arquivos a modificar:**
- `app/Config/App.php` → Remover linhas 105-111
- Verificar que `Session.php` está configurado corretamente

### **Solução 2: Adicionar Logs Detalhados (IMPLEMENTADO)**

**Ação:** Adicionar logs em todos os pontos críticos para rastrear:
- Session ID em cada etapa
- Cookie name sendo usado
- Conteúdo completo da sessão
- Cookies recebidos pelo servidor

**Arquivos modificados:**
- `app/Controllers/Auth/LoginController.php` ✓
- `app/Filters/AuthFilter.php` ✓
- `app/Filters/AdminFilter.php` ✓

### **Solução 3: Definir Cookie Domain Explicitamente**

**Ação:** Configurar `cookieDomain` corretamente para produção.

**Em App.php:**
```php
public string $cookieDomain = '.supportsondagens.com.br';
```

Ou deixar vazio se não houver subdomínios.

### **Solução 4: Verificar SafeFileHandler**

**Ação:** Adicionar logs no SafeFileHandler para ver se write() está sendo chamado e se está funcionando.

---

## 🧪 PRÓXIMOS PASSOS

1. ✅ **Adicionar logs detalhados** (CONCLUÍDO)
2. ⏳ **Limpar rate limit** para permitir testes
3. ⏳ **Remover config duplicada** de App.php
4. ⏳ **Testar login** com logs ativados
5. ⏳ **Analisar logs** para confirmar hipótese
6. ⏳ **Aplicar fix definitivo** baseado nos logs

---

## 📝 LOGS ESPERADOS APÓS CORREÇÃO

### Login bem-sucedido:
```
DEBUG [LOGIN] Session data set for user_id=1
DEBUG [LOGIN] Session ID: abc123xyz
DEBUG [LOGIN] Cookie name (PHP): ci_session
INFO  Login successful: user_id=1, role=admin
```

### Redirect para /dashboard/admin:
```
DEBUG [AUTHFILTER] Request to: https://ponto.../dashboard/admin
DEBUG [AUTHFILTER] Session ID: abc123xyz (MESMO ID!)
DEBUG [AUTHFILTER] Has user_id: YES
DEBUG [ADMINFILTER] Request to: https://ponto.../dashboard/admin
DEBUG [ADMINFILTER] Session ID: abc123xyz
DEBUG [ADMINFILTER] Session data: {"user_id":1,"user_role":"admin",...}
INFO  Access granted to admin dashboard
```

---

## 🎯 DIAGNÓSTICO FINAL

O loop está acontecendo porque:
1. **Sessão não persiste entre requests** (cookies ou file write problem)
2. **Cookie name mismatch** entre configurações
3. **SafeFileHandler pode não estar escrevendo** no disco corretamente

A solução requer:
1. Padronizar configurações de sessão
2. Analisar logs detalhados
3. Possivelmente substituir SafeFileHandler pelo FileHandler padrão se o problema persistir
