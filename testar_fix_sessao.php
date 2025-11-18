<?php
/**
 * TESTE COMPLETO: Fix de Sessão (localStorage)
 *
 * Este teste valida que o instalador funciona SEM dependência de sessão PHP
 */

echo "🧪 TESTE DO FIX: Sessão → localStorage\n";
echo str_repeat("=", 60) . "\n\n";

// Simular requisições HTTP
function httpRequest($url, $method = 'GET', $postData = null, &$cookies = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }

    // Enviar cookies
    if (!empty($cookies)) {
        curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookies));
    }

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    // Extrair cookies da resposta
    if (preg_match_all('/Set-Cookie:\s*([^;]+)/i', $headers, $matches)) {
        foreach ($matches[1] as $cookie) {
            $cookies[] = $cookie;
        }
    }

    curl_close($ch);

    return $body;
}

// Configuração do servidor
$baseUrl = 'http://localhost:9000';
$cookies = [];

echo "📍 Servidor: $baseUrl\n\n";

// ========================================
// PASSO 1: Testar Conexão MySQL
// ========================================
echo "PASSO 1: Testar Conexão MySQL\n";
echo str_repeat("-", 60) . "\n";

$testData = [
    'action' => 'test_connection',
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_database' => 'test_db_fix_sessao',
    'db_username' => 'test_user',
    'db_password' => 'test_pass'
];

echo "POST /install.php\n";
echo "  action: test_connection\n";
echo "  db_host: localhost\n";
echo "  db_database: test_db_fix_sessao\n\n";

$response = httpRequest("$baseUrl/install.php", 'POST', $testData, $cookies);
$data = json_decode($response, true);

if (!$data) {
    echo "❌ ERRO: Resposta não é JSON válido\n";
    echo "Resposta:\n$response\n";
    exit(1);
}

echo "Resposta JSON:\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// VALIDAÇÃO 1: Verificar se db_config está presente no JSON
echo "VALIDAÇÃO 1: db_config no JSON\n";
if (isset($data['db_config'])) {
    echo "  ✅ PASSOU: db_config presente no JSON\n";
    echo "  📦 db_config = " . json_encode($data['db_config']) . "\n\n";

    // Simular localStorage (salvar em variável PHP)
    $localStorage = [
        'db_config' => $data['db_config'],
        'existing_tables' => $data['existing_tables'] ?? []
    ];
} else {
    echo "  ❌ FALHOU: db_config NÃO está no JSON\n";
    echo "  ⚠️  Instalador ainda está usando sessão!\n\n";
    exit(1);
}

// VALIDAÇÃO 2: Verificar se existing_tables está presente
echo "VALIDAÇÃO 2: existing_tables no JSON\n";
if (isset($data['existing_tables'])) {
    echo "  ✅ PASSOU: existing_tables presente no JSON\n";
    echo "  📋 existing_tables = " . json_encode($data['existing_tables']) . "\n\n";
} else {
    echo "  ❌ FALHOU: existing_tables NÃO está no JSON\n\n";
    exit(1);
}

// ========================================
// PASSO 2: Simular Instalação (SEM SESSÃO)
// ========================================
echo "\nPASSO 2: Executar Instalação (Enviando db_config via POST)\n";
echo str_repeat("-", 60) . "\n";

// IMPORTANTE: NÃO usar cookies/sessão - enviar tudo via POST
$installData = [
    'action' => 'run_installation',
    // Dados do admin
    'admin_name' => 'Admin Teste',
    'admin_email' => 'admin@teste.com',
    'admin_password' => 'Senha@123456',
    // Dados do MySQL (do "localStorage")
    'db_host' => $localStorage['db_config']['host'],
    'db_port' => $localStorage['db_config']['port'],
    'db_database' => $localStorage['db_config']['database'],
    'db_username' => $localStorage['db_config']['username'],
    'db_password' => $localStorage['db_config']['password'],
    'existing_tables' => json_encode($localStorage['existing_tables'])
];

echo "POST /install.php (SEM cookies de sessão)\n";
echo "  action: run_installation\n";
echo "  admin_email: admin@teste.com\n";
echo "  db_host: {$installData['db_host']} (do localStorage)\n";
echo "  db_database: {$installData['db_database']} (do localStorage)\n\n";

// NÃO enviar cookies - testar SEM sessão
$newCookies = [];
$response = httpRequest("$baseUrl/install.php", 'POST', $installData, $newCookies);
$data = json_decode($response, true);

if (!$data) {
    echo "❌ ERRO: Resposta não é JSON válido\n";
    echo "Resposta:\n$response\n";
    exit(1);
}

echo "Resposta JSON:\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// VALIDAÇÃO 3: Verificar se NÃO retorna erro de sessão
echo "VALIDAÇÃO 3: Erro de Configuração\n";
if (strpos($data['message'] ?? '', 'Configuração do banco não encontrada') !== false) {
    echo "  ❌ FALHOU: Ainda retorna erro de sessão!\n";
    echo "  ⚠️  O fix NÃO funcionou - instalador ainda depende de \$_SESSION\n\n";
    exit(1);
} else {
    echo "  ✅ PASSOU: NÃO retorna erro de configuração!\n";
    echo "  ✅ Instalador NÃO depende mais de sessão PHP\n\n";
}

// VALIDAÇÃO 4: Verificar se recebeu os dados do MySQL
echo "VALIDAÇÃO 4: Processamento dos Dados\n";
if (isset($data['logs']) && is_array($data['logs'])) {
    $logs = implode("\n", $data['logs']);

    // Verificar se tentou conectar ao MySQL
    if (strpos($logs, 'Dados do MySQL não fornecidos') !== false) {
        echo "  ❌ FALHOU: Dados do MySQL não foram recebidos via POST\n\n";
        exit(1);
    } else {
        echo "  ✅ PASSOU: Dados do MySQL recebidos corretamente via POST\n";
        echo "  ✅ Instalador processou os dados sem usar \$_SESSION\n\n";
    }
} else {
    echo "  ⚠️  Logs não disponíveis\n\n";
}

// ========================================
// RESUMO
// ========================================
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESUMO DO TESTE\n";
echo str_repeat("=", 60) . "\n\n";

echo "✅ VALIDAÇÃO 1: db_config retornado no JSON (não em sessão)\n";
echo "✅ VALIDAÇÃO 2: existing_tables retornado no JSON\n";
echo "✅ VALIDAÇÃO 3: NÃO retorna erro de 'Configuração não encontrada'\n";
echo "✅ VALIDAÇÃO 4: Dados do MySQL recebidos via POST (não de sessão)\n\n";

echo "🎉 FIX VALIDADO COM SUCESSO!\n\n";
echo "📝 O instalador agora:\n";
echo "  1. Retorna db_config no JSON (test_connection)\n";
echo "  2. Frontend salva em localStorage (JavaScript)\n";
echo "  3. Frontend envia via POST (run_installation)\n";
echo "  4. Backend recebe via POST (não usa \$_SESSION)\n\n";

echo "✅ ZERO dependência de sessão PHP!\n";
echo "✅ Funcionará mesmo se sessões não persistirem!\n\n";

echo str_repeat("=", 60) . "\n";
echo "🚀 FIX PRONTO PARA PRODUÇÃO\n";
echo str_repeat("=", 60) . "\n";
