<?php
/**
 * Correção do Autoloader do Composer - SEM exec()
 *
 * Corrige referências a dependências dev (PHPUnit) removendo-as diretamente
 * dos arquivos de autoload do Composer, sem necessidade de exec/shell.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$isWeb = PHP_SAPI !== 'cli';

if ($isWeb) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Correção Autoloader</title>';
    echo '<style>body{font-family:monospace;background:#1a1a2e;color:#eee;padding:20px}';
    echo '.success{color:#10b981}.error{color:#ef4444}.warning{color:#f59e0b}';
    echo 'pre{background:#0a0e27;padding:15px;border-radius:5px}</style></head><body>';
}

function printMsg($msg, $type = 'info') {
    global $isWeb;
    $colors = ['success' => '#10b981', 'error' => '#ef4444', 'warning' => '#f59e0b'];

    if ($isWeb) {
        $class = in_array($type, ['success', 'error', 'warning']) ? $type : 'info';
        echo "<div class='$class'>$msg</div>";
    } else {
        echo $msg . "\n";
    }
}

printMsg("=================================================");
printMsg("  Correção do Autoloader do Composer");
printMsg("  (Modo Manual - SEM exec/shell)");
printMsg("=================================================\n");

// Arquivos que podem ter referências a dev packages
$autoloadFiles = [
    __DIR__ . '/vendor/composer/autoload_files.php',
    __DIR__ . '/vendor/composer/autoload_static.php',
    __DIR__ . '/vendor/composer/autoload_real.php',
];

$fixed = 0;
$errors = [];

printMsg("► Verificando arquivos do autoloader...\n");

foreach ($autoloadFiles as $file) {
    if (!file_exists($file)) {
        printMsg("⚠ Arquivo não encontrado: " . basename($file), 'warning');
        continue;
    }

    printMsg("Analisando: " . basename($file));

    $content = file_get_contents($file);
    $originalContent = $content;
    $modified = false;

    // Padrões problemáticos a remover
    $patterns = [
        // Remover require de PHPUnit Functions
        "/require __DIR__ \. '\/\.\.\/phpunit\/phpunit\/src\/Framework\/Assert\/Functions\.php';/",
        "/require \\\$vendorDir \. '\/phpunit\/phpunit\/src\/Framework\/Assert\/Functions\.php';/",

        // Remover do array de arquivos
        "/\s*'[a-f0-9]{32}' => \\\$vendorDir \. '\/phpunit\/phpunit\/src\/Framework\/Assert\/Functions\.php',?\s*/",
        "/\s*\\\$vendorDir \. '\/phpunit\/phpunit\/src\/Framework\/Assert\/Functions\.php',?\s*/",

        // Remover includes de outros packages dev
        "/require __DIR__ \. '\/\.\.\/[^\/]+\/[^\/]+\/[^']*\/Functions\.php';/",
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, '', $content);
            $modified = true;
        }
    }

    // Limpar arrays vazios resultantes
    $content = preg_replace("/array\s*\(\s*\)/", "array()", $content);

    // Limpar vírgulas duplas
    $content = preg_replace("/,\s*,/", ",", $content);

    // Limpar vírgula antes de fechar array
    $content = preg_replace("/,\s*\)/", ")", $content);
    $content = preg_replace("/,\s*\]/", "]", $content);

    if ($modified && $content !== $originalContent) {
        // Fazer backup
        $backupFile = $file . '.backup-' . date('YmdHis');
        if (copy($file, $backupFile)) {
            printMsg("  → Backup criado: " . basename($backupFile), 'success');
        }

        // Salvar arquivo corrigido
        if (file_put_contents($file, $content)) {
            printMsg("  ✓ Arquivo corrigido!", 'success');
            $fixed++;
        } else {
            $errors[] = "Erro ao salvar: " . basename($file);
            printMsg("  ✗ Erro ao salvar arquivo", 'error');
        }
    } else {
        printMsg("  ✓ Nenhuma correção necessária");
    }
}

printMsg("\n=================================================");

if ($fixed > 0) {
    printMsg("✓ Correção concluída com sucesso!", 'success');
    printMsg("  $fixed arquivo(s) corrigido(s)\n", 'success');

    printMsg("=================================================\n");
    printMsg("🎉 Autoloader corrigido!\n", 'success');
    printMsg("Agora você pode acessar o sistema:");

    if ($isWeb) {
        printMsg('<a href="/" style="color:#667eea;font-weight:bold">https://ponto.supportsondagens.com.br</a>');
        printMsg('<br><br><a href="diagnostico.php" style="color:#667eea">← Voltar ao Diagnóstico</a>');
    } else {
        printMsg("  https://ponto.supportsondagens.com.br\n");
    }

} elseif (!empty($errors)) {
    printMsg("✗ Erros encontrados:", 'error');
    foreach ($errors as $error) {
        printMsg("  - $error", 'error');
    }

    printMsg("\n📋 Solução Alternativa via SSH:");
    printMsg("cd /home/supportson/public_html/ponto");
    printMsg("rm -rf vendor/");
    printMsg("composer install --no-dev --optimize-autoloader\n");

} else {
    printMsg("ℹ Nenhuma correção necessária", 'warning');
    printMsg("Os arquivos já estão corretos ou não existem.\n");

    printMsg("📋 Se o erro persistir, reinstale o vendor via SSH:");
    printMsg("cd /home/supportson/public_html/ponto");
    printMsg("rm -rf vendor/");
    printMsg("composer install --no-dev --optimize-autoloader\n");
}

// Verificar se ainda há problemas
printMsg("\n► Verificando se correção funcionou...");

try {
    $testAutoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($testAutoload)) {
        // Suprimir erros para teste
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');

        ob_start();
        @include $testAutoload;
        $output = ob_get_clean();

        error_reporting($oldErrorReporting);
        ini_set('display_errors', $oldDisplayErrors);

        if (strpos($output, 'Failed opening required') === false &&
            strpos($output, 'phpunit') === false) {
            printMsg("✓ Autoloader carrega sem erros!", 'success');
        } else {
            printMsg("⚠ Ainda há erros ao carregar autoloader", 'warning');
            printMsg("Execute a reinstalação do vendor via SSH", 'warning');
        }
    }
} catch (Exception $e) {
    printMsg("⚠ Teste de carregamento falhou: " . $e->getMessage(), 'warning');
}

if ($isWeb) {
    echo '</body></html>';
}
