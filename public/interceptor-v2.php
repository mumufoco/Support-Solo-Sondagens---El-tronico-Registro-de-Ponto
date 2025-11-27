<?php
/**
 * Interceptor V2 - Usa index-no-exit.php
 */

ob_start();

echo "<!DOCTYPE html><html><head><title>Interceptor V2</title></head><body>";
echo "<h1>🔍 Interceptor V2 - Usando index-no-exit.php</h1>";
echo "<div style='font-family:monospace;font-size:13px;padding:20px'>";

echo "<p>Este interceptor usa index-no-exit.php que <strong>retorna</strong> em vez de <strong>exit()</strong>.</p>";
echo "<hr>";

// Capturar headers
$captured_headers = [];
if (function_exists('headers_list')) {
    register_shutdown_function(function() use (&$captured_headers) {
        global $captured_headers;
        $captured_headers = headers_list();
    });
}

echo "<h2>1. Executando index-no-exit.php...</h2>";

$start_time = microtime(true);

try {
    // Capturar TODO o output
    ob_start();

    // Executar index-no-exit.php
    $exit_code = require __DIR__ . '/index-no-exit.php';

    $ci_output = ob_get_clean();
    $execution_time = microtime(true) - $start_time;

    echo "<div style='background:#e8f5e9;padding:15px;margin:10px 0'>";
    echo "<h3>✅ Executado com sucesso!</h3>";
    echo "<strong>Exit code:</strong> " . var_export($exit_code, true) . "<br>";
    echo "<strong>Tempo de execução:</strong> " . round($execution_time, 3) . " segundos<br>";
    echo "</div>";

} catch (Throwable $e) {
    ob_end_clean();
    $execution_time = microtime(true) - $start_time;

    echo "<div style='background:#ffebee;padding:15px;margin:10px 0'>";
    echo "<h3>❌ Exceção capturada após " . round($execution_time, 3) . "s:</h3>";
    echo "<strong>Tipo:</strong> " . get_class($e) . "<br>";
    echo "<strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<details><summary>Stack Trace</summary><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></details>";
    echo "</div>";

    $ci_output = '';
}

// Análise
echo "<h2>2. Análise</h2>";

// Headers
echo "<h3>Headers HTTP:</h3>";
echo "<div style='background:#fff3cd;padding:10px;margin:10px 0'>";
$all_headers = array_merge($captured_headers, headers_list());
$all_headers = array_unique($all_headers);

if (!empty($all_headers)) {
    echo "<ul>";
    foreach ($all_headers as $header) {
        echo "<li>" . htmlspecialchars($header);

        if (stripos($header, 'location:') === 0) {
            preg_match('/location:\s*(.+)/i', $header, $matches);
            $redirect_url = trim($matches[1] ?? '');
            echo "<div style='background:#ff5722;color:white;padding:10px;margin:5px 0'>";
            echo "⚠️ <strong>REDIRECT!</strong> Redirecionando para: <strong>$redirect_url</strong>";
            echo "</div>";
        }
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<em>Nenhum header detectado</em>";
}
echo "</div>";

// Output
echo "<h3>Output produzido:</h3>";
echo "<div style='background:#e3f2fd;padding:10px;margin:10px 0'>";
if (!empty($ci_output)) {
    echo "<strong>Tamanho:</strong> " . strlen($ci_output) . " bytes<br>";
    echo "<strong>Primeiros 1000 caracteres:</strong><br>";
    echo "<textarea style='width:100%;height:300px;font-family:monospace;font-size:11px'>";
    echo htmlspecialchars(substr($ci_output, 0, 1000));
    echo "</textarea>";
} else {
    echo "<div style='background:#ffcdd2;padding:15px;border:2px solid #f44336'>";
    echo "<strong>❌ NENHUM OUTPUT!</strong>";
    echo "<p>CodeIgniter executou mas não produziu output.</p>";
    echo "</div>";
}
echo "</div>";

// Conclusão
echo "<hr><h2>3. Diagnóstico Final</h2>";
echo "<div style='background:#fff9c4;padding:20px;margin:10px 0;border:2px solid #ffc107'>";

$has_redirect = false;
$redirect_target = '';

foreach ($all_headers as $header) {
    if (stripos($header, 'location:') === 0) {
        $has_redirect = true;
        preg_match('/location:\s*(.+)/i', $header, $matches);
        $redirect_target = trim($matches[1] ?? '');
        break;
    }
}

if ($has_redirect) {
    echo "<h3 style='color:#d32f2f'>🎯 PROBLEMA IDENTIFICADO: HTTP REDIRECT</h3>";
    echo "<p><strong>O CodeIgniter está redirecionando para:</strong></p>";
    echo "<p style='font-size:18px;background:white;padding:10px;border:2px solid #d32f2f'>";
    echo "<code>" . htmlspecialchars($redirect_target) . "</code>";
    echo "</p>";
    echo "<p><strong>Por que a página fica branca:</strong></p>";
    echo "<ol>";
    echo "<li>Navegador recebe o redirect</li>";
    echo "<li>Segue para: <code>" . htmlspecialchars($redirect_target) . "</code></li>";
    echo "<li>A página de destino TAMBÉM tem problema → Página branca</li>";
    echo "</ol>";
    echo "<p><strong>Próximo passo:</strong> Acessar diretamente <code>" . htmlspecialchars($redirect_target) . "</code> e ver o erro.</p>";
} elseif (empty($ci_output)) {
    echo "<h3 style='color:#d32f2f'>🎯 PROBLEMA: SEM OUTPUT</h3>";
    echo "<p>CodeIgniter executa mas não produz output nem redirect.</p>";
    echo "<p>Possíveis causas:</p>";
    echo "<ul>";
    echo "<li>Controller retorna vazio</li>";
    echo "<li>View não encontrada</li>";
    echo "<li>Output buffer descartado</li>";
    echo "</ul>";
} else {
    echo "<h3 style='color:#388e3c'>✅ Output capturado com sucesso!</h3>";
    echo "<p>Verifique o output acima.</p>";
}

echo "</div>";

echo "</div></body></html>";

$final = ob_get_clean();
echo $final;
