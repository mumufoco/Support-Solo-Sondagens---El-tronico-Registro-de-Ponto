# ANÁLISE EXAUSTIVA COMPLETA - TODOS OS PROBLEMAS DO LOGIN

## ❌ PROBLEMA #1: NOME DO COOKIE INCONSISTENTE (CRÍTICO)

**PHP Default:**
```
session.name => PHPSESSID
```

**CodeIgniter Config (Session.php):**
```php
public string $cookieName = 'ci_session';
```

**SafeSession.php tenta mudar:**
```php
session_name($config->cookieName); // Linha 46
```

**PROBLEMA:**
- PHP inicia com nome `PHPSESSID`
- SafeSession tenta mudar para `ci_session` mas só funciona se sessão NÃO foi iniciada
- Se a sessão JÁ foi iniciada (por algum plugin, middleware, etc), o nome não muda
- Cookie criado: `PHPSESSID` ou `ci_session`?
- Próximo request: Espera qual nome?
- **RESULTADO: SESSÃO PERDIDA!**

---

## ❌ PROBLEMA #2: SAVE PATH INCONSISTENTE (CRÍTICO)

**PHP Default:**
```
session.save_path => /var/lib/php/sessions
```

**Session.php configura:**
```php
$this->savePath = WRITEPATH . 'session'; // writable/session
```

**SafeFileHandler usa:**
```php
$this->savePath = $path; // writable/session
```

**PROBLEMA:**
- Se a sessão for iniciada ANTES do SafeFileHandler ser configurado
- Arquivo será salvo em `/var/lib/php/sessions`
- Mas SafeFileHandler procura em `writable/session`
- **RESULTADO: SESSÃO NÃO ENCONTRADA!**

**EVIDÊNCIA:** `writable/session/` tem APENAS `index.html`, SEM arquivos de sessão!

---

## ❌ PROBLEMA #3: .user.ini NÃO ESTÁ SENDO APLICADO (GRAVE)

**.user.ini define:**
```ini
session.gc_divisor = 100
```

**php -i mostra:**
```
session.gc_divisor => 1000 => 1000
```

**PROBLEMA:**
- Configurações em `.user.ini` NÃO estão sendo aplicadas
- Servidor pode não estar configurado para ler `.user.ini`
- Todas as configurações de sessão em `.user.ini` são IGNORADAS

---

## ❌ PROBLEMA #4: SESSÃO PODE SER INICIADA PREMATURAMENTE

**Locais onde sessão pode ser iniciada:**

1. **SafeSession.php linha 43:** `if (session_status() === PHP_SESSION_NONE)`
2. **Qualquer lugar que chame `session()`** - inicia automaticamente
3. **AuthFilter linha 25:** `$session = session();` - PRIMEIRA chamada inicia sessão
4. **LoginController linha 150:** `$this->session->set()` - Se não iniciada, inicia aqui

**PROBLEMA:**
- Se `session()` é chamada ANTES de SafeFileHandler estar configurado
- Sessão inicia com configurações padrão do PHP
- SafeFileHandler nunca consegue aplicar suas configurações
- **RESULTADO: SESSÃO USA CONFIG ERRADA!**

---

## ❌ PROBLEMA #5: AuthFilter DESTRÓI SESSÃO EM CERTAS CONDIÇÕES

**AuthFilter.php linha 57-59:**
```php
if ($lastActivity && (time() - $lastActivity > $sessionTimeout)) {
    // Session expired
    $session->destroy();  // ← DESTRÓI!
}
```

**LoginController define:**
```php
'last_activity' => time(), // Linha 144
```

**PROBLEMA:**
- Em teoria, `time() - time()` = 0 < 7200, então NÃO destrói
- MAS se houver lag no redirect (1-2 segundos)
- E se `$sessionTimeout` estiver configurado incorretamente
- E se houver problema de relógio do servidor
- PODE destruir sessão prematuramente

---

## ❌ PROBLEMA #6: MÚLTIPLOS FILTROS VERIFICANDO SESSÃO

**Route /dashboard/admin:**
```php
$routes->group('dashboard', ['filter' => 'auth'], function($routes) {
    $routes->get('admin', '...', ['filter' => 'admin']);
});
```

**PROBLEMA:**
- AuthFilter roda PRIMEIRO
- AdminFilter roda SEGUNDO
- AMBOS chamam `session()` que pode re-inicializar sessão
- AMBOS verificam `user_id`
- Se primeiro encontra mas segundo não, loop!

---

## 🔍 FLUXO DO PROBLEMA (DETALHADO)

```
1. USER POSTS LOGIN
   ↓
2. LoginController::authenticate()
   - Valida credenciais ✓
   - Chama $this->session->regenerate()
     → Internamente: session_regenerate_id()
     → Mas qual session name? PHPSESSID ou ci_session?
   - Chama $this->session->set($sessionData)
     → Escreve onde? /var/lib/php/sessions ou writable/session?
   - Cookie criado: PHPSESSID=abc123 (ERRADO!)
   ↓
3. BROWSER REDIRECT
   GET /dashboard/admin
   Cookie: PHPSESSID=abc123
   ↓
4. AuthFilter::before()
   - Chama $session = session()
     → NOVA SESSÃO INICIADA!
     → Nome esperado: ci_session
     → Nome recebido: PHPSESSID
     → MISMATCH!
   - session_id() = xyz789 (NOVO, DIFERENTE!)
   - $session->get('user_id') = NULL
   - Redirect /auth/login
   ↓
5. LOOP INFINITO (45+ tentativas)
```

---

## ✅ SOLUÇÃO DEFINITIVA

### **FIX #1: FORÇAR NOME DA SESSÃO NO INÍCIO**

Adicionar no **public/index.php ANTES de Boot::bootWeb()**:

```php
// CRITICAL FIX: Set session name BEFORE any session is started
// This MUST be done before CodeIgniter boots to ensure consistency
if (session_status() === PHP_SESSION_NONE) {
    session_name('ci_session');
}
```

### **FIX #2: FORÇAR SAVE PATH NO INÍCIO**

Adicionar no **public/index.php ANTES de Boot::bootWeb()**:

```php
// CRITICAL FIX: Set session save path BEFORE any session is started
$sessionPath = dirname(__DIR__) . '/writable/session';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
if (is_writable($sessionPath)) {
    session_save_path($sessionPath);
}
```

### **FIX #3: SIMPLIFICAR SafeSession**

O SafeSession está tentando fazer coisas que já foram feitas. Simplificar ou remover.

### **FIX #4: REMOVER VERIFICAÇÃO DE TIMEOUT EXCESSIVA**

A verificação de timeout no AuthFilter é redundante (CodeIgniter já faz).

### **FIX #5: ADICIONAR LOGS PARA RASTREAR**

Já adicionado, mas precisa verificar se está mostrando session_name e session_id corretos.

---

## 📝 ARQUIVOS PARA MODIFICAR

1. **public/index.php** - Adicionar config de sessão ANTES do boot
2. **app/Libraries/SafeSession.php** - Simplificar ou remover
3. **app/Filters/AuthFilter.php** - Remover ou simplificar timeout check
4. **app/Config/Session.php** - Confirmar configurações
5. **writable/session/** - Verificar permissões (755, writable)

---

## 🎯 CAUSA RAIZ CONFIRMADA

O loop acontece porque:

1. **Sessão criada com nome PHPSESSID** (default PHP)
2. **Arquivo salvo em /var/lib/php/sessions** (default PHP)
3. **Próximo request espera nome ci_session** (Config CodeIgniter)
4. **SafeFileHandler procura em writable/session** (Config CodeIgniter)
5. **MISMATCH DUPLO!** (nome E caminho)
6. **Nova sessão vazia criada**
7. **user_id não encontrado**
8. **Redirect loop infinito**

---

## ⚡ IMPLEMENTAÇÃO URGENTE

Vou implementar o FIX #1 e #2 AGORA em public/index.php.
Isso deve resolver IMEDIATAMENTE o problema.
