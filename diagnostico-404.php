#!/usr/bin/env php
<?php
/**
 * DIAGNOSTIC COMPLETO - ERRO 404
 *
 * Testa TUDO desde configuração básica até rotas
 */

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  DIAGNÓSTICO COMPLETO - ERRO 404\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$baseDir = dirname(__DIR__);

// Test 1: Estrutura de diretórios
echo "📁 TEST 1: Estrutura de Diretórios\n";
echo "═══════════════════════════════════════════════════════════════\n";

$dirs = [
    'public' => $baseDir . '/public',
    'app' => $baseDir . '/app',
    'vendor' => $baseDir . '/vendor',
    'writable' => $baseDir . '/writable',
    'system' => $baseDir . '/vendor/codeigniter4/framework/system',
];

foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        echo "✅ $name: " . $path . "\n";
    } else {
        echo "❌ $name: NÃO EXISTE - " . $path . "\n";
    }
}

echo "\n";

// Test 2: Arquivos críticos
echo "📄 TEST 2: Arquivos Críticos\n";
echo "═══════════════════════════════════════════════════════════════\n";

$files = [
    'public/index.php' => $baseDir . '/public/index.php',
    '.htaccess' => $baseDir . '/public/.htaccess',
    'app/Config/Routes.php' => $baseDir . '/app/Config/Routes.php',
    'vendor/autoload.php' => $baseDir . '/vendor/autoload.php',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "✅ $name (size: $size bytes, perms: $perms)\n";
    } else {
        echo "❌ $name: NÃO EXISTE\n";
    }
}

echo "\n";

// Test 3: .htaccess
echo "🔧 TEST 3: Análise do .htaccess\n";
echo "═══════════════════════════════════════════════════════════════\n";

$htaccessFile = $baseDir . '/public/.htaccess';
if (file_exists($htaccessFile)) {
    echo "✅ .htaccess existe\n";
    $content = file_get_contents($htaccessFile);

    // Check for critical directives
    $checks = [
        'RewriteEngine On' => strpos($content, 'RewriteEngine On') !== false,
        'RewriteCond' => strpos($content, 'RewriteCond') !== false,
        'RewriteRule' => strpos($content, 'RewriteRule') !== false,
        'index.php' => strpos($content, 'index.php') !== false,
    ];

    foreach ($checks as $directive => $found) {
        if ($found) {
            echo "   ✅ Contém: $directive\n";
        } else {
            echo "   ❌ NÃO contém: $directive (CRÍTICO!)\n";
        }
    }

    echo "\n   📄 Conteúdo do .htaccess:\n";
    echo "   " . str_repeat("─", 60) . "\n";
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (trim($line) && !str_starts_with(trim($line), '#')) {
            echo "   " . ($i+1) . ": $line\n";
        }
    }
    echo "   " . str_repeat("─", 60) . "\n";
} else {
    echo "❌ .htaccess NÃO EXISTE!\n";
    echo "   PROBLEMA CRÍTICO: Sem .htaccess, o Apache não sabe como rotear!\n";
    echo "\n   ⚡ Criando .htaccess padrão...\n";

    $defaultHtaccess = <<<'HTACCESS'
# Disable directory browsing
Options -Indexes

# Prevent access to system directories
<IfModule authz_core_module>
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>
</IfModule>

# Enable URL rewriting
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirect to https (optional - remove if not using SSL)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]

    # Remove index.php from URL
    RewriteCond %{THE_REQUEST} ^GET.*index\.php [NC]
    RewriteRule (.*?)index\.php/*(.*) /$1$2 [R=301,L]

    # Route everything through index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>

# PHP settings
<IfModule mod_php.c>
    php_value upload_max_filesize 20M
    php_value post_max_size 20M
    php_value max_execution_time 300
    php_value memory_limit 256M
</IfModule>
HTACCESS;

    file_put_contents($htaccessFile, $defaultHtaccess);
    echo "   ✅ .htaccess criado!\n";
}

echo "\n";

// Test 4: Módulos Apache
echo "🔌 TEST 4: Módulos Apache (via php_info)\n";
echo "═══════════════════════════════════════════════════════════════\n";

if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $required = ['mod_rewrite', 'mod_headers'];

    foreach ($required as $mod) {
        if (in_array($mod, $modules)) {
            echo "✅ $mod está ativo\n";
        } else {
            echo "❌ $mod NÃO está ativo (CRÍTICO!)\n";
        }
    }
} else {
    echo "⚠️  apache_get_modules() não disponível (CGI/FastCGI mode)\n";
    echo "   Checando via $_SERVER...\n";

    if (isset($_SERVER['REDIRECT_STATUS'])) {
        echo "   ✅ Redirecionamento funciona\n";
    } else {
        echo "   ⚠️  Não há redirecionamento ativo\n";
    }
}

echo "\n";

// Test 5: public/index.php
echo "📄 TEST 5: public/index.php - Configuração de Sessão\n";
echo "═══════════════════════════════════════════════════════════════\n";

$indexFile = $baseDir . '/public/index.php';
if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);

    $checks = [
        "session_name('ci_session')" => "Configuração de session name",
        "session_save_path" => "Configuração de session save path",
        "Boot::bootWeb" => "Bootstrap correto do CI 4.5+",
    ];

    foreach ($checks as $pattern => $description) {
        if (strpos($content, $pattern) !== false) {
            echo "✅ $description\n";
        } else {
            echo "❌ $description - NÃO ENCONTRADO!\n";
        }
    }
} else {
    echo "❌ public/index.php NÃO EXISTE!\n";
}

echo "\n";

// Test 6: Rotas
echo "🛣️  TEST 6: Configuração de Rotas\n";
echo "═══════════════════════════════════════════════════════════════\n";

$routesFile = $baseDir . '/app/Config/Routes.php';
if (file_exists($routesFile)) {
    echo "✅ Routes.php existe\n";
    $content = file_get_contents($routesFile);

    // Check for common routes
    $routes = [
        '/auth/login' => strpos($content, 'auth/login') !== false,
        '/dashboard' => strpos($content, 'dashboard') !== false,
        '/' => strpos($content, "get('/', ") !== false,
    ];

    foreach ($routes as $route => $found) {
        if ($found) {
            echo "   ✅ Rota configurada: $route\n";
        } else {
            echo "   ⚠️  Rota não encontrada: $route\n";
        }
    }
} else {
    echo "❌ Routes.php NÃO EXISTE!\n";
}

echo "\n";

// Test 7: Teste de acesso direto
echo "🌐 TEST 7: Teste de Acesso Direto\n";
echo "═══════════════════════════════════════════════════════════════\n";

echo "Instruções para testar manualmente:\n\n";

$baseUrl = "https://ponto.supportsondagens.com.br";

echo "1️⃣  Teste index.php diretamente:\n";
echo "   URL: {$baseUrl}/index.php\n";
echo "   Resultado esperado: Página inicial OU redirect\n\n";

echo "2️⃣  Teste com rota:\n";
echo "   URL: {$baseUrl}/auth/login\n";
echo "   Resultado esperado: Página de login\n\n";

echo "3️⃣  Teste sem index.php (rewrite):\n";
echo "   URL: {$baseUrl}\n";
echo "   Resultado esperado: Página inicial\n\n";

echo "❌ Se TODOS dão 404:\n";
echo "   → Problema: Document Root está errado\n";
echo "   → Solução: Document Root deve apontar para: .../ponto/public\n\n";

echo "❌ Se index.php funciona mas rotas dão 404:\n";
echo "   → Problema: mod_rewrite não está funcionando\n";
echo "   → Solução: Verificar .htaccess e mod_rewrite\n\n";

echo "❌ Se index.php dá erro 500:\n";
echo "   → Problema: Erro no código PHP\n";
echo "   → Solução: Verificar logs de erro do PHP\n\n";

// Test 8: Document Root
echo "📂 TEST 8: Document Root\n";
echo "═══════════════════════════════════════════════════════════════\n";

echo "Document Root configurado: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'DESCONHECIDO') . "\n";
echo "Script atual: " . __FILE__ . "\n";
echo "Diretório público deveria ser: {$baseDir}/public\n\n";

$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$expectedRoot = $baseDir . '/public';

if ($docRoot === $expectedRoot) {
    echo "✅ Document Root está CORRETO!\n";
} else {
    echo "❌ Document Root está ERRADO!\n";
    echo "   Atual: $docRoot\n";
    echo "   Esperado: $expectedRoot\n";
    echo "\n   ⚡ AÇÃO NECESSÁRIA:\n";
    echo "   Configure o Apache/Nginx para apontar para: $expectedRoot\n";
}

echo "\n";

// Test 9: Permissões
echo "🔐 TEST 9: Permissões de Arquivos\n";
echo "═══════════════════════════════════════════════════════════════\n";

$checkPerms = [
    'public/index.php' => $baseDir . '/public/index.php',
    'public/.htaccess' => $baseDir . '/public/.htaccess',
    'writable' => $baseDir . '/writable',
    'writable/session' => $baseDir . '/writable/session',
];

foreach ($checkPerms as $name => $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $readable = is_readable($path) ? '✅' : '❌';
        $writable = is_writable($path) ? '✅' : '❌';

        echo "$name:\n";
        echo "   Permissões: $perms\n";
        echo "   Legível: $readable | Gravável: $writable\n";
    } else {
        echo "❌ $name: NÃO EXISTE\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  FIM DO DIAGNÓSTICO\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

echo "🔧 PRÓXIMOS PASSOS:\n\n";
echo "1. Verifique se o Document Root está correto no servidor web\n";
echo "2. Certifique-se que mod_rewrite está ativo no Apache\n";
echo "3. Verifique se .htaccess existe e tem permissão de leitura\n";
echo "4. Teste acessar /index.php diretamente no navegador\n";
echo "5. Verifique logs de erro: tail -f /var/log/apache2/error.log\n";
echo "\n";
