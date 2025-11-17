<?php
/**
 * APLICAR TODAS AS CORREÇÕES - Via Navegador
 *
 * Este script aplica TODAS as correções críticas automaticamente
 * Acesse: http://ponto.supportsondagens.com.br/apply-all-fixes.php
 *
 * IMPORTANTE: DELETE ESTE ARQUIVO APÓS USO!
 */

// Security: Prevent multiple executions
$lockFile = __DIR__ . '/../writable/apply-fixes.lock';
if (file_exists($lockFile)) {
    $lastRun = (int)file_get_contents($lockFile);
    if (time() - $lastRun < 1800) {
        die('Script executado recentemente. Aguarde 30 minutos ou delete writable/apply-fixes.lock');
    }
}

$fixes = [];
$errors = [];
$warnings = [];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicar Todas as Correções</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; line-height: 1.6; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-top: 0; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .step { background: #ecf0f1; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #3498db; }
        .ok { background: #d5f4e6; border-left-color: #27ae60; }
        .error { background: #fadbd8; border-left-color: #e74c3c; }
        .warning { background: #fef5e7; border-left-color: #f39c12; }
        code { background: #34495e; color: #ecf0f1; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 13px; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; max-height: 300px; overflow-y: auto; }
        .btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; cursor: pointer; border: none; font-size: 14px; }
        .btn:hover { background: #2980b9; }
        .btn-green { background: #27ae60; }
        .btn-green:hover { background: #229954; }
        .btn-red { background: #e74c3c; }
        .btn-red:hover { background: #c0392b; }
        .progress { background: #ecf0f1; height: 30px; border-radius: 15px; overflow: hidden; margin: 20px 0; }
        .progress-bar { background: #3498db; height: 100%; line-height: 30px; color: white; text-align: center; transition: width 0.3s; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Aplicar Todas as Correções</h1>
        <p><strong>Sistema:</strong> Ponto Eletrônico - CodeIgniter 4</p>
        <p><strong>Ação:</strong> Aplicar todas as correções críticas automaticamente</p>

<?php

$rootPath = __DIR__ . '/..';
$totalSteps = 8;
$currentStep = 0;

// ============================================================================
// STEP 1: Backup do index.php atual
// ============================================================================
$currentStep++;
echo '<div class="step">';
echo '<strong>Step ' . $currentStep . '/' . $totalSteps . ': Backup do index.php</strong><br>';

$indexFile = __DIR__ . '/index.php';
if (file_exists($indexFile)) {
    $backupFile = __DIR__ . '/index.php.backup.' . date('YmdHis');
    if (@copy($indexFile, $backupFile)) {
        echo '✅ Backup criado: ' . basename($backupFile) . '<br>';
        $fixes[] = 'Backup do index.php criado';
    } else {
        echo '⚠️ Não foi possível criar backup (continuando...)<br>';
        $warnings[] = 'Backup não criado';
    }
} else {
    echo '❌ index.php não encontrado!<br>';
    $errors[] = 'index.php missing';
}
echo '</div>';

// ============================================================================
// STEP 2: Criar/Atualizar public/index.php
// ============================================================================
$currentStep++;
echo '<div class="step">';
echo '<strong>Step ' . $currentStep . '/' . $totalSteps . ': Atualizar public/index.php</strong><br>';

$indexContent = <<<'PHPINDEX'
<?php

/**
 * Sistema de Ponto Eletrônico Brasileiro
 *
 * Entry point for the application
 *
 * @package    PontoEletronico
 * @author     Mumufoco Team
 * @copyright  2024 Mumufoco
 * @license    MIT
 * @link       https://github.com/mumufoco/ponto-eletronico
 */

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.1'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * DEFINE ENVIRONMENT CONSTANT EARLY
 *---------------------------------------------------------------
 */

// Define ENVIRONMENT constant before anything else to prevent "Undefined constant" errors
// This is normally done by Boot.php, but we need it earlier for error handling
if (!defined('ENVIRONMENT')) {
    // Try to load from .env file
    $envFile = __DIR__ . '/../.env';
    $environment = 'production'; // Default to production for safety

    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        if (preg_match('/^\s*CI_ENVIRONMENT\s*=\s*["\']?(\w+)["\']?\s*$/m', $envContent, $matches)) {
            $environment = $matches[1];
        }
    }

    define('ENVIRONMENT', $environment);
}

/*
 *---------------------------------------------------------------
 * LOAD PRODUCTION PHP CONFIGURATION
 *---------------------------------------------------------------
 */

// Force critical PHP settings for production environment
// This ensures session cookies are secure even if .htaccess/.user.ini don't work
if (file_exists(__DIR__ . '/php-config-production.php')) {
    require __DIR__ . '/php-config-production.php';
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// LOAD OUR PATHS CONFIG FILE
// This is the line that might need to be changed, depending on your folder structure.
require FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder

$paths = new Paths();

// LOAD COMPOSER AUTOLOADER
// This must be loaded before Boot.php to ensure all classes are available
$composerAutoload = FCPATH . '../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
}

// LOAD EXCEPTION CLASSES BEFORE BOOT
// Load critical exception classes manually before Boot.php to prevent
// "InvalidArgumentException not found" errors during DotEnv initialization
if (file_exists(__DIR__ . '/bootstrap-exceptions.php')) {
    require __DIR__ . '/bootstrap-exceptions.php';
}

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
PHPINDEX;

if (@file_put_contents($indexFile, $indexContent)) {
    echo '✅ index.php atualizado com todas as correções<br>';
    echo '  ✓ ENVIRONMENT constant definida<br>';
    echo '  ✓ Carrega php-config-production.php<br>';
    echo '  ✓ Carrega bootstrap-exceptions.php<br>';
    echo '  ✓ Usa Boot::bootWeb (não bootstrap.php)<br>';
    $fixes[] = 'index.php atualizado';
} else {
    echo '❌ Falha ao atualizar index.php (sem permissão)<br>';
    $errors[] = 'Cannot write index.php';
}
echo '</div>';

// ============================================================================
// STEP 3: Criar public/bootstrap-exceptions.php
// ============================================================================
$currentStep++;
echo '<div class="step">';
echo '<strong>Step ' . $currentStep . '/' . $totalSteps . ': Criar bootstrap-exceptions.php</strong><br>';

$bootstrapContent = <<<'PHPBOOTSTRAP'
<?php
/**
 * Emergency Exception Classes Loader
 *
 * This file manually loads critical exception classes that may not be
 * autoloaded correctly before DotEnv initialization.
 *
 * LOAD THIS BEFORE: Boot::bootWeb()
 */

// Get the system path
$systemPath = __DIR__ . '/../vendor/codeigniter4/framework/system';

// CRITICAL: Ensure writable/session directory exists before framework boots
// This prevents "Unable to create file writable/session/ci_session..." errors
$sessionPath = __DIR__ . '/../writable/session';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0777, true);
    @chmod($sessionPath, 0777);
}

// Critical exception classes that MUST be loaded before DotEnv
$criticalClasses = [
    'CodeIgniter\Exceptions\ExceptionInterface' => '/Exceptions/ExceptionInterface.php',
    'CodeIgniter\Exceptions\DebugTraceableTrait' => '/Exceptions/DebugTraceableTrait.php',
    'CodeIgniter\Exceptions\HTTPExceptionInterface' => '/Exceptions/HTTPExceptionInterface.php',
    'CodeIgniter\Exceptions\HasExitCodeInterface' => '/Exceptions/HasExitCodeInterface.php',
    'CodeIgniter\Exceptions\FrameworkException' => '/Exceptions/FrameworkException.php',
    'CodeIgniter\Exceptions\InvalidArgumentException' => '/Exceptions/InvalidArgumentException.php',
    'CodeIgniter\Exceptions\CriticalError' => '/Exceptions/CriticalError.php',
    'CodeIgniter\Exceptions\ConfigException' => '/Exceptions/ConfigException.php',
    'CodeIgniter\Exceptions\LogicException' => '/Exceptions/LogicException.php',
    'CodeIgniter\Exceptions\RuntimeException' => '/Exceptions/RuntimeException.php',
];

// Load each class if not already loaded
foreach ($criticalClasses as $className => $filePath) {
    if (!class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {
        $fullPath = $systemPath . $filePath;
        if (file_exists($fullPath)) {
            require_once $fullPath;
        }
    }
}

// Verify critical classes are now available
if (!class_exists('CodeIgniter\Exceptions\InvalidArgumentException')) {
    die('CRITICAL ERROR: Cannot load InvalidArgumentException class. Check vendor/codeigniter4/framework/system/Exceptions/ directory.');
}
PHPBOOTSTRAP;

$bootstrapFile = __DIR__ . '/bootstrap-exceptions.php';
if (@file_put_contents($bootstrapFile, $bootstrapContent)) {
    echo '✅ bootstrap-exceptions.php criado<br>';
    echo '  ✓ Cria writable/session automaticamente<br>';
    echo '  ✓ Carrega 10 classes de exceção<br>';
    $fixes[] = 'bootstrap-exceptions.php criado';
} else {
    echo '❌ Falha ao criar bootstrap-exceptions.php<br>';
    $errors[] = 'Cannot create bootstrap-exceptions.php';
}
echo '</div>';

// ============================================================================
// STEP 4: Criar public/php-config-production.php
// ============================================================================
$currentStep++;
echo '<div class="step">';
echo '<strong>Step ' . $currentStep . '/' . $totalSteps . ': Criar php-config-production.php</strong><br>';

$phpConfigContent = file_get_contents($rootPath . '/public/php-config-production.php');
if ($phpConfigContent === false) {
    // Se não existe, criar com conteúdo padrão
    $phpConfigContent = <<<'PHPCONFIG'
<?php
/**
 * Production PHP Configuration
 *
 * This file forces critical PHP settings at runtime using ini_set().
 */

// Session save path - use project directory
$sessionPath = __DIR__ . '/../writable/session';

// Create directory if it doesn't exist
if (!is_dir($sessionPath)) {
    if (@mkdir($sessionPath, 0777, true)) {
        @chmod($sessionPath, 0777);

        $indexFile = $sessionPath . '/index.html';
        if (!file_exists($indexFile)) {
            @file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
        }

        $htaccessFile = $sessionPath . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            @file_put_contents($htaccessFile, "Deny from all\n");
        }
    }
}

// Ensure directory has correct permissions
if (is_dir($sessionPath)) {
    @chmod($sessionPath, 0777);
}

// Set session save path
if (is_dir($sessionPath)) {
    ini_set('session.save_path', $sessionPath);
}

// Force HTTPS-only cookies
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

// Session garbage collector
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
ini_set('session.gc_maxlifetime', '7200');

// Error handling
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$errorLogPath = __DIR__ . '/../writable/logs/php-errors.log';
ini_set('error_log', $errorLogPath);

// Performance
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');

// Timezone
date_default_timezone_set('America/Sao_Paulo');
PHPCONFIG;

    $phpConfigFile = __DIR__ . '/php-config-production.php';
    if (@file_put_contents($phpConfigFile, $phpConfigContent)) {
        echo '✅ php-config-production.php criado<br>';
        $fixes[] = 'php-config-production.php criado';
    } else {
        echo '❌ Falha ao criar php-config-production.php<br>';
        $errors[] = 'Cannot create php-config-production.php';
    }
} else {
    echo '✅ php-config-production.php já existe<br>';
}
echo '</div>';

// ============================================================================
// STEP 5: Verificar/Corrigir app/Config/Paths.php
// ============================================================================
$currentStep++;
echo '<div class="step">';
echo '<strong>Step ' . $currentStep . '/' . $totalSteps . ': Verificar Paths.php</strong><br>';

$pathsFile = $rootPath . '/app/Config/Paths.php';
if (file_exists($pathsFile)) {
    $pathsContent = file_get_contents($pathsFile);

    if (strpos($pathsContent, "'/../../storage'") !== false || strpos($pathsContent, '"/../../storage"') !== false) {
        echo '⚠️ Paths.php usa "storage" - corrigindo para "writable"...<br>';

        $pathsContent = str_replace("'/../../storage'", "'/../../writable'", $pathsContent);
        $pathsContent = str_replace('"/../../storage"', '"/../../writable"', $pathsContent);

        if (@file_put_contents($pathsFile, $pathsContent)) {
            echo '✅ Paths.php corrigido (storage → writable)<br>';
            $fixes[] = 'Paths.php corrigido';
        } else {
            echo '❌ Falha ao corrigir Paths.php<br>';
            $errors[] = 'Cannot update Paths.php';
        }
    } else {
        echo '✅ Paths.php já usa "writable" (correto)<br>';
    }
} else {
    echo '❌ Paths.php não encontrado!<br>';
    $errors[] = 'Paths.php missing';
}
echo '</div>';

// ============================================================================
// STEP 6: Criar todos os diretórios writable
// ============================================================================
$currentStep++;
echo '<div class="step">';
echo '<strong>Step ' . $currentStep . '/' . $totalSteps . ': Criar diretórios writable</strong><br>';

$directories = [
    'writable',
    'writable/session',
    'writable/cache',
    'writable/cache/data',
    'writable/logs',
    'writable/uploads',
    'writable/debugbar',
    'writable/biometric',
    'writable/biometric/faces',
    'writable/biometric/fingerprints',
    'writable/exports',
];

$createdCount = 0;
foreach ($directories as $dir) {
    $fullPath = $rootPath . '/' . $dir;
    if (!is_dir($fullPath)) {
        if (@mkdir($fullPath, 0777, true)) {
            $createdCount++;
        }
    }
    @chmod($fullPath, 0777);
}

echo '✅ Criados/verificados ' . count($directories) . ' diretórios<br>';
echo '✅ Permissões 777 aplicadas<br>';
$fixes[] = 'Diretórios writable criados';
echo '</div>';

// ============================================================================
// STEP 7: Verificar .env
// ============================================================================
$currentStep++;
echo '<div class="step">';
echo '<strong>Step ' . $currentStep . '/' . $totalSteps . ': Verificar .env</strong><br>';

$envFile = $rootPath . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);

    $checks = [
        'CI_ENVIRONMENT' => preg_match('/CI_ENVIRONMENT\s*=/', $envContent),
        'session.savePath' => preg_match('/session\.savePath\s*=\s*writable\/session/', $envContent),
    ];

    foreach ($checks as $key => $found) {
        if ($found) {
            echo '✅ ' . $key . ' configurado<br>';
        } else {
            echo '⚠️ ' . $key . ' não encontrado no .env<br>';
            $warnings[] = $key . ' missing in .env';
        }
    }
} else {
    echo '❌ .env não encontrado!<br>';
    $errors[] = '.env missing';
}
echo '</div>';

// ============================================================================
// STEP 8: Limpar caches
// ============================================================================
$currentStep++;
echo '<div class="step">';
echo '<strong>Step ' . $currentStep . '/' . $totalSteps . ': Limpar caches</strong><br>';

$cacheFiles = glob($rootPath . '/writable/cache/*');
$debugbarFiles = glob($rootPath . '/writable/debugbar/*');
$deleted = 0;

foreach (array_merge($cacheFiles, $debugbarFiles) as $file) {
    if (is_file($file)) {
        @unlink($file);
        $deleted++;
    }
}

echo '✅ ' . $deleted . ' arquivos de cache removidos<br>';
$fixes[] = 'Caches limpos';
echo '</div>';

// ============================================================================
// SUMMARY
// ============================================================================
echo '<div class="step ' . (count($errors) === 0 ? 'ok' : (count($fixes) > 0 ? 'warning' : 'error')) . '">';
echo '<h2>📋 Resumo</h2>';

if (count($fixes) > 0) {
    echo '<strong>Correções Aplicadas (' . count($fixes) . '):</strong><ul>';
    foreach ($fixes as $fix) {
        echo '<li>✅ ' . htmlspecialchars($fix) . '</li>';
    }
    echo '</ul>';
}

if (count($warnings) > 0) {
    echo '<strong>Avisos (' . count($warnings) . '):</strong><ul>';
    foreach ($warnings as $warning) {
        echo '<li>⚠️ ' . htmlspecialchars($warning) . '</li>';
    }
    echo '</ul>';
}

if (count($errors) > 0) {
    echo '<strong>Erros (' . count($errors) . '):</strong><ul>';
    foreach ($errors as $error) {
        echo '<li>❌ ' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
}

if (count($errors) === 0) {
    echo '<h3 style="color: #27ae60;">✅ TODAS AS CORREÇÕES APLICADAS COM SUCESSO!</h3>';
    echo '<p>O sistema está pronto. Teste o acesso:</p>';
    echo '<a href="/auth/login" class="btn btn-green">Testar Sistema →</a>';

    // Create lock file
    @file_put_contents($lockFile, time());
} else {
    echo '<h3 style="color: #e74c3c;">⚠️ ALGUNS ERROS OCORRERAM</h3>';
    echo '<p>Verifique as permissões dos arquivos e diretórios.</p>';
}

echo '</div>';

// Progress bar
$progress = count($errors) === 0 ? 100 : (count($fixes) * 100 / $totalSteps);
echo '<div class="progress">';
echo '<div class="progress-bar" style="width: ' . $progress . '%;">' . round($progress) . '%</div>';
echo '</div>';

?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1;">
            <h3 style="color: #e74c3c;">⚠️ SEGURANÇA</h3>
            <p><strong>DELETE ESTE ARQUIVO IMEDIATAMENTE APÓS USO!</strong></p>
            <a href="?delete=1" class="btn btn-red">🗑️ Deletar Este Script Agora</a>
            <p style="font-size: 12px; color: #7f8c8d; margin-top: 10px;">
                Este script contém operações sensíveis e deve ser removido da produção.
            </p>
        </div>

        <?php
        if (isset($_GET['delete'])) {
            if (@unlink(__FILE__)) {
                echo '<div class="step ok"><h3>✅ Script Deletado</h3><p>Arquivo removido com sucesso.</p></div>';
                echo '<script>setTimeout(function(){ window.location.href="/"; }, 2000);</script>';
            } else {
                echo '<div class="step error"><h3>❌ Falha ao Deletar</h3><p>Remova manualmente: <code>public/apply-all-fixes.php</code></p></div>';
            }
        }
        ?>
    </div>
</body>
</html>
