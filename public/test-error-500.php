<?php
/**
 * Script de Diagnóstico de Erro 500
 * Tenta inicializar o CodeIgniter e capturar erros
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Diagnóstico de Erro 500 - CodeIgniter</h1>";
echo "<hr>";

// Verificar estrutura básica
echo "<h3>1. Verificações Básicas</h3>";

$checks = [
    'vendor/autoload.php' => dirname(__DIR__) . '/vendor/autoload.php',
    'app/Config/Paths.php' => dirname(__DIR__) . '/app/Config/Paths.php',
    '.env' => dirname(__DIR__) . '/.env',
    'spark' => dirname(__DIR__) . '/spark',
];

foreach ($checks as $name => $path) {
    $exists = file_exists($path);
    echo ($exists ? "✅" : "❌") . " $name " . ($exists ? "(encontrado)" : "(NÃO ENCONTRADO)") . "<br>";
}

echo "<hr>";
echo "<h3>2. Tentando Carregar Autoload</h3>";

try {
    require dirname(__DIR__) . '/vendor/autoload.php';
    echo "✅ Autoload carregado com sucesso<br>";
} catch (Exception $e) {
    echo "❌ Erro ao carregar autoload: " . $e->getMessage() . "<br>";
    die();
}

echo "<hr>";
echo "<h3>3. Verificando Constantes do CodeIgniter</h3>";

// Definir constantes necessárias
if (!defined('FCPATH')) {
    define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
    echo "✅ FCPATH definido: " . FCPATH . "<br>";
}

// Carregar Paths
try {
    require dirname(__DIR__) . '/app/Config/Paths.php';
    $paths = new Config\Paths();
    echo "✅ Paths carregado<br>";

    if (!defined('APPPATH')) {
        define('APPPATH', realpath($paths->appDirectory) . DIRECTORY_SEPARATOR);
        echo "✅ APPPATH definido: " . APPPATH . "<br>";
    }

    if (!defined('WRITEPATH')) {
        define('WRITEPATH', realpath($paths->writableDirectory) . DIRECTORY_SEPARATOR);
        echo "✅ WRITEPATH definido: " . WRITEPATH . "<br>";
    }

    if (!defined('SYSTEMPATH')) {
        define('SYSTEMPATH', realpath($paths->systemDirectory) . DIRECTORY_SEPARATOR);
        echo "✅ SYSTEMPATH definido: " . SYSTEMPATH . "<br>";
    }

    if (!defined('ROOTPATH')) {
        define('ROOTPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
        echo "✅ ROOTPATH definido: " . ROOTPATH . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao carregar Paths: " . $e->getMessage() . "<br>";
    die();
}

echo "<hr>";
echo "<h3>4. Verificando Permissões de Diretórios</h3>";

$writableDirs = [
    'writable/cache',
    'writable/logs',
    'writable/session',
    'writable/uploads',
];

foreach ($writableDirs as $dir) {
    $fullPath = ROOTPATH . $dir;
    $exists = is_dir($fullPath);
    $writable = $exists ? is_writable($fullPath) : false;

    if (!$exists) {
        echo "❌ $dir - NÃO EXISTE<br>";
    } elseif (!$writable) {
        echo "⚠️ $dir - EXISTE mas NÃO É GRAVÁVEL<br>";
    } else {
        echo "✅ $dir - OK (gravável)<br>";
    }
}

echo "<hr>";
echo "<h3>5. Carregando Bootstrap do CodeIgniter</h3>";

try {
    require SYSTEMPATH . 'bootstrap.php';
    echo "✅ Bootstrap carregado<br>";
} catch (Exception $e) {
    echo "❌ Erro no bootstrap: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

echo "<hr>";
echo "<h3>6. Tentando Criar Aplicação CodeIgniter</h3>";

try {
    $app = Config\Services::codeigniter();
    echo "✅ Aplicação CodeIgniter criada<br>";

    // Tentar obter configuração
    $config = config('App');
    echo "✅ Configuração App carregada<br>";
    echo "BaseURL: " . $config->baseURL . "<br>";
    echo "Environment: " . ENVIRONMENT . "<br>";

} catch (Exception $e) {
    echo "❌ ERRO AO CRIAR APLICAÇÃO:<br>";
    echo "<strong>Tipo:</strong> " . get_class($e) . "<br>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . " (linha " . $e->getLine() . ")<br>";
    echo "<h4>Stack Trace:</h4>";
    echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;'>" . $e->getTraceAsString() . "</pre>";
    die();
}

echo "<hr>";
echo "<h3>7. Testando Conexão com Banco de Dados</h3>";

try {
    $db = \Config\Database::connect();
    echo "✅ Objeto de conexão criado<br>";

    // Tentar query simples
    $query = $db->query("SELECT 1 as test");
    $result = $query->getRow();

    if ($result && $result->test == 1) {
        echo "✅ Conexão com banco de dados FUNCIONANDO<br>";

        // Listar tabelas
        $tables = $db->listTables();
        echo "Total de tabelas: " . count($tables) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ ERRO DE BANCO DE DADOS:<br>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>8. Tentando Executar Request Completo</h3>";

try {
    $request = \Config\Services::request();
    $response = \Config\Services::response();

    echo "✅ Request e Response criados<br>";

    // Tentar processar uma rota simples
    echo "Tentando processar rota '/'...<br>";

} catch (Exception $e) {
    echo "❌ ERRO:<br>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>✅ Diagnóstico Concluído</h3>";
echo "<p>Se você chegou até aqui sem erros críticos, o problema pode estar em:</p>";
echo "<ul>";
echo "<li>Configuração do servidor web (Apache/Nginx)</li>";
echo "<li>Arquivo .htaccess</li>";
echo "<li>Permissões de arquivos</li>";
echo "<li>Controllers específicos</li>";
echo "<li>Rotas mal configuradas</li>";
echo "</ul>";

echo "<hr>";
echo "<small>Teste concluído em " . date('Y-m-d H:i:s') . "</small>";
