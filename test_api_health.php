<?php
/**
 * Teste direto do endpoint /api/health
 */

echo "=== TESTE DIRETO DO /api/health ===\n\n";

// Verificar arquivo INSTALLED
$installedFile = __DIR__ . '/writable/INSTALLED';
echo "1. Arquivo INSTALLED existe: " . (file_exists($installedFile) ? "✅ SIM" : "❌ NÃO") . "\n";

if (file_exists($installedFile)) {
    echo "   Conteúdo: " . file_get_contents($installedFile) . "\n";
}

echo "\n";

// Testar endpoint
echo "2. Testando /api/health:\n";

$ch = curl_init('http://localhost:8080/api/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Status: $code\n";

if ($code == 200) {
    echo "   ✅ SUCESSO!\n\n";

    $headerSize = strpos($response, "\r\n\r\n");
    $body = substr($response, $headerSize + 4);

    echo "   Response Body:\n";
    $json = json_decode($body, true);
    if ($json) {
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo $body . "\n";
    }
} else {
    echo "   ❌ ERRO $code\n\n";
    echo "   Response:\n";
    echo $response . "\n";
}

echo "\n";
echo "3. Verificar log de erros:\n";
$logFile = __DIR__ . '/writable/logs/log-' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $errorLines = array_filter($lines, function($line) {
        return strpos($line, 'api/health') !== false ||
               strpos($line, 'CRITICAL') !== false;
    });

    if (count($errorLines) > 0) {
        echo "   Últimos erros relacionados:\n";
        $lastErrors = array_slice($errorLines, -5);
        foreach ($lastErrors as $line) {
            echo "   " . trim($line) . "\n";
        }
    } else {
        echo "   ✅ Sem erros no log\n";
    }
} else {
    echo "   ⚠️  Log file não encontrado\n";
}

echo "\n=== FIM DO TESTE ===\n";
