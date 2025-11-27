<?php
/**
 * Test Boot::bootWeb() com timeout
 */

set_time_limit(5); // Máximo 5 segundos

echo "<!DOCTYPE html><html><head><title>Boot Timeout Test</title></head><body>";
echo "<h1>Test Boot::bootWeb() com Timeout</h1>";
echo "<div style='font-family:monospace;padding:20px'>";

echo "<p>Tempo limite: 5 segundos</p>";
echo "<p>Se o script atingir o timeout, significa que Boot::bootWeb() está travando.</p>";
echo "<hr>";

flush();

// Setup básico
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();

require FCPATH . '../vendor/autoload.php';
require $paths->systemDirectory . '/Boot.php';

echo "<p><strong>Preparação completa.</strong> Agora chamando Boot::bootWeb()...</p>";
flush();

$start = microtime(true);

try {
    // Registrar shutdown para timeout
    register_shutdown_function(function() use ($start) {
        $elapsed = microtime(true) - $start;
        $error = error_get_last();

        if ($error && $error['type'] === E_ERROR) {
            echo "<div style='background:#ffcdd2;padding:20px;margin:10px 0;border:2px solid #f44336'>";
            echo "<h2>❌ ERRO FATAL após " . round($elapsed, 2) . " segundos:</h2>";
            echo "<strong>Mensagem:</strong> " . htmlspecialchars($error['message']) . "<br>";
            echo "<strong>Arquivo:</strong> " . $error['file'] . ":" . $error['line'];
            echo "</div>";
        } elseif ($elapsed >= 4.5) {
            echo "<div style='background:#fff3cd;padding:20px;margin:10px 0;border:2px solid #ffc107'>";
            echo "<h2>⏱️ TIMEOUT após " . round($elapsed, 2) . " segundos!</h2>";
            echo "<p><strong>Boot::bootWeb() travou ou entrou em loop.</strong></p>";
            echo "<p>Possíveis causas:</p>";
            echo "<ul>";
            echo "<li>Loop infinito no código</li>";
            echo "<li>Esperando por recurso que não responde (banco de dados, arquivo, etc.)</li>";
            echo "<li>Deadlock em sessão ou cache</li>";
            echo "</ul>";
            echo "</div>";
        }

        echo "</div></body></html>";
    });

    // Chamar Boot::bootWeb()
    CodeIgniter\Boot::bootWeb($paths);

    $elapsed = microtime(true) - $start;

    echo "<div style='background:#e8f5e9;padding:20px;margin:10px 0;border:2px solid #4caf50'>";
    echo "<h2>✅ Boot::bootWeb() RETORNOU após " . round($elapsed, 2) . " segundos!</h2>";
    echo "<p>O boot completou, mas pode ter produzido output que não estamos vendo.</p>";
    echo "</div>";

} catch (Throwable $e) {
    $elapsed = microtime(true) - $start;

    echo "<div style='background:#ffebee;padding:20px;margin:10px 0;border:2px solid #f44336'>";
    echo "<h2>❌ EXCEÇÃO após " . round($elapsed, 2) . " segundos:</h2>";
    echo "<strong>Tipo:</strong> " . get_class($e) . "<br>";
    echo "<strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<details><summary>Stack Trace</summary>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</details>";
    echo "</div>";
}

echo "</div></body></html>";
