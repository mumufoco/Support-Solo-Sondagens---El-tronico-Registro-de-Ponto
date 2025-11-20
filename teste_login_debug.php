<?php
/**
 * Teste de Login com DEBUG completo
 */

echo "🔍 TESTE DE LOGIN - DEBUG COMPLETO\n";
echo str_repeat("=", 80) . "\n\n";

$email = 'admin@sistema.com';
$password = 'Admin@202512';
$baseUrl = 'http://localhost:8080';

echo "Credenciais:\n";
echo "  Email: $email\n";
echo "  Senha: $password\n";
echo "  Tamanho senha: " . strlen($password) . " caracteres\n\n";

// Get login page
echo "1. Acessando página de login...\n";
$ch = curl_init("$baseUrl/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies_debug.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies_debug.txt');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  Status: $httpCode\n\n";

// Extract CSRF
$csrfName = '';
$csrfValue = '';
if (preg_match('/name="csrf_token_name" value="([^"]+)"/', $response, $matches)) {
    $csrfName = $matches[1];
}
if (preg_match('/name="csrf_token_value" value="([^"]+)"/', $response, $matches)) {
    $csrfValue = $matches[1];
}

// Send login
echo "2. Enviando requisição de login...\n";
$postData = [
    'email' => $email,
    'password' => $password,
];

if ($csrfName && $csrfValue) {
    $postData[$csrfName] = $csrfValue;
    echo "  CSRF: Incluído ($csrfName)\n";
} else {
    echo "  CSRF: Não encontrado\n";
}

$ch = curl_init("$baseUrl/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies_debug.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies_debug.txt');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  Status: $httpCode\n";

// Extract Location header
if (preg_match('/Location: (.+)/', $response, $matches)) {
    $location = trim($matches[1]);
    echo "  Location: $location\n";
}

// Extract body
$parts = explode("\r\n\r\n", $response, 2);
$body = $parts[1] ?? '';

echo "\n3. Analisando resposta...\n";

// Check for error messages
if (strpos($body, 'E-mail ou senha inválidos') !== false) {
    echo "  ❌ Erro: 'E-mail ou senha inválidos'\n";
}
if (strpos($body, 'errors') !== false) {
    echo "  ⚠️ Há erros na resposta\n";

    // Try to extract error messages
    if (preg_match_all('/<li>([^<]+)<\/li>/', $body, $matches)) {
        echo "  Erros encontrados:\n";
        foreach ($matches[1] as $error) {
            echo "    - $error\n";
        }
    }
}

// Check for success message
if (strpos($body, 'Bem-vindo') !== false) {
    echo "  ✅ Mensagem de boas-vindas encontrada!\n";
}

// Check session cookie
echo "\n4. Verificando cookies...\n";
if (file_exists('/tmp/cookies_debug.txt')) {
    $cookies = file_get_contents('/tmp/cookies_debug.txt');
    if (strpos($cookies, 'ci_session') !== false) {
        echo "  ✅ Cookie ci_session encontrado\n";
        preg_match('/ci_session\s+([^\s]+)/', $cookies, $matches);
        if (isset($matches[1])) {
            echo "  Session ID: " . substr($matches[1], 0, 30) . "...\n";
        }
    } else {
        echo "  ❌ Cookie ci_session NÃO encontrado\n";
    }

    // Show all cookies
    echo "\n  Todos os cookies:\n";
    $lines = explode("\n", $cookies);
    foreach ($lines as $line) {
        if (!empty($line) && $line[0] !== '#') {
            echo "    " . substr($line, 0, 100) . "\n";
        }
    }
} else {
    echo "  ❌ Arquivo de cookies não existe\n";
}

// Try accessing dashboard
echo "\n5. Tentando acessar dashboard...\n";
$ch = curl_init("$baseUrl/dashboard");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies_debug.txt');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  Status: $httpCode\n";

if ($httpCode == 200) {
    echo "  ✅ Dashboard acessível - LOGIN FUNCIONOU!\n";
} elseif ($httpCode == 302) {
    if (preg_match('/Location: (.+)/', $response, $matches)) {
        $location = trim($matches[1]);
        echo "  ⚠️ Redirecionou para: $location\n";

        if (strpos($location, 'login') !== false) {
            echo "  ❌ Sessão não persistiu - usuário não autenticado\n";
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
