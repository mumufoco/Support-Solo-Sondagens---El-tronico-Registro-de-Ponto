<?php
/**
 * Interceptor de Output - Captura TUDO que o CodeIgniter faz
 */

// Capturar TUDO
ob_start();

// Interceptar headers
$headers_sent = [];
header_register_callback(function() use (&$headers_sent) {
    $headers_sent = headers_list();
});

echo "<!DOCTYPE html><html><head><title>Output Interceptor</title></head><body>";
echo "<h1>🔍 Interceptando CodeIgniter</h1>";
echo "<div style='font-family:monospace;font-size:13px;padding:20px'>";

// Executar o index.php original
echo "<h2>1. Executando index.php...</h2>";

try {
    // Capturar output do index.php
    ob_start();

    // Executar index.php
    require __DIR__ . '/index.php';

    $ci_output = ob_get_clean();

    echo "<div style='background:#e8f5e9;padding:15px;margin:10px 0;border:2px solid #4caf50'>";
    echo "<h3>✅ index.php executado sem exceção</h3>";
    echo "</div>";

} catch (Throwable $e) {
    ob_end_clean();

    echo "<div style='background:#ffebee;padding:15px;margin:10px 0;border:2px solid #f44336'>";
    echo "<h3>❌ Exceção capturada:</h3>";
    echo "<strong>Tipo:</strong> " . get_class($e) . "<br>";
    echo "<strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";

    $ci_output = '';
}

// Mostrar resultados
echo "<h2>2. Análise de Resultados</h2>";

echo "<h3>Headers HTTP enviados:</h3>";
echo "<div style='background:#fff3cd;padding:10px;margin:10px 0;border:1px solid #ffc107'>";
if (!empty($headers_sent)) {
    echo "<ul>";
    foreach ($headers_sent as $header) {
        echo "<li>" . htmlspecialchars($header) . "</li>";

        // Destacar redirects
        if (stripos($header, 'location:') === 0) {
            echo "<div style='background:#ff5722;color:white;padding:10px;margin:5px 0'>";
            echo "<strong>⚠️ REDIRECT DETECTADO!</strong> Isso explica a página branca!";
            echo "</div>";
        }
    }
    echo "</ul>";
} else {
    echo "<em>Nenhum header enviado</em>";
}
echo "</div>";

echo "<h3>Output do CodeIgniter:</h3>";
echo "<div style='background:#e3f2fd;padding:10px;margin:10px 0;border:1px solid #2196f3'>";
if (!empty($ci_output)) {
    echo "<strong>Tamanho:</strong> " . strlen($ci_output) . " bytes<br>";
    echo "<strong>Conteúdo (primeiros 500 caracteres):</strong><br>";
    echo "<pre>" . htmlspecialchars(substr($ci_output, 0, 500)) . "</pre>";
    if (strlen($ci_output) > 500) {
        echo "<em>... (truncado)</em>";
    }
} else {
    echo "<div style='background:#ffcdd2;padding:10px;border:1px solid #f44336'>";
    echo "<strong>❌ CodeIgniter não produziu NENHUM output!</strong>";
    echo "</div>";
}
echo "</div>";

echo "<h3>Status do Output Buffering:</h3>";
echo "<div style='background:#f3e5f5;padding:10px;margin:10px 0;border:1px solid #9c27b0'>";
echo "Nível de buffer: " . ob_get_level() . "<br>";
echo "Tamanho do buffer: " . ob_get_length() . " bytes<br>";
echo "</div>";

echo "<hr>";
echo "<h2>3. Conclusão</h2>";
echo "<div style='background:#fff9c4;padding:15px;margin:10px 0;border:2px solid #ffc107'>";

if (!empty($headers_sent)) {
    foreach ($headers_sent as $header) {
        if (stripos($header, 'location:') === 0) {
            preg_match('/location:\s*(.+)/i', $header, $matches);
            $redirect_url = trim($matches[1] ?? '');
            echo "<h3>🔍 PROBLEMA IDENTIFICADO:</h3>";
            echo "<p>O CodeIgniter está enviando um <strong>HTTP Redirect</strong> para:</p>";
            echo "<p style='font-size:18px;color:#d32f2f'><strong>$redirect_url</strong></p>";
            echo "<p>A página branca acontece porque:</p>";
            echo "<ul>";
            echo "<li>O navegador segue o redirect</li>";
            echo "<li>A página de destino também pode ter problemas</li>";
            echo "<li>Ou pode estar em loop de redirects</li>";
            echo "</ul>";
            echo "<p><strong>Próximo passo:</strong> Verificar se a página de destino existe e funciona.</p>";
            break;
        }
    }
} elseif (empty($ci_output)) {
    echo "<h3>🔍 PROBLEMA IDENTIFICADO:</h3>";
    echo "<p>CodeIgniter executa mas <strong>não produz output nem redirect</strong>.</p>";
    echo "<p>Possíveis causas:</p>";
    echo "<ul>";
    echo "<li>exit() ou die() sem mensagem</li>";
    echo "<li>Output buffering descartado</li>";
    echo "<li>Erro suprimido por configuração</li>";
    echo "</ul>";
} else {
    echo "<h3>✅ Output capturado!</h3>";
    echo "<p>CodeIgniter produziu output. Verifique acima o conteúdo.</p>";
}

echo "</div>";

echo "</div></body></html>";

$final_output = ob_get_clean();
echo $final_output;
