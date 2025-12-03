#!/usr/bin/env php
<?php
/**
 * Teste REAL de login HTTP com cookies e sessões
 * Simula o fluxo completo de login do navegador
 */

echo "====================================================================\n";
echo "  TESTE REAL DE LOGIN HTTP\n";
echo "====================================================================\n\n";

$baseUrl = 'http://localhost:8080';
$cookieFile = __DIR__ . '/writable/test-cookies.txt';

// Remover arquivo de cookies anterior
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

// ====================================================================
// STEP 1: Acessar página de login (GET) para obter CSRF token
// ====================================================================

echo "📋 STEP 1: Acessando página de login...\n";

$ch = curl_init($baseUrl . '/auth/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_HEADER => true,
    CURLOPT_VERBOSE => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  HTTP Status: $httpCode\n";

// Extrair headers e body
list($headers, $body) = explode("\r\n\r\n", $response, 2);

// Procurar por cookie de sessão nos headers
preg_match('/Set-Cookie: ([^;]+)/', $headers, $sessionCookie);
if (!empty($sessionCookie[1])) {
    echo "  ✓ Cookie de sessão recebido: {$sessionCookie[1]}\n";
} else {
    echo "  ⚠️  Nenhum cookie de sessão encontrado\n";
}

// Extrair CSRF token (se existir)
preg_match('/name=["\']csrf_token_name["\'] value=["\']([^"\']+)["\']/', $body, $csrfMatch);
$csrfToken = $csrfMatch[1] ?? '';

if ($csrfToken) {
    echo "  ✓ CSRF Token encontrado: " . substr($csrfToken, 0, 20) . "...\n";
} else {
    echo "  ⚠️  CSRF Token não encontrado (pode estar desabilitado)\n";
}

// Verificar se formulário de login existe
if (strpos($body, '<form') !== false && strpos($body, 'auth/login') !== false) {
    echo "  ✓ Formulário de login encontrado\n";
} else {
    echo "  ❌ Formulário de login NÃO encontrado\n";
}

echo "\n";

// ====================================================================
// STEP 2: Fazer POST de login (sem credenciais reais, apenas teste)
// ====================================================================

echo "📋 STEP 2: Testando POST de login...\n";

// Como não temos banco de dados real, vamos apenas enviar dados
// e ver a resposta do servidor
$postData = http_build_query([
    'email' => 'admin@test.com',
    'password' => 'admin123',
    'csrf_token_name' => $csrfToken
]);

$ch = curl_init($baseUrl . '/auth/login');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false, // Não seguir redirects para ver o Location
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_HEADER => true,
    CURLOPT_VERBOSE => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

echo "  HTTP Status: $httpCode\n";

// Analisar resposta
list($headers, $body) = explode("\r\n\r\n", $response, 2);

if ($httpCode === 302 || $httpCode === 303 || $httpCode === 307) {
    // Procurar header Location
    preg_match('/Location: (.+)/', $headers, $locationMatch);
    $location = trim($locationMatch[1] ?? '');

    echo "  ✓ Redirect detectado\n";
    echo "  → Location: $location\n";

    // Verificar se redirecionou para dashboard (sucesso) ou volta para login (falha)
    if (strpos($location, '/dashboard') !== false) {
        echo "  ✅ SUCESSO: Redirecionou para dashboard!\n";
    } elseif (strpos($location, '/auth/login') !== false) {
        echo "  ⚠️  FALHA: Redirecionou de volta para login\n";
        echo "  → Possível causa: Credenciais inválidas (esperado sem banco de dados)\n";
    } else {
        echo "  ℹ️  Redirecionou para: $location\n";
    }
} elseif ($httpCode === 200) {
    echo "  ⚠️  Status 200 (sem redirect)\n";

    // Verificar se há mensagem de erro no body
    if (strpos($body, 'erro') !== false || strpos($body, 'error') !== false) {
        echo "  → Possível erro no formulário\n";
    }

    if (strpos($body, '<form') !== false) {
        echo "  → Formulário de login ainda está presente (falha)\n";
    }
}

// Verificar cookies após login
if (file_exists($cookieFile)) {
    $cookies = file_get_contents($cookieFile);
    $cookieCount = substr_count($cookies, 'localhost');
    echo "  📊 Cookies salvos: $cookieCount cookie(s)\n";

    // Procurar especificamente pelo ci_session
    if (strpos($cookies, 'ci_session') !== false) {
        echo "  ✓ Cookie 'ci_session' presente\n";
    } else {
        echo "  ⚠️  Cookie 'ci_session' NÃO encontrado\n";
    }
}

echo "\n";

// ====================================================================
// STEP 3: Verificar se sessão persiste (acesso a página protegida)
// ====================================================================

echo "📋 STEP 3: Testando acesso a página protegida (dashboard)...\n";

$ch = curl_init($baseUrl . '/dashboard/admin');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_HEADER => true,
    CURLOPT_VERBOSE => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  HTTP Status: $httpCode\n";

list($headers, $body) = explode("\r\n\r\n", $response, 2);

if ($httpCode === 302 || $httpCode === 303 || $httpCode === 307) {
    preg_match('/Location: (.+)/', $headers, $locationMatch);
    $location = trim($locationMatch[1] ?? '');

    echo "  ✓ Redirect detectado\n";
    echo "  → Location: $location\n";

    if (strpos($location, '/auth/login') !== false) {
        echo "  ⚠️  FALHA: Redirecionou para login (sessão NÃO persistiu)\n";
        echo "  → Este é o problema do LOOP que estamos investigando!\n";
    } else {
        echo "  ✓ Redirecionou para: $location\n";
    }
} elseif ($httpCode === 200) {
    echo "  ✓ Status 200 (página carregada)\n";

    if (strpos($body, 'Dashboard') !== false || strpos($body, 'Painel') !== false) {
        echo "  ✅ SUCESSO: Dashboard carregou! Sessão está funcionando!\n";
    } else {
        echo "  ℹ️  Página carregada, mas conteúdo desconhecido\n";
    }
} elseif ($httpCode === 404) {
    echo "  ⚠️  404 Not Found (rota não existe)\n";
}

echo "\n";

// ====================================================================
// ANÁLISE DE LOGS
// ====================================================================

echo "📋 STEP 4: Verificando logs da aplicação...\n";

$logFile = __DIR__ . '/writable/logs/log-' . date('Y-m-d') . '.log';

if (file_exists($logFile)) {
    echo "  ✓ Arquivo de log encontrado: $logFile\n";

    // Ler últimas 50 linhas
    $lines = file($logFile);
    $recentLines = array_slice($lines, -50);

    // Procurar por mensagens críticas
    $criticalMessages = 0;
    foreach ($recentLines as $line) {
        if (stripos($line, 'AUTHFILTER') !== false ||
            stripos($line, 'LOGIN') !== false ||
            stripos($line, 'SESSION') !== false ||
            stripos($line, 'ERROR') !== false) {
            $criticalMessages++;
        }
    }

    echo "  📊 Mensagens relevantes encontradas: $criticalMessages\n";

    if ($criticalMessages > 0) {
        echo "\n  📝 Últimas mensagens relevantes:\n";
        echo "  " . str_repeat("-", 68) . "\n";
        foreach ($recentLines as $line) {
            if (stripos($line, 'AUTHFILTER') !== false ||
                stripos($line, 'LOGIN') !== false ||
                stripos($line, 'SESSION') !== false ||
                stripos($line, 'ERROR') !== false) {
                echo "  " . trim($line) . "\n";
            }
        }
    }
} else {
    echo "  ⚠️  Arquivo de log não encontrado: $logFile\n";
}

echo "\n";

// ====================================================================
// VERIFICAR ARQUIVOS DE SESSÃO
// ====================================================================

echo "📋 STEP 5: Verificando arquivos de sessão...\n";

$sessionDir = __DIR__ . '/writable/session';

if (is_dir($sessionDir)) {
    $files = glob($sessionDir . '/ci_session*');
    echo "  📂 Diretório de sessão: $sessionDir\n";
    echo "  📊 Arquivos de sessão encontrados: " . count($files) . "\n";

    if (count($files) > 0) {
        echo "  ✓ Sessões estão sendo gravadas no disco\n";

        // Mostrar arquivos recentes
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $recentFile = $files[0];
        $age = time() - filemtime($recentFile);
        echo "  📄 Arquivo mais recente: " . basename($recentFile) . " (criado há {$age}s)\n";

        // Ler conteúdo (se for pequeno)
        $content = file_get_contents($recentFile);
        if (strlen($content) < 500) {
            echo "  📝 Conteúdo: " . substr($content, 0, 200) . "...\n";
        }
    } else {
        echo "  ⚠️  Nenhum arquivo de sessão encontrado\n";
        echo "  → Sessões podem não estar sendo persistidas\n";
    }
} else {
    echo "  ❌ Diretório de sessão não existe: $sessionDir\n";
}

echo "\n";

// ====================================================================
// RESUMO
// ====================================================================

echo "====================================================================\n";
echo "  RESUMO DO TESTE\n";
echo "====================================================================\n\n";

echo "🔍 O que testamos:\n";
echo "  1. GET /auth/login - Carregar formulário e receber cookie\n";
echo "  2. POST /auth/login - Enviar credenciais\n";
echo "  3. GET /dashboard/admin - Verificar se sessão persiste\n";
echo "  4. Análise de logs de debug\n";
echo "  5. Verificação de arquivos de sessão\n\n";

echo "📊 Próximos passos:\n";
echo "  - Analisar os logs acima para identificar problemas\n";
echo "  - Verificar se cookies estão sendo enviados/recebidos corretamente\n";
echo "  - Confirmar que arquivos de sessão estão sendo criados e lidos\n\n";
