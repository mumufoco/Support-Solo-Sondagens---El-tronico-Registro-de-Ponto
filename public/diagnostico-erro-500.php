<?php
/**
 * Diagnóstico de Erro 500 - Servidor Compartilhado
 * Este arquivo testa o carregamento básico do CodeIgniter
 */

// Habilitar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔍 Diagnóstico de Erro 500</h1>";
echo "<hr>";

// 1. Verificar ROOTPATH
echo "<h2>1. Verificando Caminhos</h2>";
echo "<pre>";

$rootPath = dirname(__DIR__);
echo "ROOTPATH: $rootPath\n";
echo "FCPATH: " . __DIR__ . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";

if (!defined('ROOTPATH')) {
    define('ROOTPATH', $rootPath . DIRECTORY_SEPARATOR);
}
echo "ROOTPATH definido: " . ROOTPATH . "\n";

echo "</pre>";

// 2. Verificar arquivo autoload
echo "<h2>2. Verificando Autoloader do Composer</h2>";
echo "<pre>";

$autoloadFile = ROOTPATH . 'vendor/autoload.php';
echo "Arquivo: $autoloadFile\n";
echo "Existe: " . (file_exists($autoloadFile) ? 'SIM' : 'NÃO') . "\n";

if (file_exists($autoloadFile)) {
    echo "Tamanho: " . filesize($autoloadFile) . " bytes\n";
    echo "Tentando carregar...\n";

    try {
        require_once $autoloadFile;
        echo "✅ Autoload carregado com sucesso!\n";
    } catch (\Exception $e) {
        echo "❌ ERRO ao carregar autoload: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ Arquivo autoload.php NÃO EXISTE!\n";
    echo "Execute 'composer install' no servidor!\n";
}

echo "</pre>";

// 3. Verificar arquivo index.php
echo "<h2>3. Verificando public/index.php</h2>";
echo "<pre>";

$indexFile = __DIR__ . '/index.php';
echo "Arquivo: $indexFile\n";
echo "Existe: " . (file_exists($indexFile) ? 'SIM' : 'NÃO') . "\n";

if (file_exists($indexFile)) {
    echo "Tamanho: " . filesize($indexFile) . " bytes\n";
    echo "Permissões: " . substr(sprintf('%o', fileperms($indexFile)), -4) . "\n";

    // Verificar sintaxe do index.php
    $output = [];
    $return = 0;
    exec("php -l " . escapeshellarg($indexFile) . " 2>&1", $output, $return);
    echo "Verificação de sintaxe: " . ($return === 0 ? "✅ OK" : "❌ ERRO") . "\n";
    if ($return !== 0) {
        echo implode("\n", $output) . "\n";
    }
}

echo "</pre>";

// 4. Verificar .htaccess
echo "<h2>4. Verificando .htaccess</h2>";
echo "<pre>";

$htaccessFile = __DIR__ . '/.htaccess';
echo "Arquivo: $htaccessFile\n";
echo "Existe: " . (file_exists($htaccessFile) ? 'SIM' : 'NÃO') . "\n";

if (file_exists($htaccessFile)) {
    echo "Conteúdo:\n";
    echo str_repeat('-', 80) . "\n";
    echo file_get_contents($htaccessFile);
    echo "\n" . str_repeat('-', 80) . "\n";
}

echo "</pre>";

// 5. Verificar arquivo .env
echo "<h2>5. Verificando arquivo .env</h2>";
echo "<pre>";

$envFile = ROOTPATH . '.env';
echo "Arquivo: $envFile\n";
echo "Existe: " . (file_exists($envFile) ? 'SIM' : 'NÃO') . "\n";

if (file_exists($envFile)) {
    echo "Tamanho: " . filesize($envFile) . " bytes\n";
    echo "Permissões: " . substr(sprintf('%o', fileperms($envFile)), -4) . "\n";

    $envContent = file_get_contents($envFile);

    // Verificar configurações importantes
    echo "\nConfigurações encontradas:\n";
    echo "- CI_ENVIRONMENT: " . (strpos($envContent, 'CI_ENVIRONMENT') !== false ? 'SIM' : 'NÃO') . "\n";
    echo "- app.baseURL: " . (strpos($envContent, 'app.baseURL') !== false ? 'SIM' : 'NÃO') . "\n";
    echo "- database.default.hostname: " . (strpos($envContent, 'database.default.hostname') !== false ? 'SIM' : 'NÃO') . "\n";

    // Verificar placeholders
    if (strpos($envContent, 'PREENCHA_AQUI') !== false) {
        echo "\n⚠️ ATENÇÃO: Ainda existem placeholders PREENCHA_AQUI no arquivo!\n";
    }
}

echo "</pre>";

// 6. Testar carregamento do CodeIgniter
echo "<h2>6. Teste de Carregamento do CodeIgniter</h2>";
echo "<pre>";

try {
    // Definir constantes necessárias
    if (!defined('FCPATH')) {
        define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
    }

    if (!defined('SYSTEMPATH')) {
        define('SYSTEMPATH', ROOTPATH . 'vendor/codeigniter4/framework/system/');
    }

    if (!defined('APPPATH')) {
        define('APPPATH', ROOTPATH . 'app/');
    }

    if (!defined('WRITEPATH')) {
        define('WRITEPATH', ROOTPATH . 'writable/');
    }

    echo "Constantes definidas:\n";
    echo "- ROOTPATH: " . ROOTPATH . "\n";
    echo "- FCPATH: " . FCPATH . "\n";
    echo "- SYSTEMPATH: " . SYSTEMPATH . "\n";
    echo "- APPPATH: " . APPPATH . "\n";
    echo "- WRITEPATH: " . WRITEPATH . "\n\n";

    // Verificar se os diretórios existem
    echo "Verificando diretórios:\n";
    echo "- SYSTEMPATH existe: " . (is_dir(SYSTEMPATH) ? 'SIM' : 'NÃO') . "\n";
    echo "- APPPATH existe: " . (is_dir(APPPATH) ? 'SIM' : 'NÃO') . "\n";
    echo "- WRITEPATH existe: " . (is_dir(WRITEPATH) ? 'SIM' : 'NÃO') . "\n\n";

    // Tentar carregar Bootstrap do CodeIgniter
    $bootstrapFile = SYSTEMPATH . 'Config/Routes.php';
    echo "Tentando localizar Bootstrap...\n";
    echo "Arquivo Routes.php: " . (file_exists($bootstrapFile) ? 'SIM' : 'NÃO') . "\n";

    if (file_exists($autoloadFile)) {
        echo "\n✅ Estrutura básica OK!\n";
        echo "Se ainda há erro 500, o problema está na execução do CodeIgniter.\n";
    } else {
        echo "\n❌ Estrutura incompleta!\n";
    }

} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

// 7. Verificar logs de erro do PHP
echo "<h2>7. Logs de Erro do PHP</h2>";
echo "<pre>";

$phpErrorLog = ini_get('error_log');
echo "PHP error_log configurado: " . ($phpErrorLog ?: 'default') . "\n";

// Verificar logs do CodeIgniter
$logDir = WRITEPATH . 'logs';
echo "\nDiretório de logs do CodeIgniter: $logDir\n";
echo "Existe: " . (is_dir($logDir) ? 'SIM' : 'NÃO') . "\n";

if (is_dir($logDir)) {
    $logFiles = glob($logDir . '/log-*.log');
    echo "Arquivos de log encontrados: " . count($logFiles) . "\n\n";

    if ($logFiles) {
        rsort($logFiles);
        $latestLog = $logFiles[0];
        echo "Último arquivo de log: " . basename($latestLog) . "\n";
        echo "Tamanho: " . filesize($latestLog) . " bytes\n\n";

        echo "Últimas 30 linhas:\n";
        echo str_repeat('-', 80) . "\n";

        $lines = file($latestLog);
        $lastLines = array_slice($lines, -30);
        echo implode('', $lastLines);

        echo str_repeat('-', 80) . "\n";
    } else {
        echo "Nenhum arquivo de log encontrado.\n";
    }
}

echo "</pre>";

// 8. Informações do servidor
echo "<h2>8. Informações do Servidor</h2>";
echo "<pre>";

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Server API: " . php_sapi_name() . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";

echo "\nExtensões carregadas:\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "- $ext\n";
}

echo "</pre>";

echo "<hr>";
echo "<h2>✅ Diagnóstico Concluído</h2>";
echo "<p>Revise os erros acima para identificar o problema.</p>";
echo "<p><a href='/checagem-instalacao.php'>← Voltar para Checagem de Instalação</a></p>";
