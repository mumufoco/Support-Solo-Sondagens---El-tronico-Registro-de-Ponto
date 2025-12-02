<?php
/**
 * DEBUG LOGIN - Rastrear Redirects
 * Acesse: https://ponto.supportsondagens.com.br/debug-login.php
 * DELETE após resolver!
 */

// Capture all redirects
$redirects = [];
$maxRedirects = 20;
$url = 'https://ponto.supportsondagens.com.br/auth/login';

// Start session to see cookies
session_start();

echo "<!DOCTYPE html><html><head><title>Debug Login</title>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.redirect { background: #fff; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
.error { border-left-color: #dc3545; background: #f8d7da; }
.success { border-left-color: #28a745; background: #d4edda; }
pre { background: #f8f9fa; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 Debug de Redirects - Sistema de Ponto</h1>";
echo "<p><strong>URL Inicial:</strong> $url</p>";
echo "<hr>";

// Follow redirects manually
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow, we'll do it manually
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$count = 0;
$visitedUrls = [];

while ($count < $maxRedirects) {
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);

    $count++;

    echo "<div class='redirect'>";
    echo "<h3>Redirect #{$count}: $url</h3>";
    echo "<strong>HTTP Code:</strong> $httpCode<br>";

    // Check if we've been here before (infinite loop detection)
    if (in_array($url, $visitedUrls)) {
        echo "<div class='error'>";
        echo "<h2>❌ LOOP INFINITO DETECTADO!</h2>";
        echo "<p>A URL <code>$url</code> já foi visitada antes.</p>";
        echo "<p><strong>Loop entre:</strong></p>";
        echo "<pre>" . implode("\n↓\n", $visitedUrls) . "\n↓\n$url (VOLTA AQUI!)</pre>";
        echo "</div>";
        break;
    }

    $visitedUrls[] = $url;

    // Check for redirect
    if ($httpCode >= 300 && $httpCode < 400) {
        // Parse Location header
        if (preg_match('/Location:\s*(.+)/i', $headers, $matches)) {
            $location = trim($matches[1]);

            echo "<strong>Redirecionando para:</strong> <code>$location</code><br>";

            // Handle relative URLs
            if (strpos($location, 'http') !== 0) {
                $parsedUrl = parse_url($url);
                $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                $location = $baseUrl . $location;
            }

            $url = $location;
            curl_setopt($ch, CURLOPT_URL, $url);

            echo "<strong>Próxima URL:</strong> $url";
        } else {
            echo "<div class='error'>❌ Redirect sem Location header!</div>";
            break;
        }
    } else if ($httpCode == 200) {
        echo "<div class='success'>";
        echo "<h2>✅ Página carregada com sucesso!</h2>";
        echo "<p>URL Final: <code>$url</code></p>";
        echo "</div>";
        break;
    } else {
        echo "<div class='error'>";
        echo "<h2>❌ Erro HTTP $httpCode</h2>";
        echo "</div>";
        break;
    }

    echo "</div>";
}

curl_close($ch);

if ($count >= $maxRedirects) {
    echo "<div class='error'>";
    echo "<h2>❌ Muitos redirects ($maxRedirects)</h2>";
    echo "<p>O sistema está em loop infinito!</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Resumo de URLs Visitadas:</h2>";
echo "<ol>";
foreach ($visitedUrls as $idx => $visitedUrl) {
    echo "<li><code>$visitedUrl</code></li>";
}
echo "</ol>";

echo "<hr>";
echo "<h2>Verificações Adicionais:</h2>";

// Check Routes.php content
$routesFile = dirname(__DIR__) . '/app/Config/Routes.php';
if (file_exists($routesFile)) {
    $routesContent = file_get_contents($routesFile);

    echo "<h3>Routes.php - Linha 44:</h3>";
    $lines = explode("\n", $routesContent);
    if (isset($lines[43])) {
        echo "<pre>" . htmlspecialchars($lines[43]) . "</pre>";

        if (strpos($lines[43], 'Dashboard\DashboardController::admin') !== false) {
            echo "<div class='success'>✅ Routes.php CORRETO!</div>";
        } else {
            echo "<div class='error'>❌ Routes.php ERRADO - Git pull não funcionou!</div>";
        }
    }
}

// Check .htaccess
$htaccessFile = __DIR__ . '/.htaccess';
if (file_exists($htaccessFile)) {
    $htaccessContent = file_get_contents($htaccessFile);
    echo "<h3>.htaccess Content:</h3>";

    if (strpos($htaccessContent, 'RewriteCond %{HTTPS} off') !== false &&
        strpos($htaccessContent, '# RewriteCond %{HTTPS} off') === false) {
        echo "<div class='error'>❌ .htaccess tem redirect HTTPS ATIVO!</div>";
        echo "<pre>" . htmlspecialchars($htaccessContent) . "</pre>";
    } else {
        echo "<div class='success'>✅ .htaccess OK - redirect HTTPS comentado</div>";
    }
}

echo "</body></html>";
