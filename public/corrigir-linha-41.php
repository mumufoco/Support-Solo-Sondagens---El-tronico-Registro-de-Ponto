<?php
/**
 * CORREÇÃO FORÇADA - Edita linha 41 diretamente
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$file = '/home/supportson/public_html/ponto/vendor/composer/autoload_real.php';

echo "🔧 CORREÇÃO FORÇADA - Linha 41\n";
echo str_repeat("=", 80) . "\n\n";

// 1. Verificar arquivo
if (!file_exists($file)) {
    die("❌ Arquivo não encontrado: $file\n");
}

echo "✅ Arquivo encontrado\n";
echo "Tamanho: " . filesize($file) . " bytes\n\n";

// 2. Backup
$backup = $file . '.backup-forcada-' . date('YmdHis');
if (!copy($file, $backup)) {
    die("❌ Erro ao criar backup\n");
}
echo "✅ Backup criado: " . basename($backup) . "\n\n";

// 3. Ler arquivo
$lines = file($file, FILE_IGNORE_NEW_LINES);
echo "Total de linhas: " . count($lines) . "\n\n";

// 4. Mostrar linha 41
echo "Linha 41 ANTES:\n";
echo str_repeat("-", 80) . "\n";
echo isset($lines[40]) ? $lines[40] : "LINHA NÃO EXISTE\n";
echo str_repeat("-", 80) . "\n\n";

// 5. Modificar linha 41 (índice 40)
if (isset($lines[40])) {
    $originalLine = $lines[40];

    // Se a linha contém require e phpunit, comentar
    if (stripos($originalLine, 'require') !== false && stripos($originalLine, 'phpunit') !== false) {
        // Preservar indentação
        $indent = str_repeat(' ', strlen($originalLine) - strlen(ltrim($originalLine)));
        $lines[40] = $indent . '// ' . trim($originalLine) . ' // DESABILITADO - PHPUnit não instalado';

        echo "✅ Linha 41 MODIFICADA\n\n";

        echo "Linha 41 DEPOIS:\n";
        echo str_repeat("-", 80) . "\n";
        echo $lines[40] . "\n";
        echo str_repeat("-", 80) . "\n\n";

        // 6. Salvar arquivo
        $newContent = implode("\n", $lines) . "\n";
        if (file_put_contents($file, $newContent)) {
            echo "✅ Arquivo salvo com sucesso!\n\n";

            // 7. Limpar caches
            if (function_exists('opcache_reset')) {
                opcache_reset();
                echo "✅ Cache OPcache limpo\n";
            }

            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($file, true);
                echo "✅ Arquivo invalidado no OPcache\n";
            }

            echo "\n";
            echo str_repeat("=", 80) . "\n";
            echo "✅ CORREÇÃO CONCLUÍDA!\n";
            echo str_repeat("=", 80) . "\n";
            echo "\nTeste agora:\n";
            echo "https://ponto.supportsondagens.com.br/health\n";
            echo "https://ponto.supportsondagens.com.br/auth/login\n";

        } else {
            echo "❌ Erro ao salvar arquivo\n";
        }

    } else {
        echo "⚠️ Linha 41 NÃO contém referência ao PHPUnit ou require\n";
        echo "Conteúdo: " . $originalLine . "\n";
    }
} else {
    echo "❌ Arquivo tem menos de 41 linhas!\n";
}
