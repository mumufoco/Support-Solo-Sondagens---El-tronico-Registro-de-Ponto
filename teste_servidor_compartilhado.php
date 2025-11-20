<?php
/**
 * Teste do Servidor Compartilhado
 * URL: ponto.supportsondagens.com.br
 */

$baseUrl = 'https://ponto.supportsondagens.com.br';

echo "🌐 TESTE - SERVIDOR COMPARTILHADO\n";
echo str_repeat("=", 80) . "\n";
echo "URL: $baseUrl\n";
echo str_repeat("=", 80) . "\n\n";

// Endpoints para testar
$endpoints = [
    'Health Check' => '/health',
    'Página Inicial' => '/',
    'Login' => '/auth/login',
    'API Health' => '/api/health',
    'Instalador' => '/install.php',
];

echo "📋 TESTANDO ENDPOINTS:\n\n";

foreach ($endpoints as $name => $path) {
    $url = $baseUrl . $path;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "  $name:\n";
    echo "    URL: $url\n";

    if ($error) {
        echo "    ❌ ERRO: $error\n";
    } else {
        if ($httpCode == 200) {
            echo "    ✅ Status: $httpCode (OK)\n";
        } elseif ($httpCode == 302 || $httpCode == 301) {
            echo "    ✅ Status: $httpCode (Redirect)\n";
        } elseif ($httpCode == 404) {
            echo "    ⚠️  Status: $httpCode (Não encontrado)\n";
        } elseif ($httpCode == 500) {
            echo "    ❌ Status: $httpCode (ERRO CRÍTICO)\n";

            // Extrair mensagem de erro
            if (preg_match('/<title>([^<]+)<\/title>/', $response, $matches)) {
                echo "    Erro: {$matches[1]}\n";
            }
        } else {
            echo "    ⚠️  Status: $httpCode\n";
        }
    }
    echo "\n";
}

echo str_repeat("=", 80) . "\n";
echo "📊 VERIFICAÇÕES ADICIONAIS\n";
echo str_repeat("=", 80) . "\n\n";

// Verificar se arquivo INSTALLED existe
echo "1. Verificando arquivo INSTALLED local:\n";
$installedFile = __DIR__ . '/writable/INSTALLED';
if (file_exists($installedFile)) {
    echo "   ✅ Arquivo INSTALLED existe\n";
    echo "   Data: " . file_get_contents($installedFile) . "\n";
} else {
    echo "   ❌ Arquivo INSTALLED NÃO encontrado\n";
}
echo "\n";

// Verificar permissões
echo "2. Verificando permissões:\n";
$dirs = [
    'writable',
    'writable/cache',
    'writable/logs',
    'writable/session',
    'writable/database',
];

foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path) ? '✅ Gravável' : '❌ Não gravável';
        echo "   $dir: $perms - $writable\n";
    } else {
        echo "   $dir: ❌ Não existe\n";
    }
}
echo "\n";

// Verificar JSON database
echo "3. Verificando JSON database:\n";
$jsonFiles = [
    'employees.json',
    'timesheets.json',
    'settings.json',
];

foreach ($jsonFiles as $file) {
    $path = __DIR__ . '/writable/database/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $data = json_decode(file_get_contents($path), true);
        $count = is_array($data) ? count($data) : 0;
        echo "   ✅ $file ($size bytes, $count registros)\n";
    } else {
        echo "   ❌ $file não encontrado\n";
    }
}
echo "\n";

// Verificar PHP version no servidor
echo "4. Informações do ambiente:\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   CodeIgniter: " . (defined('CI_VERSION') ? CI_VERSION : 'Não detectado') . "\n";
echo "   Ambiente: " . (getenv('CI_ENVIRONMENT') ?: 'Não definido') . "\n";
echo "\n";

echo str_repeat("=", 80) . "\n";
echo "✅ TESTE CONCLUÍDO\n";
echo str_repeat("=", 80) . "\n";
