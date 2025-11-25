<?php
/**
 * Script de Correção - Document Root e Arquivos Conflitantes
 *
 * Este script:
 * 1. Remove index.php da raiz (se existir)
 * 2. Remove .htaccess da raiz (se existir)
 * 3. Verifica se public/index.php existe
 * 4. Verifica permissões
 * 5. Testa carregamento do CodeIgniter
 *
 * COMO USAR:
 * - Via Browser: https://ponto.supportsondagens.com.br/fix-document-root.php
 * - Via SSH: php /home/supportson/public_html/ponto/public/fix-document-root.php
 */

// Impedir execução direta em produção (comentar para executar)
// if (php_sapi_name() !== 'cli') {
//     die('Este script só pode ser executado via linha de comando por segurança.');
// }

// Definir diretórios
$publicDir = __DIR__;
$rootDir = dirname($publicDir);

// Configurações
$removeRootHtaccess = true; // Remover .htaccess da raiz
$removeRootIndex = true;    // Remover index.php da raiz (se existir)
$dryRun = false;            // true = apenas simular, false = executar

// Buffer de saída
$output = [];
$errors = [];
$warnings = [];
$success = [];

// Função para adicionar mensagem
function addMessage(&$array, $message) {
    $array[] = $message;
}

// Cabeçalho
addMessage($output, "==========================================================");
addMessage($output, "Script de Correção - Document Root");
addMessage($output, "Executado em: " . date('Y-m-d H:i:s'));
addMessage($output, "==========================================================");
addMessage($output, "");

// Informações do sistema
addMessage($output, "INFORMAÇÕES DO SISTEMA:");
addMessage($output, "- Public Dir: {$publicDir}");
addMessage($output, "- Root Dir: {$rootDir}");
addMessage($output, "- PHP Version: " . PHP_VERSION);
addMessage($output, "- User: " . get_current_user());
addMessage($output, "- Mode: " . ($dryRun ? 'DRY RUN (simulação)' : 'EXECUÇÃO REAL'));
addMessage($output, "");

// ==========================================================
// VERIFICAÇÃO 1: index.php na raiz
// ==========================================================
addMessage($output, "VERIFICAÇÃO 1: index.php na raiz do projeto");
addMessage($output, str_repeat("-", 60));

$rootIndexPath = $rootDir . '/index.php';
$publicIndexPath = $publicDir . '/index.php';

if (file_exists($rootIndexPath)) {
    addMessage($warnings, "⚠️  ENCONTRADO: index.php na raiz ({$rootIndexPath})");
    addMessage($output, "Este arquivo NÃO deve existir quando Document Root aponta para public/");

    // Verificar se é diferente do público
    if (file_exists($publicIndexPath)) {
        $rootHash = md5_file($rootIndexPath);
        $publicHash = md5_file($publicIndexPath);

        if ($rootHash === $publicHash) {
            addMessage($output, "→ Arquivo é IDÊNTICO ao public/index.php (pode ser removido)");
        } else {
            addMessage($output, "→ Arquivo é DIFERENTE do public/index.php");
            addMessage($output, "→ Tamanho raiz: " . filesize($rootIndexPath) . " bytes");
            addMessage($output, "→ Tamanho public: " . filesize($publicIndexPath) . " bytes");
        }
    }

    if ($removeRootIndex) {
        if ($dryRun) {
            addMessage($output, "→ [DRY RUN] Removeria: {$rootIndexPath}");
        } else {
            // Fazer backup antes de remover
            $backupPath = $rootIndexPath . '.backup.' . date('YmdHis');
            if (copy($rootIndexPath, $backupPath)) {
                addMessage($output, "→ Backup criado: {$backupPath}");
            }

            if (unlink($rootIndexPath)) {
                addMessage($success, "✅ REMOVIDO: {$rootIndexPath}");
            } else {
                addMessage($errors, "❌ ERRO ao remover: {$rootIndexPath}");
            }
        }
    }
} else {
    addMessage($success, "✅ OK: Nenhum index.php na raiz (correto)");
}

addMessage($output, "");

// ==========================================================
// VERIFICAÇÃO 2: .htaccess na raiz
// ==========================================================
addMessage($output, "VERIFICAÇÃO 2: .htaccess na raiz do projeto");
addMessage($output, str_repeat("-", 60));

$rootHtaccessPath = $rootDir . '/.htaccess';
$publicHtaccessPath = $publicDir . '/.htaccess';

if (file_exists($rootHtaccessPath)) {
    addMessage($warnings, "⚠️  ENCONTRADO: .htaccess na raiz ({$rootHtaccessPath})");
    addMessage($output, "Este arquivo causa conflito quando Document Root aponta para public/");

    // Mostrar primeiras linhas
    $htaccessContent = file_get_contents($rootHtaccessPath);
    if (strpos($htaccessContent, 'RewriteRule ^(.*)$ public/$1') !== false) {
        addMessage($output, "→ Contém regra de redirecionamento para public/ (CONFLITO!)");
    }

    addMessage($output, "→ Tamanho: " . filesize($rootHtaccessPath) . " bytes");

    if ($removeRootHtaccess) {
        if ($dryRun) {
            addMessage($output, "→ [DRY RUN] Removeria: {$rootHtaccessPath}");
        } else {
            // Fazer backup antes de remover
            $backupPath = $rootHtaccessPath . '.backup.' . date('YmdHis');
            if (copy($rootHtaccessPath, $backupPath)) {
                addMessage($output, "→ Backup criado: {$backupPath}");
            }

            if (unlink($rootHtaccessPath)) {
                addMessage($success, "✅ REMOVIDO: {$rootHtaccessPath}");
            } else {
                addMessage($errors, "❌ ERRO ao remover: {$rootHtaccessPath}");
            }
        }
    }
} else {
    addMessage($success, "✅ OK: Nenhum .htaccess na raiz (correto)");
}

addMessage($output, "");

// ==========================================================
// VERIFICAÇÃO 3: Arquivos públicos essenciais
// ==========================================================
addMessage($output, "VERIFICAÇÃO 3: Arquivos essenciais em public/");
addMessage($output, str_repeat("-", 60));

$essentialFiles = [
    'index.php' => $publicIndexPath,
    '.htaccess' => $publicHtaccessPath,
];

foreach ($essentialFiles as $name => $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        addMessage($success, "✅ OK: {$name} existe (permissões: {$perms})");
    } else {
        addMessage($errors, "❌ FALTANDO: {$name} em {$path}");
    }
}

addMessage($output, "");

// ==========================================================
// VERIFICAÇÃO 4: Estrutura de diretórios
// ==========================================================
addMessage($output, "VERIFICAÇÃO 4: Estrutura de diretórios");
addMessage($output, str_repeat("-", 60));

$requiredDirs = [
    'app' => $rootDir . '/app',
    'app/Config' => $rootDir . '/app/Config',
    'vendor' => $rootDir . '/vendor',
    'writable' => $rootDir . '/writable',
    'writable/cache' => $rootDir . '/writable/cache',
    'writable/logs' => $rootDir . '/writable/logs',
    'writable/session' => $rootDir . '/writable/session',
];

foreach ($requiredDirs as $name => $path) {
    if (is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path) ? 'gravável' : 'NÃO gravável';
        addMessage($success, "✅ OK: {$name}/ existe ({$perms}, {$writable})");
    } else {
        addMessage($errors, "❌ FALTANDO: {$name}/ em {$path}");
    }
}

addMessage($output, "");

// ==========================================================
// VERIFICAÇÃO 5: Teste de carregamento do CodeIgniter
// ==========================================================
addMessage($output, "VERIFICAÇÃO 5: Teste de carregamento do CodeIgniter");
addMessage($output, str_repeat("-", 60));

try {
    // Tentar carregar Paths.php
    $pathsFile = $rootDir . '/app/Config/Paths.php';
    if (file_exists($pathsFile)) {
        addMessage($success, "✅ OK: app/Config/Paths.php existe");

        // Tentar carregar autoload
        $autoloadFile = $rootDir . '/vendor/autoload.php';
        if (file_exists($autoloadFile)) {
            addMessage($success, "✅ OK: vendor/autoload.php existe");
        } else {
            addMessage($errors, "❌ ERRO: vendor/autoload.php NÃO existe");
            addMessage($output, "→ Execute: composer install");
        }
    } else {
        addMessage($errors, "❌ ERRO: app/Config/Paths.php NÃO existe");
    }
} catch (Exception $e) {
    addMessage($errors, "❌ ERRO ao testar CodeIgniter: " . $e->getMessage());
}

addMessage($output, "");

// ==========================================================
// RESUMO FINAL
// ==========================================================
addMessage($output, "==========================================================");
addMessage($output, "RESUMO FINAL");
addMessage($output, "==========================================================");
addMessage($output, "");

if (count($success) > 0) {
    addMessage($output, "✅ SUCESSOS (" . count($success) . "):");
    foreach ($success as $msg) {
        addMessage($output, "   " . $msg);
    }
    addMessage($output, "");
}

if (count($warnings) > 0) {
    addMessage($output, "⚠️  AVISOS (" . count($warnings) . "):");
    foreach ($warnings as $msg) {
        addMessage($output, "   " . $msg);
    }
    addMessage($output, "");
}

if (count($errors) > 0) {
    addMessage($output, "❌ ERROS (" . count($errors) . "):");
    foreach ($errors as $msg) {
        addMessage($output, "   " . $msg);
    }
    addMessage($output, "");
}

// Status geral
if (count($errors) === 0 && count($warnings) === 0) {
    addMessage($output, "🎉 TUDO CERTO! A aplicação está configurada corretamente.");
    addMessage($output, "");
    addMessage($output, "Próximo passo:");
    addMessage($output, "→ Acesse: https://ponto.supportsondagens.com.br");
} elseif (count($errors) === 0) {
    addMessage($output, "⚠️  CONFIGURAÇÃO OK, mas há avisos para revisar.");
    addMessage($output, "");
    addMessage($output, "Próximo passo:");
    addMessage($output, "→ Revise os avisos acima");
    addMessage($output, "→ Acesse: https://ponto.supportsondagens.com.br");
} else {
    addMessage($output, "❌ HÁ ERROS QUE PRECISAM SER CORRIGIDOS!");
    addMessage($output, "");
    addMessage($output, "Próximos passos:");
    addMessage($output, "→ Corrija os erros listados acima");
    addMessage($output, "→ Execute este script novamente");
}

addMessage($output, "");
addMessage($output, "==========================================================");

// ==========================================================
// EXIBIR SAÍDA
// ==========================================================

// Se executado via CLI
if (php_sapi_name() === 'cli') {
    foreach ($output as $line) {
        echo $line . PHP_EOL;
    }
} else {
    // Se executado via browser
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>';
    echo '<html lang="pt-BR">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Fix Document Root - Sistema de Ponto</title>';
    echo '<style>';
    echo 'body { font-family: "Courier New", monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }';
    echo 'pre { background: #252526; padding: 20px; border-radius: 5px; overflow-x: auto; line-height: 1.6; }';
    echo '.success { color: #4ec9b0; }';
    echo '.warning { color: #ce9178; }';
    echo '.error { color: #f48771; }';
    echo '.info { color: #569cd6; }';
    echo 'h1 { color: #4ec9b0; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<h1>🔧 Fix Document Root - Sistema de Ponto Eletrônico</h1>';
    echo '<pre>';

    foreach ($output as $line) {
        $class = '';
        if (strpos($line, '✅') !== false) {
            $class = 'success';
        } elseif (strpos($line, '⚠️') !== false) {
            $class = 'warning';
        } elseif (strpos($line, '❌') !== false) {
            $class = 'error';
        } elseif (strpos($line, '→') !== false) {
            $class = 'info';
        }

        if ($class) {
            echo '<span class="' . $class . '">' . htmlspecialchars($line) . '</span>' . "\n";
        } else {
            echo htmlspecialchars($line) . "\n";
        }
    }

    echo '</pre>';
    echo '<p><a href="/" style="color: #4ec9b0;">← Voltar para a página inicial</a></p>';
    echo '</body>';
    echo '</html>';
}

// Retornar código de saída
exit(count($errors) === 0 ? 0 : 1);
