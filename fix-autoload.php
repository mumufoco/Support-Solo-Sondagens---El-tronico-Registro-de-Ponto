<?php
/**
 * Correção do Autoloader do Composer
 *
 * Regenera o autoloader do Composer corretamente para produção,
 * removendo referências a dependências dev (como PHPUnit)
 */

echo "=================================================\n";
echo "  Correção do Autoloader do Composer\n";
echo "=================================================\n\n";

// Verificar se Composer está disponível
$composerPhar = __DIR__ . '/composer.phar';
$composerInstalled = false;

if (file_exists($composerPhar)) {
    $composerCmd = "php $composerPhar";
    $composerInstalled = true;
} else {
    // Tentar composer global
    exec('which composer 2>/dev/null', $output, $returnCode);
    if ($returnCode === 0 && !empty($output[0])) {
        $composerCmd = 'composer';
        $composerInstalled = true;
    }
}

if (!$composerInstalled) {
    echo "❌ Composer não encontrado!\n\n";
    echo "📋 Solução Manual:\n\n";
    echo "1. Via SSH:\n";
    echo "   cd /home/supportson/public_html/ponto\n";
    echo "   composer dump-autoload --no-dev --optimize\n\n";
    echo "2. Ou baixe composer.phar:\n";
    echo "   curl -sS https://getcomposer.org/installer | php\n";
    echo "   php composer.phar dump-autoload --no-dev --optimize\n\n";
    echo "3. Ou deletar vendor e reinstalar:\n";
    echo "   rm -rf vendor/\n";
    echo "   composer install --no-dev --optimize-autoloader\n\n";
    exit(1);
}

echo "✓ Composer encontrado: $composerCmd\n\n";

// Executar dump-autoload
echo "► Regenerando autoloader para produção...\n";

$cmd = "cd " . escapeshellarg(__DIR__) . " && $composerCmd dump-autoload --no-dev --optimize 2>&1";
exec($cmd, $output, $returnCode);

if ($returnCode === 0) {
    echo "✓ Autoloader regenerado com sucesso!\n\n";
    echo "=================================================\n";
    echo "  ✓ Correção concluída!\n";
    echo "=================================================\n\n";
    echo "Agora você pode acessar o sistema:\n";
    echo "  https://ponto.supportsondagens.com.br\n\n";
} else {
    echo "❌ Erro ao regenerar autoloader:\n";
    echo implode("\n", $output) . "\n\n";

    echo "📋 Solução Manual via SSH:\n";
    echo "cd /home/supportson/public_html/ponto\n";
    echo "composer dump-autoload --no-dev --optimize\n\n";

    echo "Ou reinstale o vendor:\n";
    echo "rm -rf vendor/\n";
    echo "composer install --no-dev --optimize-autoloader\n\n";
}
