<?php
/**
 * Script de Debug para Servidor Compartilhado
 * Acesse: https://ponto.supportsondagens.com.br/debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug - Servidor Compartilhado</h1>";
echo "<hr>";

// 1. Informações do PHP
echo "<h2>1. Informações do PHP</h2>";
echo "<pre>";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server API: " . php_sapi_name() . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . __FILE__ . "\n";
echo "</pre>";

// 2. Verificar ROOTPATH
echo "<h2>2. Verificar ROOTPATH</h2>";
echo "<pre>";
$rootPath = dirname(__DIR__);
echo "ROOTPATH: $rootPath\n";
echo "Existe: " . (is_dir($rootPath) ? 'SIM' : 'NÃO') . "\n";
echo "</pre>";

// 3. Verificar arquivo INSTALLED
echo "<h2>3. Arquivo INSTALLED</h2>";
echo "<pre>";
$installedFile = $rootPath . '/writable/INSTALLED';
if (file_exists($installedFile)) {
    echo "✅ Arquivo existe\n";
    echo "Conteúdo: " . file_get_contents($installedFile) . "\n";
} else {
    echo "❌ Arquivo NÃO existe\n";
    echo "Procurado em: $installedFile\n";
}
echo "</pre>";

// 4. Verificar CodeIgniter
echo "<h2>4. CodeIgniter</h2>";
echo "<pre>";
$indexFile = __DIR__ . '/index.php';
echo "Index.php: " . (file_exists($indexFile) ? 'SIM' : 'NÃO') . "\n";

$vendorAutoload = $rootPath . '/vendor/autoload.php';
echo "Vendor autoload: " . (file_exists($vendorAutoload) ? 'SIM' : 'NÃO') . "\n";

$sparkFile = $rootPath . '/spark';
echo "Spark: " . (file_exists($sparkFile) ? 'SIM' : 'NÃO') . "\n";
echo "</pre>";

// 5. Verificar Controllers
echo "<h2>5. Controllers</h2>";
echo "<pre>";
$controllers = [
    'BaseController.php',
    'Auth/LoginController.php',
    'HealthController.php',
    'Admin/DashboardController.php',
];

foreach ($controllers as $controller) {
    $path = $rootPath . '/app/Controllers/' . $controller;
    echo "$controller: " . (file_exists($path) ? '✅' : '❌') . "\n";
}
echo "</pre>";

// 6. Verificar writable
echo "<h2>6. Diretórios Writable</h2>";
echo "<pre>";
$dirs = ['cache', 'logs', 'session', 'database', 'uploads'];
foreach ($dirs as $dir) {
    $path = $rootPath . '/writable/' . $dir;
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
    echo "writable/$dir: " . ($exists ? '✅' : '❌') . " | Gravável: " . ($writable ? 'SIM' : 'NÃO') . " | Permissões: $perms\n";
}
echo "</pre>";

// 7. Testar carregamento do CodeIgniter
echo "<h2>7. Teste de Carregamento do CodeIgniter</h2>";
echo "<pre>";
try {
    // Tentar definir ROOTPATH
    if (!defined('ROOTPATH')) {
        define('ROOTPATH', $rootPath . DIRECTORY_SEPARATOR);
    }

    echo "ROOTPATH definido: " . ROOTPATH . "\n";

    // Verificar se o arquivo index.php tem erros
    if (file_exists($indexFile)) {
        echo "Tentando incluir vendor/autoload...\n";
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
            echo "✅ Autoload carregado\n";
        } else {
            echo "❌ Autoload não encontrado\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
echo "</pre>";

// 8. Verificar logs
echo "<h2>8. Últimos Erros do Log</h2>";
echo "<pre>";
$logDir = $rootPath . '/writable/logs';
$logFiles = glob($logDir . '/log-*.log');
if ($logFiles) {
    rsort($logFiles);
    $latestLog = $logFiles[0];
    echo "Arquivo: " . basename($latestLog) . "\n";
    echo str_repeat('-', 80) . "\n";
    $lines = file($latestLog);
    $lastLines = array_slice($lines, -50);
    echo implode('', $lastLines);
} else {
    echo "Nenhum arquivo de log encontrado\n";
}
echo "</pre>";

// 9. Informações do .env
echo "<h2>9. Arquivo .env</h2>";
echo "<pre>";
$envFile = $rootPath . '/.env';
if (file_exists($envFile)) {
    echo "✅ Arquivo .env existe\n";
    $envContent = file_get_contents($envFile);
    // Esconder senhas
    $envContent = preg_replace('/(password|secret|key)\s*=\s*.+/i', '$1 = ***HIDDEN***', $envContent);
    echo "Primeiras 30 linhas:\n";
    echo str_repeat('-', 80) . "\n";
    $lines = explode("\n", $envContent);
    echo implode("\n", array_slice($lines, 0, 30));
} else {
    echo "❌ Arquivo .env NÃO existe\n";
}
echo "</pre>";

echo "<hr>";
echo "<p>✅ Debug concluído</p>";
