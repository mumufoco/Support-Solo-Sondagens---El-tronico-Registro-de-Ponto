<?php
/**
 * TEMPORARY: Clear Rate Limit for Testing
 * DELETE after fixing login issue!
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../vendor/autoload.php';

// Bootstrap CodeIgniter minimal
$paths = new Config\Paths();
require $paths->systemDirectory . '/Common.php';

// Get cache instance
$cache = \Config\Services::cache();

// Clear all rate limit cache keys
$cleared = false;
try {
    // Try to clear cache
    $result = $cache->clean();
    $cleared = true;

    echo "<h2>✅ Cache de Rate Limit Limpo!</h2>";
    echo "<p>Todas as restrições de tentativas de login foram removidas.</p>";
    echo "<p>Você pode tentar fazer login novamente agora.</p>";
    echo "<p><a href='/auth/login'>← Voltar para Login</a></p>";

} catch (Exception $e) {
    echo "<h2>❌ Erro ao limpar cache</h2>";
    echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><strong>⚠️ IMPORTANTE: DELETE este arquivo após resolver o problema!</strong></p>";
echo "<p><code>rm public/clear-ratelimit.php</code></p>";
?>
