<?php
/**
 * Script de Diagnóstico - Erro 403
 *
 * Acesse este arquivo pelo navegador para diagnosticar problemas de 403
 * URL: https://ponto.supportsondagens.com.br/diagnostico-403.php
 */

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico 403 - Sistema de Ponto</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 30px;
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        h2 {
            color: #764ba2;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-weight: bold;
        }
        .ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }
        tr:hover { background: #f5f5f5; }
        .code {
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        .section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .btn:hover { background: #764ba2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico Completo - Erro 403</h1>

        <?php
        // Funções auxiliares
        function checkStatus($condition, $okMsg, $errorMsg) {
            if ($condition) {
                echo "<div class='status ok'>✅ $okMsg</div>";
                return true;
            } else {
                echo "<div class='status error'>❌ $errorMsg</div>";
                return false;
            }
        }

        function warningStatus($msg) {
            echo "<div class='status warning'>⚠️ $msg</div>";
        }

        function infoStatus($msg) {
            echo "<div class='status info'>ℹ️ $msg</div>";
        }

        // 1. INFORMAÇÕES DO SERVIDOR
        echo "<h2>1. Informações do Servidor</h2>";
        echo "<div class='section'>";
        echo "<table>";
        echo "<tr><th>Configuração</th><th>Valor</th></tr>";
        echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
        echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td>Document Root</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td>Script Filename</td><td>" . __FILE__ . "</td></tr>";
        echo "<tr><td>Current User</td><td>" . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user()) . "</td></tr>";
        echo "</table>";
        echo "</div>";

        // 2. VERIFICAÇÃO DE ARQUIVOS CRÍTICOS
        echo "<h2>2. Arquivos Críticos</h2>";
        $baseDir = dirname(__DIR__);
        $criticalFiles = [
            'index.php' => __DIR__ . '/index.php',
            '.htaccess' => __DIR__ . '/.htaccess',
            'app/Config/App.php' => $baseDir . '/app/Config/App.php',
            'app/Config/Routes.php' => $baseDir . '/app/Config/Routes.php',
            '.env' => $baseDir . '/.env',
        ];

        foreach ($criticalFiles as $name => $path) {
            if (file_exists($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $readable = is_readable($path);
                if ($readable) {
                    echo "<div class='status ok'>✅ $name existe e é legível (Permissões: $perms)</div>";
                } else {
                    echo "<div class='status error'>❌ $name existe mas NÃO é legível (Permissões: $perms)</div>";
                }
            } else {
                echo "<div class='status error'>❌ $name NÃO EXISTE em: $path</div>";
            }
        }

        // 3. VERIFICAÇÃO DE DIRETÓRIOS
        echo "<h2>3. Diretórios Críticos</h2>";
        $criticalDirs = [
            'public/' => __DIR__,
            'writable/' => $baseDir . '/writable',
            'writable/cache/' => $baseDir . '/writable/cache',
            'writable/logs/' => $baseDir . '/writable/logs',
            'writable/session/' => $baseDir . '/writable/session',
            'app/' => $baseDir . '/app',
            'vendor/' => $baseDir . '/vendor',
        ];

        foreach ($criticalDirs as $name => $path) {
            if (is_dir($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $writable = is_writable($path);
                if ($writable) {
                    echo "<div class='status ok'>✅ $name existe e é gravável (Permissões: $perms)</div>";
                } else {
                    echo "<div class='status warning'>⚠️ $name existe mas NÃO é gravável (Permissões: $perms)</div>";
                }
            } else {
                echo "<div class='status error'>❌ $name NÃO EXISTE em: $path</div>";
            }
        }

        // 4. TESTE DE INCLUDE
        echo "<h2>4. Teste de Carregamento de Arquivos</h2>";
        $testFiles = [
            'vendor/autoload.php' => $baseDir . '/vendor/autoload.php',
            'app/Config/Paths.php' => $baseDir . '/app/Config/Paths.php',
        ];

        foreach ($testFiles as $name => $path) {
            try {
                if (file_exists($path)) {
                    require_once $path;
                    echo "<div class='status ok'>✅ $name carregado com sucesso</div>";
                } else {
                    echo "<div class='status error'>❌ $name não encontrado</div>";
                }
            } catch (Exception $e) {
                echo "<div class='status error'>❌ Erro ao carregar $name: " . $e->getMessage() . "</div>";
            }
        }

        // 5. VARIÁVEIS DE AMBIENTE
        echo "<h2>5. Variáveis de Ambiente</h2>";
        $envFile = $baseDir . '/.env';
        if (file_exists($envFile)) {
            echo "<div class='status ok'>✅ Arquivo .env existe</div>";
            echo "<div class='status info'>ℹ️ Conteúdo do .env (primeiras linhas, sem senhas):</div>";
            $envContent = file_get_contents($envFile);
            $lines = explode("\n", $envContent);
            $safeLines = array_filter(array_slice($lines, 0, 10), function($line) {
                return !preg_match('/password|secret|key/i', $line) && trim($line) !== '';
            });
            echo "<div class='code'>" . htmlspecialchars(implode("\n", $safeLines)) . "\n...</div>";
        } else {
            echo "<div class='status warning'>⚠️ Arquivo .env NÃO existe</div>";
        }

        // 6. TESTE APACHE/NGINX
        echo "<h2>6. Configuração do Servidor Web</h2>";
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            $hasRewrite = in_array('mod_rewrite', $modules);
            checkStatus($hasRewrite,
                "mod_rewrite está ativo",
                "mod_rewrite NÃO está ativo (necessário para URLs amigáveis)");
        } else {
            infoStatus("Não é possível verificar módulos Apache (pode ser Nginx ou PHP-FPM)");
        }

        // 7. TESTE DE ACESSO AO CODEIGNITER
        echo "<h2>7. Teste de Inicialização do CodeIgniter</h2>";
        try {
            $pathsConfig = $baseDir . '/app/Config/Paths.php';
            if (file_exists($pathsConfig)) {
                require_once $pathsConfig;
                $paths = new Config\Paths();
                echo "<div class='status ok'>✅ Paths configurado corretamente</div>";
                echo "<div class='code'>";
                echo "System Path: " . $paths->systemDirectory . "<br>";
                echo "App Path: " . $paths->appDirectory . "<br>";
                echo "Writable Path: " . $paths->writableDirectory . "<br>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='status error'>❌ Erro ao carregar Paths: " . $e->getMessage() . "</div>";
        }

        // 8. RECOMENDAÇÕES
        echo "<h2>8. Recomendações para Resolver o 403</h2>";
        echo "<div class='section'>";
        echo "<h3>Possíveis Causas do Erro 403:</h3>";
        echo "<ol>";
        echo "<li><strong>Permissões de Arquivo:</strong> O index.php deve ter permissão 644 ou 755</li>";
        echo "<li><strong>Permissões de Diretório:</strong> O diretório public/ deve ter permissão 755</li>";
        echo "<li><strong>Proprietário dos Arquivos:</strong> Os arquivos devem pertencer ao usuário correto do servidor web</li>";
        echo "<li><strong>.htaccess:</strong> Pode estar bloqueando acesso ou com configurações incorretas</li>";
        echo "<li><strong>Diretiva Options:</strong> O servidor pode não permitir a diretiva 'Options -Indexes'</li>";
        echo "<li><strong>AllowOverride:</strong> O servidor precisa ter 'AllowOverride All' para .htaccess funcionar</li>";
        echo "</ol>";

        echo "<h3>Comandos para Executar via SSH:</h3>";
        echo "<div class='code'>";
        echo "# Corrigir permissões de arquivos<br>";
        echo "find /caminho/para/projeto/public -type f -exec chmod 644 {} \\;<br>";
        echo "<br>";
        echo "# Corrigir permissões de diretórios<br>";
        echo "find /caminho/para/projeto/public -type d -exec chmod 755 {} \\;<br>";
        echo "<br>";
        echo "# Corrigir permissões do writable<br>";
        echo "chmod -R 755 /caminho/para/projeto/writable<br>";
        echo "<br>";
        echo "# Verificar proprietário (deve ser o usuário do servidor web)<br>";
        echo "ls -la /caminho/para/projeto/public/<br>";
        echo "</div>";

        echo "<h3>Teste Direto do Index.php:</h3>";
        echo "<div class='code'>";
        echo "URL para testar: <a href='index.php'>index.php</a><br>";
        echo "Se este link funcionar, o problema está no .htaccess ou mod_rewrite<br>";
        echo "</div>";

        echo "</div>";

        // 9. PRÓXIMOS PASSOS
        echo "<h2>9. Próximos Passos</h2>";
        echo "<div class='section'>";
        echo "<p><strong>Se você tem acesso SSH:</strong></p>";
        echo "<ol>";
        echo "<li>Execute os comandos de correção de permissões acima</li>";
        echo "<li>Verifique os logs de erro: <code>tail -f /caminho/logs/error_log</code></li>";
        echo "<li>Teste o acesso direto ao index.php</li>";
        echo "</ol>";

        echo "<p><strong>Se você usa cPanel:</strong></p>";
        echo "<ol>";
        echo "<li>Acesse o Gerenciador de Arquivos</li>";
        echo "<li>Selecione public/ e ajuste permissões para 755</li>";
        echo "<li>Selecione index.php e ajuste permissões para 644</li>";
        echo "<li>Verifique se o .htaccess está presente</li>";
        echo "</ol>";
        echo "</div>";

        ?>

        <div style="margin-top: 30px; padding: 20px; background: #e8f4f8; border-radius: 5px;">
            <h3>📞 Informações de Suporte</h3>
            <p>Envie um print desta tela para análise mais detalhada.</p>
            <p><strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
