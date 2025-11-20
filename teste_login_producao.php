<?php
/**
 * Teste de Login em Produção
 * Simula um usuário real fazendo login no sistema
 */

echo "🔐 TESTE DE LOGIN - PRODUÇÃO\n";
echo str_repeat("=", 80) . "\n\n";

// Credenciais do admin
$email = 'admin@sistema.com';
$password = 'Admin@2025';
$baseUrl = 'http://localhost:8080';

echo "1️⃣ Testando página de login...\n";
$ch = curl_init("$baseUrl/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "   ✅ Página de login acessível (HTTP $httpCode)\n\n";
} else {
    echo "   ❌ Erro ao acessar página de login (HTTP $httpCode)\n";
    exit(1);
}

echo "2️⃣ Verificando usuário no database JSON...\n";
$employeesFile = __DIR__ . '/writable/database/employees.json';
if (file_exists($employeesFile)) {
    $employees = json_decode(file_get_contents($employeesFile), true);
    $admin = null;
    foreach ($employees as $emp) {
        if ($emp['email'] === $email) {
            $admin = $emp;
            break;
        }
    }

    if ($admin) {
        echo "   ✅ Usuário encontrado no database\n";
        echo "   📧 Email: {$admin['email']}\n";
        echo "   👤 Nome: {$admin['full_name']}\n";
        echo "   🔑 Role: {$admin['role']}\n";
        echo "   🔐 Hash senha: " . substr($admin['password'], 0, 20) . "...\n\n";

        // Verificar se a senha bate
        if (password_verify($password, $admin['password'])) {
            echo "   ✅ Senha verificada com sucesso!\n\n";
        } else {
            echo "   ❌ Senha não confere!\n";
            exit(1);
        }
    } else {
        echo "   ❌ Usuário não encontrado no database\n";
        exit(1);
    }
} else {
    echo "   ❌ Arquivo de employees não encontrado\n";
    exit(1);
}

echo "3️⃣ Extraindo CSRF token...\n";
if (preg_match('/name="csrf_token_name" value="([^"]+)"/', $response, $matches)) {
    $csrfName = $matches[1];
    echo "   ✅ CSRF name: $csrfName\n";
} else {
    echo "   ⚠️ CSRF token não encontrado (pode não ser necessário)\n";
    $csrfName = '';
}

if (preg_match('/name="csrf_token_value" value="([^"]+)"/', $response, $matches)) {
    $csrfValue = $matches[1];
    echo "   ✅ CSRF value: " . substr($csrfValue, 0, 20) . "...\n\n";
} else {
    echo "   ⚠️ CSRF value não encontrado (pode não ser necessário)\n\n";
    $csrfValue = '';
}

echo "4️⃣ Enviando requisição de login...\n";
$postData = [
    'email' => $email,
    'password' => $password,
];

if ($csrfName && $csrfValue) {
    $postData[$csrfName] = $csrfValue;
}

$ch = curl_init("$baseUrl/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

echo "   📊 HTTP Status: $httpCode\n";

if ($httpCode == 302 || $httpCode == 301) {
    echo "   ✅ Login redirecionou (esperado)\n";
    if ($redirectUrl) {
        echo "   🔗 Redirect URL: $redirectUrl\n";
    } else {
        // Extrair do header
        if (preg_match('/Location: (.+)/', $response, $matches)) {
            $redirectUrl = trim($matches[1]);
            echo "   🔗 Redirect URL: $redirectUrl\n";
        }
    }
    echo "\n";
} else {
    echo "   ⚠️ Status inesperado: $httpCode\n";
    echo "   Response (primeiros 500 chars):\n";
    echo substr($response, 0, 500) . "\n\n";
}

echo "5️⃣ Verificando cookies de sessão...\n";
if (file_exists('/tmp/cookies.txt')) {
    $cookies = file_get_contents('/tmp/cookies.txt');
    if (strpos($cookies, 'ci_session') !== false) {
        echo "   ✅ Cookie de sessão encontrado\n";
        preg_match('/ci_session\s+([^\s]+)/', $cookies, $matches);
        if (isset($matches[1])) {
            echo "   🍪 Session ID: " . substr($matches[1], 0, 20) . "...\n";
        }
    } else {
        echo "   ⚠️ Cookie de sessão não encontrado\n";
    }
    echo "\n";
}

echo "6️⃣ Testando acesso ao dashboard autenticado...\n";
$ch = curl_init("$baseUrl/dashboard");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   📊 HTTP Status: $httpCode\n";

if ($httpCode == 200) {
    echo "   ✅ Dashboard acessível - LOGIN BEM-SUCEDIDO!\n";

    // Verificar se há conteúdo do dashboard
    if (strpos($response, 'Dashboard') !== false || strpos($response, 'dashboard') !== false) {
        echo "   ✅ Conteúdo do dashboard carregado\n";
    }
} elseif ($httpCode == 302) {
    echo "   ⚠️ Dashboard redirecionou (pode indicar sessão não persistida)\n";
    if (preg_match('/Location: (.+)/', $response, $matches)) {
        echo "   🔗 Redirect para: " . trim($matches[1]) . "\n";
    }
} else {
    echo "   ❌ Erro ao acessar dashboard\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 RESUMO DO TESTE DE LOGIN\n";
echo str_repeat("=", 80) . "\n";

if ($httpCode == 200) {
    echo "✅ STATUS: LOGIN FUNCIONANDO PERFEITAMENTE!\n";
    echo "✅ Usuário admin autenticado com sucesso\n";
    echo "✅ Dashboard acessível\n";
    echo "✅ Sistema de autenticação 100% operacional\n";
} else {
    echo "⚠️ STATUS: LOGIN COM PROBLEMAS\n";
    echo "⚠️ Verifique os logs acima para mais detalhes\n";
}

echo str_repeat("=", 80) . "\n";
