<?php
/**
 * Boot Step-by-Step - Replica Boot::bootWeb() com logging em cada passo
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

function step($num, $desc) {
    echo "<div style='padding:8px;margin:3px;background:#e8f5e9;border-left:4px solid #4caf50'>";
    echo "<strong>Passo $num:</strong> $desc";
    echo "</div>";
    flush();
    if (ob_get_level() > 0) ob_flush();
}

function stepError($num, $desc, $error) {
    echo "<div style='padding:8px;margin:3px;background:#ffebee;border-left:4px solid #f44336'>";
    echo "<strong>Passo $num FALHOU:</strong> $desc<br>";
    echo "<strong>Erro:</strong> " . htmlspecialchars($error);
    echo "</div>";
    flush();
}

echo "<!DOCTYPE html><html><head><title>Boot Step by Step</title></head><body>";
echo "<h1>🔍 Boot::bootWeb() Step-by-Step</h1>";
echo "<div style='font-family:monospace;font-size:13px'>";

try {
    // Setup
    define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
    require FCPATH . '../app/Config/Paths.php';
    $paths = new Config\Paths();
    require FCPATH . '../vendor/autoload.php';

    step(0, "Preparação completa (FCPATH, Paths, Autoload)");

    // Passo 1: definePathConstants
    step(1, "Chamando CodeIgniter\\Boot::definePathConstants()...");
    try {
        CodeIgniter\Boot::definePathConstants($paths);
        step("1✅", "definePathConstants() OK - Constantes: APPPATH=" . APPPATH . ", WRITEPATH=" . WRITEPATH);
    } catch (Throwable $e) {
        stepError(1, "definePathConstants()", $e->getMessage());
        throw $e;
    }

    // Passo 2: loadConstants
    step(2, "Chamando CodeIgniter\\Boot::loadConstants()...");
    try {
        if (! defined('APP_NAMESPACE')) {
            CodeIgniter\Boot::loadConstants();
        }
        step("2✅", "loadConstants() OK");
    } catch (Throwable $e) {
        stepError(2, "loadConstants()", $e->getMessage());
        throw $e;
    }

    // Passo 3: checkMissingExtensions
    step(3, "Chamando CodeIgniter\\Boot::checkMissingExtensions()...");
    try {
        CodeIgniter\Boot::checkMissingExtensions();
        step("3✅", "checkMissingExtensions() OK");
    } catch (Throwable $e) {
        stepError(3, "checkMissingExtensions()", $e->getMessage());
        throw $e;
    }

    // Passo 4: loadDotEnv
    step(4, "Chamando CodeIgniter\\Boot::loadDotEnv()...");
    try {
        CodeIgniter\Boot::loadDotEnv($paths);
        step("4✅", "loadDotEnv() OK");
    } catch (Throwable $e) {
        stepError(4, "loadDotEnv()", $e->getMessage());
        throw $e;
    }

    // Passo 5: defineEnvironment
    step(5, "Chamando CodeIgniter\\Boot::defineEnvironment()...");
    try {
        CodeIgniter\Boot::defineEnvironment();
        step("5✅", "defineEnvironment() OK - ENVIRONMENT=" . ENVIRONMENT);
    } catch (Throwable $e) {
        stepError(5, "defineEnvironment()", $e->getMessage());
        throw $e;
    }

    // Passo 6: loadEnvironmentBootstrap (SUSPEITO!)
    step(6, "⚠️ Chamando CodeIgniter\\Boot::loadEnvironmentBootstrap() - CARREGA production.php");
    try {
        CodeIgniter\Boot::loadEnvironmentBootstrap($paths);
        step("6✅", "loadEnvironmentBootstrap() OK");
    } catch (Throwable $e) {
        stepError(6, "loadEnvironmentBootstrap()", $e->getMessage());
        throw $e;
    }

    // Passo 7: loadCommonFunctions
    step(7, "Chamando CodeIgniter\\Boot::loadCommonFunctions()...");
    try {
        CodeIgniter\Boot::loadCommonFunctions();
        step("7✅", "loadCommonFunctions() OK");
    } catch (Throwable $e) {
        stepError(7, "loadCommonFunctions()", $e->getMessage());
        throw $e;
    }

    // Passo 8: loadAutoloader
    step(8, "Chamando CodeIgniter\\Boot::loadAutoloader()...");
    try {
        CodeIgniter\Boot::loadAutoloader();
        step("8✅", "loadAutoloader() OK");
    } catch (Throwable $e) {
        stepError(8, "loadAutoloader()", $e->getMessage());
        throw $e;
    }

    // Passo 9: setExceptionHandler
    step(9, "Chamando CodeIgniter\\Boot::setExceptionHandler()...");
    try {
        CodeIgniter\Boot::setExceptionHandler();
        step("9✅", "setExceptionHandler() OK");
    } catch (Throwable $e) {
        stepError(9, "setExceptionHandler()", $e->getMessage());
        throw $e;
    }

    // Passo 10: initializeKint
    step(10, "Chamando CodeIgniter\\Boot::initializeKint()...");
    try {
        CodeIgniter\Boot::initializeKint();
        step("10✅", "initializeKint() OK");
    } catch (Throwable $e) {
        stepError(10, "initializeKint()", $e->getMessage());
        throw $e;
    }

    // Passo 11: autoloadHelpers
    step(11, "Chamando CodeIgniter\\Boot::autoloadHelpers()...");
    try {
        CodeIgniter\Boot::autoloadHelpers();
        step("11✅", "autoloadHelpers() OK");
    } catch (Throwable $e) {
        stepError(11, "autoloadHelpers()", $e->getMessage());
        throw $e;
    }

    // Passo 12: initializeCodeIgniter
    step(12, "Chamando CodeIgniter\\Boot::initializeCodeIgniter()...");
    try {
        $app = CodeIgniter\Boot::initializeCodeIgniter();
        step("12✅", "initializeCodeIgniter() OK - App: " . get_class($app));
    } catch (Throwable $e) {
        stepError(12, "initializeCodeIgniter()", $e->getMessage());
        throw $e;
    }

    // Passo 13: runCodeIgniter (SUSPEITO!)
    step(13, "⚠️ Chamando CodeIgniter\\Boot::runCodeIgniter() - EXECUTA A APLICAÇÃO");
    try {
        CodeIgniter\Boot::runCodeIgniter($app);
        step("13✅", "runCodeIgniter() OK");
    } catch (Throwable $e) {
        stepError(13, "runCodeIgniter()", $e->getMessage());
        throw $e;
    }

    step(14, "✅✅✅ BOOT COMPLETO COM SUCESSO! ✅✅✅");

} catch (Throwable $e) {
    echo "<div style='padding:15px;margin:10px;background:#ffcdd2;border:2px solid #f44336'>";
    echo "<h2>❌ EXCEÇÃO CAPTURADA:</h2>";
    echo "<strong>Tipo:</strong> " . get_class($e) . "<br>";
    echo "<strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div>";
echo "<hr>";
echo "<p><em>Se o script parou em algum passo, o problema está NAQUELE passo específico.</em></p>";
echo "</body></html>";
