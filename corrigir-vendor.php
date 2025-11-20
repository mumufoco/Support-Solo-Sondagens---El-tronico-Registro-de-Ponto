<?php
/**
 * Script de Correção Automática do Vendor
 * Executa via CLI: php corrigir-vendor.php
 */

echo "🔧 CORREÇÃO AUTOMÁTICA DO AUTOLOAD\n";
echo str_repeat("=", 80) . "\n\n";

$rootPath = __DIR__;
$vendorDir = $rootPath . '/vendor';
$autoloadRealFile = $vendorDir . '/composer/autoload_real.php';

// 1. Verificar se o arquivo existe
echo "1. Verificando arquivo autoload_real.php...\n";
if (!file_exists($autoloadRealFile)) {
    echo "❌ ERRO: Arquivo não encontrado: $autoloadRealFile\n";
    exit(1);
}
echo "✅ Arquivo encontrado\n\n";

// 2. Fazer backup
echo "2. Criando backup...\n";
$backupFile = $autoloadRealFile . '.backup-' . date('Y-m-d-His');
if (copy($autoloadRealFile, $backupFile)) {
    echo "✅ Backup criado: $backupFile\n\n";
} else {
    echo "❌ ERRO: Não foi possível criar backup\n";
    exit(1);
}

// 3. Ler o arquivo
echo "3. Lendo arquivo...\n";
$content = file_get_contents($autoloadRealFile);
$originalContent = $content;
echo "✅ Arquivo lido (" . strlen($content) . " bytes)\n\n";

// 4. Procurar e comentar linha do PHPUnit
echo "4. Procurando referência ao PHPUnit...\n";

// Padrões possíveis
$patterns = [
    "/(\s*)(require __DIR__ \. '\\/\\.\\.\/phpunit\\/phpunit\\/src\\/Framework\\/Assert\\/Functions\\.php';)/",
    "/(\s*)(require __DIR__\\s*\\.\\s*'\\/\\.\\.\/phpunit\/phpunit\/src\/Framework\/Assert\/Functions\\.php';)/",
];

$found = false;
foreach ($patterns as $pattern) {
    if (preg_match($pattern, $content)) {
        $replacement = "$1// $2 // Desabilitado - PHPUnit não instalado (dev dependency)";
        $content = preg_replace($pattern, $replacement, $content);
        $found = true;
        echo "✅ Referência ao PHPUnit encontrada e comentada\n\n";
        break;
    }
}

if (!$found) {
    echo "⚠️  Não encontrei a referência ao PHPUnit no formato esperado\n";
    echo "Vou tentar um approach diferente...\n\n";

    // Abordagem alternativa: comentar qualquer linha que mencione phpunit
    $lines = explode("\n", $content);
    $modified = false;

    foreach ($lines as $i => $line) {
        if (stripos($line, 'phpunit') !== false && stripos($line, 'require') !== false) {
            $lines[$i] = '        // ' . trim($line) . ' // Desabilitado - PHPUnit não instalado';
            $modified = true;
            echo "✅ Linha modificada: " . trim($line) . "\n";
        }
    }

    if ($modified) {
        $content = implode("\n", $lines);
        $found = true;
        echo "✅ Referências ao PHPUnit comentadas\n\n";
    }
}

if (!$found) {
    echo "❌ Não foi possível encontrar referências ao PHPUnit\n";
    echo "O arquivo pode já estar corrigido ou ter formato diferente\n\n";

    // Mostrar parte do conteúdo
    echo "Primeiras linhas do arquivo:\n";
    echo str_repeat("-", 80) . "\n";
    $lines = explode("\n", $content);
    foreach (array_slice($lines, 0, 20) as $line) {
        echo $line . "\n";
    }
    echo str_repeat("-", 80) . "\n";
    exit(1);
}

// 5. Salvar o arquivo modificado
echo "5. Salvando arquivo corrigido...\n";
if (file_put_contents($autoloadRealFile, $content)) {
    echo "✅ Arquivo salvo com sucesso\n\n";
} else {
    echo "❌ ERRO: Não foi possível salvar o arquivo\n";
    exit(1);
}

// 6. Verificar se funcionou
echo "6. Verificando correção...\n";
$newContent = file_get_contents($autoloadRealFile);
if (stripos($newContent, '// Desabilitado') !== false) {
    echo "✅ Correção aplicada com sucesso!\n\n";
} else {
    echo "⚠️  Não foi possível confirmar a correção\n\n";
}

// 7. Testar o autoload
echo "7. Testando autoload...\n";
try {
    require $vendorDir . '/autoload.php';
    echo "✅ Autoload carregado com SUCESSO!\n\n";
} catch (\Exception $e) {
    echo "❌ ERRO ao carregar autoload: " . $e->getMessage() . "\n\n";

    // Restaurar backup
    echo "Restaurando backup...\n";
    copy($backupFile, $autoloadRealFile);
    echo "✅ Backup restaurado\n";
    exit(1);
}

echo str_repeat("=", 80) . "\n";
echo "✅ CORREÇÃO CONCLUÍDA COM SUCESSO!\n";
echo str_repeat("=", 80) . "\n\n";

echo "📋 PRÓXIMOS PASSOS:\n";
echo "1. Teste o sistema acessando: https://ponto.supportsondagens.com.br/health\n";
echo "2. Execute composer install --no-dev para instalar as dependências corretas\n";
echo "3. Remova o backup quando confirmar que está funcionando\n\n";

echo "🔄 Para reverter, execute:\n";
echo "cp $backupFile $autoloadRealFile\n\n";
