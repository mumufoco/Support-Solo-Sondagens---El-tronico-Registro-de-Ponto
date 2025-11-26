<?php
/**
 * Boot Debugger - Descobre exatamente onde Boot::bootWeb() está travando
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Criar arquivo de log para debug
$logFile = __DIR__ . '/../writable/logs/boot-debug.log';
file_put_contents($logFile, "=== BOOT DEBUG START " . date('Y-m-d H:i:s') . " ===\n");

function debugLog($message) {
    global $logFile;
    $time = microtime(true);
    file_put_contents($logFile, "[$time] $message\n", FILE_APPEND);
    echo "<div style='padding:5px;background:#f0f0f0;margin:2px;border-left:3px solid #333'>$message</div>";
    flush();
    if (ob_get_level() > 0) {
        ob_flush();
    }
}

echo "<!DOCTYPE html><html><head><title>Boot Debug</title></head><body>";
echo "<h1>Boot Debug - Rastreando Boot::bootWeb()</h1>";
echo "<div style='font-family:monospace;font-size:12px'>";

try {
    debugLog("1. Iniciando boot debug");

    // Define FCPATH
    define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
    debugLog("2. FCPATH definido: " . FCPATH);

    // Load paths
    debugLog("3. Carregando Paths...");
    require FCPATH . '../app/Config/Paths.php';
    $paths = new Config\Paths();
    debugLog("4. Paths carregado. systemDirectory: " . $paths->systemDirectory);

    // Load autoload
    debugLog("5. Carregando autoloader...");
    if (is_file(FCPATH . '../vendor/autoload.php')) {
        require FCPATH . '../vendor/autoload.php';
        debugLog("6. Autoloader carregado");
    } else {
        debugLog("6. ERRO: Autoloader não encontrado!");
    }

    // Load Boot.php
    debugLog("7. Carregando Boot.php...");
    require $paths->systemDirectory . '/Boot.php';
    debugLog("8. Boot.php carregado");

    // Check if bootWeb method exists
    debugLog("9. Verificando se CodeIgniter\\Boot::bootWeb existe...");
    if (method_exists('CodeIgniter\Boot', 'bootWeb')) {
        debugLog("10. Método bootWeb encontrado");
    } else {
        debugLog("10. ERRO: Método bootWeb não encontrado!");
    }

    // Try to intercept boot by wrapping it
    debugLog("11. Preparando para executar Boot::bootWeb()");
    debugLog("12. ⚠️ EXECUTANDO BOOT - Se travar aqui, o problema está DENTRO do bootWeb()");

    // Flush everything before boot
    flush();
    if (ob_get_level() > 0) {
        ob_flush();
    }

    // Execute boot with error handling
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        debugLog("PHP ERROR durante boot: [$errno] $errstr em $errfile:$errline");
    });

    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            debugLog("ERRO FATAL: {$error['message']} em {$error['file']}:{$error['line']}");
        } else {
            debugLog("Shutdown function chamada (sem erro fatal)");
        }
    });

    debugLog("13. Chamando Boot::bootWeb(\$paths)...");

    // Execute boot
    $exitCode = CodeIgniter\Boot::bootWeb($paths);

    debugLog("14. Boot::bootWeb() RETORNOU! Exit code: $exitCode");
    debugLog("15. Boot concluído sem exceção");

} catch (Throwable $e) {
    debugLog("EXCEÇÃO CAPTURADA: " . get_class($e));
    debugLog("Mensagem: " . $e->getMessage());
    debugLog("Arquivo: " . $e->getFile() . ":" . $e->getLine());
    debugLog("Stack trace:");
    foreach (explode("\n", $e->getTraceAsString()) as $line) {
        debugLog("  " . $line);
    }
}

echo "</div>";
echo "<hr>";
echo "<h2>Log Completo:</h2>";
echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
echo "</body></html>";
