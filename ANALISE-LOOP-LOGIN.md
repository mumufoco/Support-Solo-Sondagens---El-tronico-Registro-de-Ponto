# ANÁLISE DO LOOP DE REDIRECT NO LOGIN

## 🔍 Problema Identificado

O sistema está preso em um loop de redirect infinito:
1. Usuário faz login com credenciais corretas
2. LoginController processa o login e redireciona para `/dashboard/admin`
3. AdminFilter verifica se `user_id` existe na sessão
4. `user_id` NÃO é encontrado na sessão
5. AdminFilter redireciona de volta para `/auth/login`
6. **LOOP INFINITO**

## 🐛 Causa Raiz

O problema está na **incompatibilidade entre os métodos de sessão do CodeIgniter e funções nativas do PHP** no `LoginController.php` (linhas 133-159).

### Código Atual (Problemático):

```php
// CRITICAL FIX: Regenerate session ID BEFORE setting data
$this->session->regenerate();

// Create session data
$sessionData = [
    'user_id' => $user->id,
    // ... outros dados
];

// Set session data
$this->session->set($sessionData);

// CRITICAL: Force immediate write to storage
session_write_close();  // ❌ PROBLEMA: Mistura CI com PHP nativo

// Restart session for current request
if (session_status() === PHP_SESSION_NONE) {
    session_start();  // ❌ PROBLEMA: Conflito com CI Session
}
```

### Por que isso causa o loop:

1. **CodeIgniter usa SafeFileHandler customizado** (`app/Session/Handlers/SafeFileHandler.php`)
2. **SafeFileHandler gerencia seus próprios file handles e locks**
3. **`$this->session->set()` armazena dados no buffer interno do CodeIgniter**
4. **`session_write_close()` fecha a sessão PHP ANTES do CI escrever seu buffer**
5. **`session_start()` reabre a sessão SEM os dados do buffer do CI**
6. **Resultado: dados perdidos, `user_id` não persiste**

## ✅ Solução

### Opção 1: Usar APENAS métodos do CodeIgniter (RECOMENDADO)

```php
// Regenerate session ID BEFORE setting data
$this->session->regenerate();

// Create and set session data
$sessionData = [
    'user_id'       => $user->id,
    'user_name'     => $user->name,
    'user_email'    => $user->email,
    'user_role'     => $user->role,
    'user_active'   => (bool) $user->active,
    'last_activity' => time(),
    'logged_in'     => true,
    'employee'      => (array) $user,
];

$this->session->set($sessionData);

// Force immediate write using CI's method
$this->session->stop();  // Fecha e escreve a sessão
$this->session->start(); // Reabre para uso imediato

// OU simplesmente deixar o CI escrever naturalmente:
// (remove session_write_close() e session_start() completamente)
```

### Opção 2: Usar APENAS PHP nativo

```php
// Regenerate BEFORE setting
session_regenerate_id(false); // false = não destruir dados antigos

// Set session data usando $_SESSION diretamente
$_SESSION['user_id']       = $user->id;
$_SESSION['user_name']     = $user->name;
$_SESSION['user_email']    = $user->email;
$_SESSION['user_role']     = $user->role;
$_SESSION['user_active']   = (bool) $user->active;
$_SESSION['last_activity'] = time();
$_SESSION['logged_in']     = true;
$_SESSION['employee']      = (array) $user;

// Force write
session_write_close();
session_start();
```

## 🔧 Configuração Atual

- **Session Driver**: `SafeFileHandler` (custom)
- **regenerateDestroy**: `false` (preserva dados antigos)
- **cookieName**: `ci_session`
- **expiration**: `7200` segundos (2 horas)
- **savePath**: `writable/session`

## 📊 Evidências do Problema

Nos logs de produção:

```
WARNING - 2025-12-02 15:56:25 --> Rate limit exceeded for IP: 77.111.247.44,
   Attempts=6/5
```

Isso mostra:
- Usuário tentou fazer login 6 vezes
- Todas as tentativas falharam (rate limit atingido)
- Loop confirmado: cada tentativa resulta em redirect, que resulta em nova tentativa

## 💡 Recomendação Final

**Implementar Opção 1** - Remover completamente as chamadas nativas `session_write_close()` e `session_start()`, confiando no CodeIgniter para gerenciar a sessão.

Por quê:
1. O projeto já usa SafeFileHandler customizado
2. Misturar métodos causa conflitos de estado interno
3. CodeIgniter escreve sessões automaticamente no final da requisição
4. Mais simples e menos propenso a erros

## 🎯 Arquivos para Modificar

1. `app/Controllers/Auth/LoginController.php` (linhas 154-159)
   - Remover `session_write_close()`
   - Remover `session_start()`
   - Confiar no CI para escrever sessão
