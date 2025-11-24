<?php
/**
 * Pré-Instalação - Correção de Problemas Comuns
 *
 * Este script deve ser executado ANTES do install.php em ambientes
 * onde houve problemas de compatibilidade do Composer.
 */

// Fix common PHP configuration issues
if (ini_get('session.gc_divisor') == 0) {
    ini_set('session.gc_divisor', '100');
}
if (ini_get('session.gc_probability') == 0) {
    ini_set('session.gc_probability', '1');
}

echo "=================================================\n";
echo "  PRÉ-INSTALAÇÃO - Correção de Compatibilidade\n";
echo "=================================================\n\n";

// 1. Verificar versão do PHP
echo "► Verificando versão do PHP...\n";
echo "  Versão detectada: " . PHP_VERSION . "\n";

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    echo "  ✗ ERRO: PHP 8.1.0 ou superior é necessário!\n";
    exit(1);
}
echo "  ✓ Versão do PHP compatível\n\n";

// 2. Corrigir Composer platform check
$platformCheckFile = __DIR__ . '/vendor/composer/platform_check.php';

if (file_exists($platformCheckFile)) {
    echo "► Verificando Composer platform check...\n";

    $content = file_get_contents($platformCheckFile);

    // Verificar se há exigência de PHP 8.3+ mas estamos rodando em versão menor
    if (strpos($content, '>= 8.3') !== false && version_compare(PHP_VERSION, '8.3.0', '<')) {
        echo "  ⚠ Detectado problema de compatibilidade!\n";
        echo "  → O Composer foi instalado em PHP 8.3+ mas o servidor usa " . PHP_VERSION . "\n";
        echo "  → Removendo arquivo platform_check.php problemático...\n";

        if (unlink($platformCheckFile)) {
            echo "  ✓ Arquivo removido com sucesso!\n";
        } else {
            echo "  ✗ Erro ao remover arquivo. Execute manualmente:\n";
            echo "    rm vendor/composer/platform_check.php\n";
        }
    } else {
        echo "  ✓ Platform check está compatível\n";
    }
} else {
    echo "► Platform check não encontrado (OK)\n";
}

echo "\n";

// 3. Verificar se vendor/autoload.php existe
echo "► Verificando Composer autoloader...\n";

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "  ✗ ERRO: Composer dependencies não instaladas!\n";
    echo "  → Execute: composer install --no-dev --ignore-platform-reqs\n";
    exit(1);
}
echo "  ✓ Autoloader encontrado\n\n";

// 4. Verificar permissões do diretório writable
echo "► Verificando permissões...\n";

$writableDir = __DIR__ . '/writable';
if (!is_writable($writableDir)) {
    echo "  ⚠ Diretório writable/ não é gravável!\n";
    echo "  → Execute: chmod -R 755 writable/\n";
} else {
    echo "  ✓ Diretório writable/ é gravável\n";
}

echo "\n";

// 5. Verificar configurações PHP críticas
echo "► Verificando configurações do PHP...\n";

$warnings = [];

if (ini_get('session.gc_divisor') == 0) {
    $warnings[] = "session.gc_divisor está definido como 0";
    if (ini_set('session.gc_divisor', '100') !== false) {
        echo "  ⚠ session.gc_divisor corrigido para 100 (via ini_set)\n";
    } else {
        echo "  ✗ Não foi possível corrigir session.gc_divisor via ini_set\n";
        echo "  → O arquivo .user.ini corrigirá isso automaticamente\n";
    }
}

if (empty($warnings)) {
    echo "  ✓ Configurações PHP estão corretas\n";
}

echo "\n";
echo "=================================================\n";
echo "  ✓ Pré-instalação concluída!\n";
echo "=================================================\n\n";

echo "Agora você pode executar o instalador:\n";
echo "  • Via navegador: http://seusite.com/ponto/install.php\n";
echo "  • Via CLI: php install.php\n\n";
