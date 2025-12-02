<?php
/**
 * Clear Rate Limit Cache
 * Run this ONCE to clear rate limiting
 * DELETE after running!
 */

// Clear writable/cache directory
$cacheDir = dirname(__DIR__) . '/writable/cache';

if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    $count = 0;

    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }

    echo "✅ Cache limpo! $count arquivos deletados.\n";
    echo "Agora você pode fazer login novamente!\n";
    echo "\n⚠️ DELETE este arquivo (clear-rate-limit.php) agora!\n";
} else {
    echo "❌ Diretório de cache não encontrado.\n";
}
