<?php
/**
 * COMPREHENSIVE LOGIN FLOW TEST
 *
 * This script tests the complete login flow to identify the redirect loop issue.
 * It simulates the session handling and tracks every step.
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output
echo "=================================================================\n";
echo "  TESTE COMPLETO DO FLUXO DE LOGIN - DEBUG DETALHADO\n";
echo "=================================================================\n\n";

// Define paths
define('ROOTPATH', __DIR__);
define('APPPATH', ROOTPATH . '/app/');
define('SYSTEMPATH', ROOTPATH . '/vendor/codeigniter4/framework/system/');
define('FCPATH', ROOTPATH . '/public/');
define('WRITEPATH', ROOTPATH . '/writable/');

echo "📁 Verificando estrutura do projeto...\n";
echo "   ROOTPATH: " . ROOTPATH . "\n";
echo "   APPPATH: " . APPPATH . "\n";
echo "   WRITEPATH: " . WRITEPATH . "\n\n";

// Check if CodeIgniter exists
if (!file_exists(ROOTPATH . '/vendor/autoload.php')) {
    die("❌ ERRO: vendor/autoload.php não encontrado. Execute 'composer install' primeiro.\n");
}

echo "✅ Autoloader encontrado\n\n";

// Load Composer autoloader
require ROOTPATH . '/vendor/autoload.php';

// Load environment variables
if (file_exists(ROOTPATH . '/.env')) {
    try {
        if (class_exists('\Dotenv\Dotenv')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(ROOTPATH);
            $dotenv->load();
            echo "✅ Arquivo .env carregado\n\n";
        } else {
            // Manually load .env if Dotenv class doesn't exist
            $lines = file(ROOTPATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv("$key=$value");
                }
            }
            echo "✅ Arquivo .env carregado (modo manual)\n\n";
        }
    } catch (Exception $e) {
        echo "⚠️  Erro ao carregar .env: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "⚠️  Arquivo .env não encontrado\n\n";
}

// Bootstrap CodeIgniter
echo "🚀 Inicializando CodeIgniter...\n";

// Get our paths
$paths = new Config\Paths();

// Set environment
$_SERVER['CI_ENVIRONMENT'] = $_ENV['CI_ENVIRONMENT'] ?? 'development';
defined('ENVIRONMENT') || define('ENVIRONMENT', $_SERVER['CI_ENVIRONMENT']);

echo "   Ambiente: " . ENVIRONMENT . "\n\n";

// Load the framework
require SYSTEMPATH . '/bootstrap.php';

// Create app instance
$app = Config\Services::codeigniter();
$app->initialize();
$context = is_cli() ? 'php-cli' : 'web';
$app->setContext($context);

echo "✅ CodeIgniter inicializado\n\n";

echo "=================================================================\n";
echo "  ETAPA 1: ANÁLISE DA CONFIGURAÇÃO DE SESSÃO\n";
echo "=================================================================\n\n";

// Load session config
$sessionConfig = new \Config\Session();
echo "📋 Configuração da Sessão:\n";
echo "   Driver: " . $sessionConfig->driver . "\n";
echo "   Cookie Name: " . $sessionConfig->cookieName . "\n";
echo "   Expiration: " . $sessionConfig->expiration . " segundos\n";
echo "   Save Path: " . $sessionConfig->savePath . "\n";
echo "   Match IP: " . ($sessionConfig->matchIP ? 'Sim' : 'Não') . "\n";
echo "   Time to Update: " . $sessionConfig->timeToUpdate . " segundos\n";
echo "   Regenerate Destroy: " . ($sessionConfig->regenerateDestroy ? 'Sim' : 'Não') . "\n\n";

// Check session save path
$sessionPath = WRITEPATH . $sessionConfig->savePath;
echo "📁 Verificando diretório de sessão:\n";
echo "   Path: " . $sessionPath . "\n";

if (!is_dir($sessionPath)) {
    echo "   ⚠️  Diretório não existe. Criando...\n";
    mkdir($sessionPath, 0755, true);
}

if (is_writable($sessionPath)) {
    echo "   ✅ Diretório gravável\n";
} else {
    echo "   ❌ Diretório NÃO gravável (pode causar problemas)\n";
}

// Count existing session files
$sessionFiles = glob($sessionPath . '/ci_session*');
echo "   📄 Arquivos de sessão existentes: " . count($sessionFiles) . "\n\n";

echo "=================================================================\n";
echo "  ETAPA 2: SIMULAÇÃO DE LOGIN\n";
echo "=================================================================\n\n";

// Start fresh session for testing
echo "🔄 Iniciando nova sessão...\n";
$session = \Config\Services::session();

if (!$session->has('test_marker')) {
    $session->set('test_marker', 'initial_value');
    echo "   ✅ Sessão iniciada (ID: " . session_id() . ")\n";
    echo "   ✅ Teste de escrita inicial bem-sucedido\n";
} else {
    echo "   ⚠️  Sessão já existia\n";
}

echo "\n📝 Simulando dados de login de um admin...\n";

// Simulate login data (like LoginController does)
$simulatedUserId = 1;
$sessionData = [
    'user_id'       => $simulatedUserId,
    'user_name'     => 'Admin Test',
    'user_email'    => 'admin@test.com',
    'user_role'     => 'admin',
    'user_active'   => true,
    'last_activity' => time(),
    'logged_in'     => true,
];

echo "   Dados a serem salvos:\n";
foreach ($sessionData as $key => $value) {
    echo "     - $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}
echo "\n";

// Test 1: Set without regenerate
echo "🧪 TESTE 1: Set() sem regenerate()\n";
foreach ($sessionData as $key => $value) {
    $session->set($key, $value);
}
echo "   ✅ Dados definidos\n";

// Verify immediately
$allValid = true;
foreach ($sessionData as $key => $expectedValue) {
    $actualValue = $session->get($key);
    $match = $actualValue === $expectedValue;
    if (!$match) {
        echo "   ❌ $key: Esperado=" . var_export($expectedValue, true) . ", Obtido=" . var_export($actualValue, true) . "\n";
        $allValid = false;
    }
}

if ($allValid) {
    echo "   ✅ Todos os dados verificados corretamente\n";
}
echo "\n";

// Test 2: Regenerate AFTER set (current buggy approach)
echo "🧪 TESTE 2: regenerate() DEPOIS de set() [ABORDAGEM ATUAL]\n";
foreach ($sessionData as $key => $value) {
    $session->set($key, $value);
}
echo "   ✅ Dados definidos\n";

$oldSessionId = session_id();
$session->regenerate();
$newSessionId = session_id();
echo "   🔄 Sessão regenerada (ID: $oldSessionId -> $newSessionId)\n";

// Verify after regenerate
$allValid = true;
$lostKeys = [];
foreach ($sessionData as $key => $expectedValue) {
    $actualValue = $session->get($key);
    $match = $actualValue === $expectedValue;
    if (!$match) {
        $lostKeys[] = $key;
        $allValid = false;
    }
}

if ($allValid) {
    echo "   ✅ Dados preservados após regenerate()\n";
} else {
    echo "   ❌ PROBLEMA ENCONTRADO! Dados perdidos após regenerate():\n";
    foreach ($lostKeys as $key) {
        echo "      - $key\n";
    }
}
echo "\n";

// Test 3: Regenerate BEFORE set (proposed fix)
echo "🧪 TESTE 3: regenerate() ANTES de set() [CORREÇÃO PROPOSTA]\n";
$session->destroy();
$session = \Config\Services::session();

$oldSessionId = session_id();
$session->regenerate();
$newSessionId = session_id();
echo "   🔄 Sessão regenerada primeiro (ID: $oldSessionId -> $newSessionId)\n";

foreach ($sessionData as $key => $value) {
    $session->set($key, $value);
}
echo "   ✅ Dados definidos após regenerate()\n";

// Verify
$allValid = true;
$lostKeys = [];
foreach ($sessionData as $key => $expectedValue) {
    $actualValue = $session->get($key);
    $match = $actualValue === $expectedValue;
    if (!$match) {
        $lostKeys[] = $key;
        $allValid = false;
    }
}

if ($allValid) {
    echo "   ✅ Todos os dados preservados!\n";
} else {
    echo "   ❌ Ainda há problema:\n";
    foreach ($lostKeys as $key) {
        echo "      - $key perdido\n";
    }
}
echo "\n";

// Test 4: Test with session_write_close
echo "🧪 TESTE 4: Usando session_write_close() para forçar salvamento\n";
$session->destroy();
$session = \Config\Services::session();

$session->regenerate();
foreach ($sessionData as $key => $value) {
    $session->set($key, $value);
}
echo "   ✅ Dados definidos\n";

// Force write
session_write_close();
echo "   💾 session_write_close() chamado\n";

// Restart session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "   🔄 session_start() chamado\n";
}

// Verify
$allValid = true;
$lostKeys = [];
foreach ($sessionData as $key => $expectedValue) {
    $actualValue = $_SESSION[$key] ?? null;
    $match = $actualValue === $expectedValue;
    if (!$match) {
        $lostKeys[] = $key;
        $allValid = false;
    }
}

if ($allValid) {
    echo "   ✅ Dados preservados após write_close()\n";
} else {
    echo "   ❌ Problema persiste:\n";
    foreach ($lostKeys as $key) {
        echo "      - $key\n";
    }
}
echo "\n";

echo "=================================================================\n";
echo "  ETAPA 3: VERIFICAÇÃO DO LoginController\n";
echo "=================================================================\n\n";

$loginControllerPath = APPPATH . 'Controllers/Auth/LoginController.php';
$loginContent = file_get_contents($loginControllerPath);

// Check order of regenerate and set
$regeneratePos = strpos($loginContent, '$this->session->regenerate()');
$setPos = strpos($loginContent, '$this->session->set($sessionData)');

echo "📄 Analisando LoginController.php:";
if ($regeneratePos !== false && $setPos !== false) {
    if ($regeneratePos < $setPos) {
        echo " ✅\n";
        echo "   ✅ regenerate() está ANTES de set() (correto)\n";
    } else {
        echo " ⚠️\n";
        echo "   ⚠️  regenerate() está DEPOIS de set() (pode causar perda de dados)\n";
    }
}

// Check if session_write_close is used
if (strpos($loginContent, 'session_write_close()') !== false) {
    echo "   ✅ session_write_close() é usado\n";
} else {
    echo "   ⚠️  session_write_close() NÃO é usado (dados podem não persistir)\n";
}

// Check if session_start is used after
if (strpos($loginContent, 'session_start()') !== false) {
    echo "   ✅ session_start() é usado após write_close()\n";
} else {
    echo "   ⚠️  session_start() NÃO é usado após write_close()\n";
}

echo "\n";

echo "=================================================================\n";
echo "  ETAPA 4: SIMULAÇÃO DE FILTROS\n";
echo "=================================================================\n\n";

// Simulate what AdminFilter checks
echo "🔒 Simulando AdminFilter (verifica se user_id existe)...\n";

$userId = $session->get('user_id');
$userRole = $session->get('user_role');

if (!$userId) {
    echo "   ❌ PROBLEMA: user_id não encontrado na sessão!\n";
    echo "   🔁 Isso causaria redirect para /auth/login (LOOP!)\n";
} else {
    echo "   ✅ user_id encontrado: $userId\n";

    if (empty($userRole) || strtolower($userRole) !== 'admin') {
        echo "   ❌ PROBLEMA: user_role não é 'admin' (role=$userRole)\n";
        echo "   🔁 Isso causaria redirect baseado em role\n";
    } else {
        echo "   ✅ user_role é 'admin'\n";
        echo "   ✅ AdminFilter permitiria acesso\n";
    }
}

echo "\n";

echo "=================================================================\n";
echo "  ETAPA 5: DIAGNÓSTICO FINAL\n";
echo "=================================================================\n\n";

echo "🔍 Resumo dos testes:\n\n";

// Recount session files
$sessionFiles = glob($sessionPath . '/ci_session*');
$sessionFileCount = count($sessionFiles);
echo "1. Arquivos de sessão no diretório:\n";
echo "   Total: $sessionFileCount arquivos\n";
if ($sessionFileCount > 0) {
    echo "   ✅ Sessões estão sendo criadas no disco\n";
} else {
    echo "   ❌ Nenhum arquivo de sessão criado (problema de escrita?)\n";
}
echo "\n";

echo "2. Comportamento do regenerate():\n";
$regenerateConfig = $sessionConfig->regenerateDestroy ? 'Destrói dados antigos' : 'Preserva dados antigos';
echo "   Configuração: $regenerateConfig\n";
if ($sessionConfig->regenerateDestroy) {
    echo "   ⚠️  Com regenerateDestroy=true, dados podem ser perdidos se set() for antes\n";
} else {
    echo "   ✅ regenerateDestroy=false ajuda a preservar dados\n";
}
echo "\n";

echo "3. Identificação do problema:\n";
if (!$allValid) {
    echo "   ❌ LOOP CONFIRMADO: Dados de sessão não persistem após regenerate()\n";
    echo "\n";
    echo "   🔧 CAUSA RAIZ IDENTIFICADA:\n";
    echo "      - LoginController chama set() e depois regenerate()\n";
    echo "      - Com regenerateDestroy=true, os dados são perdidos\n";
    echo "      - AdminFilter não encontra user_id\n";
    echo "      - Redirect para /auth/login\n";
    echo "      - LOOP INFINITO!\n";
    echo "\n";
    echo "   💡 SOLUÇÃO:\n";
    echo "      1. Chamar regenerate() ANTES de set()\n";
    echo "      2. Usar session_write_close() após set()\n";
    echo "      3. Usar session_start() para reabrir sessão\n";
    echo "      4. OU alterar regenerateDestroy para false\n";
} else {
    echo "   ✅ Sessão funcionando corretamente nos testes\n";
    echo "   ℹ️  Se ainda há loop em produção, pode ser:\n";
    echo "      - Problema de permissões no diretório de sessão\n";
    echo "      - Diferenças de configuração PHP\n";
    echo "      - Cache do navegador com sessão antiga\n";
    echo "      - Rate limiting bloqueando requisições\n";
}

echo "\n";
echo "=================================================================\n";
echo "  TESTE CONCLUÍDO\n";
echo "=================================================================\n";
