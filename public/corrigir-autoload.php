<?php
/**
 * Corrigir Autoload do Composer
 * Este script remove referências a dependências de desenvolvimento que não estão no servidor
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Correção do Autoload do Composer</h1>";
echo "<hr>";

$rootPath = dirname(__DIR__);

echo "<h2>1. Diagnóstico</h2>";
echo "<pre>";

$vendorDir = $rootPath . '/vendor';
echo "Diretório vendor: $vendorDir\n";
echo "Existe: " . (is_dir($vendorDir) ? 'SIM' : 'NÃO') . "\n\n";

// Verificar se PHPUnit existe
$phpunitDir = $vendorDir . '/phpunit/phpunit';
echo "PHPUnit instalado: " . (is_dir($phpunitDir) ? 'SIM' : 'NÃO') . "\n";

if (!is_dir($phpunitDir)) {
    echo "❌ PHPUnit NÃO está instalado (dependência de desenvolvimento)\n";
    echo "⚠️  Mas o autoload está configurado para carregá-lo!\n\n";
}

echo "</pre>";

echo "<h2>2. Soluções Disponíveis</h2>";
echo "<div style='background: #f5f5f5; padding: 20px; margin: 20px 0; border-left: 4px solid #2196F3;'>";

echo "<h3>Opção 1: Executar composer install (RECOMENDADO)</h3>";
echo "<p>Execute via SSH no servidor:</p>";
echo "<pre style='background: #2d2d2d; color: #fff; padding: 15px; border-radius: 5px;'>";
echo "cd /home/supportson/public_html/ponto\n";
echo "composer install --no-dev --optimize-autoloader\n";
echo "</pre>";

echo "<h3>Opção 2: Upload do vendor/ completo</h3>";
echo "<p>Faça upload da pasta <code>vendor/</code> completa via FTP/SFTP</p>";

echo "<h3>Opção 3: Remover referências (TEMPORÁRIO)</h3>";
echo "<p>Este script pode tentar corrigir o autoload automaticamente.</p>";
echo "<form method='post' style='margin-top: 15px;'>";
echo "<button type='submit' name='action' value='fix_autoload' style='background: #ff9800; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px;'>🔧 Corrigir Autoload Agora</button>";
echo "<p style='color: #666; font-size: 13px; margin-top: 10px;'>Esta opção é temporária. Você ainda precisará executar composer install posteriormente.</p>";
echo "</form>";

echo "</div>";

// Se o usuário clicou para corrigir
if (isset($_POST['action']) && $_POST['action'] === 'fix_autoload') {
    echo "<h2>3. Aplicando Correção</h2>";
    echo "<pre>";

    $autoloadRealFile = $vendorDir . '/composer/autoload_real.php';

    if (!file_exists($autoloadRealFile)) {
        echo "❌ Arquivo autoload_real.php não encontrado!\n";
    } else {
        // Fazer backup
        $backupFile = $autoloadRealFile . '.backup';
        if (!file_exists($backupFile)) {
            copy($autoloadRealFile, $backupFile);
            echo "✅ Backup criado: " . basename($backupFile) . "\n\n";
        }

        // Ler o arquivo
        $content = file_get_contents($autoloadRealFile);

        // Comentar a linha que carrega o PHPUnit
        $pattern = "/(require __DIR__ \. '\/\.\.\/phpunit\/phpunit\/src\/Framework\/Assert\/Functions\.php';)/";
        $replacement = "// $1 // Comentado temporariamente - execute composer install";

        $newContent = preg_replace($pattern, $replacement, $content);

        if ($newContent !== $content) {
            file_put_contents($autoloadRealFile, $newContent);
            echo "✅ Arquivo autoload_real.php corrigido!\n";
            echo "✅ Referência ao PHPUnit comentada\n\n";

            echo "<div style='background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin: 20px 0;'>";
            echo "<strong>✅ Correção Aplicada!</strong><br>";
            echo "O sistema agora deve funcionar. Teste acessando:<br>";
            echo "<a href='/health' target='_blank' style='color: #2196F3;'>https://ponto.supportsondagens.com.br/health</a><br>";
            echo "<a href='/auth/login' target='_blank' style='color: #2196F3;'>https://ponto.supportsondagens.com.br/auth/login</a><br><br>";
            echo "<strong>⚠️ IMPORTANTE:</strong> Esta é uma correção temporária.<br>";
            echo "Execute <code>composer install --no-dev</code> no servidor assim que possível.";
            echo "</div>";
        } else {
            echo "⚠️  Não foi possível encontrar a linha para corrigir\n";
            echo "Conteúdo do arquivo autoload_real.php:\n\n";
            echo htmlspecialchars(substr($content, 0, 500)) . "\n...\n";
        }
    }

    echo "</pre>";
}

echo "<h2>4. Verificar Estrutura do Vendor</h2>";
echo "<pre>";

$requiredDirs = [
    'codeigniter4/framework',
    'composer',
    'autoload.php',
];

echo "Verificando arquivos essenciais:\n\n";
foreach ($requiredDirs as $dir) {
    $path = $vendorDir . '/' . $dir;
    $exists = (is_dir($path) || file_exists($path));
    $icon = $exists ? '✅' : '❌';
    echo "$icon vendor/$dir\n";
}

echo "</pre>";

echo "<h2>5. Próximos Passos</h2>";
echo "<div style='background: #fff3e0; padding: 15px; border-left: 4px solid #ff9800;'>";
echo "<ol>";
echo "<li>Clique no botão <strong>Corrigir Autoload</strong> acima (correção temporária)</li>";
echo "<li>Teste se o sistema funciona</li>";
echo "<li>Execute <code>composer install --no-dev</code> no servidor via SSH (solução definitiva)</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><a href='/checagem-instalacao.php'>← Voltar para Checagem</a> | ";
echo "<a href='/diagnostico-erro-500.php'>Ver Diagnóstico Completo</a></p>";
