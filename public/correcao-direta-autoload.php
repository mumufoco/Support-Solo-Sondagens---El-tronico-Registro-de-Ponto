<?php
/**
 * Correção Direta do Autoload
 * Usa o caminho absoluto correto
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Caminho absoluto baseado no erro mostrado
$composerDir = '/home/supportson/public_html/ponto/vendor/composer';
$autoloadRealFile = $composerDir . '/autoload_real.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>🔧 Correção Direta do Autoload</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .box {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 5px solid #ddd;
        }
        .success { background: #e8f5e9; border-left-color: #4caf50; }
        .error { background: #ffebee; border-left-color: #f44336; }
        .warning { background: #fff3e0; border-left-color: #ff9800; }
        .info { background: #e3f2fd; border-left-color: #2196f3; }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
            white-space: pre-wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px 5px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .btn-primary { background: #4caf50; color: white; }
        .btn-danger { background: #f44336; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Correção Direta do Autoload</h1>

        <?php
        if (!isset($_POST['action'])) {
        ?>

        <div class="box info">
            <h3>📋 Informações</h3>
            <p><strong>Arquivo a corrigir:</strong><br><code><?= $autoloadRealFile ?></code></p>
            <p><strong>Verificando existência:</strong>
            <?php
            if (file_exists($autoloadRealFile)) {
                echo "✅ Arquivo existe (" . filesize($autoloadRealFile) . " bytes)";
            } else {
                echo "❌ Arquivo NÃO existe!";
            }
            ?>
            </p>
        </div>

        <?php if (file_exists($autoloadRealFile)): ?>

        <div class="box warning">
            <h3>🔍 Conteúdo Completo do Arquivo</h3>
            <p>Mostrando todas as linhas para encontrar a referência ao PHPUnit:</p>
            <pre><?php
            $content = file_get_contents($autoloadRealFile);
            $lines = explode("\n", $content);

            foreach ($lines as $i => $line) {
                $lineNum = str_pad($i + 1, 3, '0', STR_PAD_LEFT);

                // Destacar linhas com phpunit
                if (stripos($line, 'phpunit') !== false) {
                    echo "<span style='background: yellow; color: black;'>>>> $lineNum: " . htmlspecialchars($line) . "</span>\n";
                } else {
                    echo "$lineNum: " . htmlspecialchars($line) . "\n";
                }
            }
            ?></pre>
        </div>

        <div class="box info">
            <h3>✅ O que fazer agora:</h3>
            <p>1. Procure no conteúdo acima por linhas destacadas em <span style="background: yellow;">AMARELO</span></p>
            <p>2. Se encontrar referências ao PHPUnit, clique no botão abaixo para corrigir</p>
            <p>3. Se NÃO encontrar nada em amarelo, o problema está em outro arquivo</p>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="fix">
            <button type="submit" class="btn btn-primary">🔧 Comentar Referências ao PHPUnit</button>
        </form>

        <?php else: ?>

        <div class="box error">
            <h3>❌ Erro</h3>
            <p>O arquivo autoload_real.php não foi encontrado no caminho esperado.</p>
            <p>Caminho tentado: <code><?= $autoloadRealFile ?></code></p>
        </div>

        <?php endif; ?>

        <?php
        } else {
            // Aplicar correção
            echo "<h2>🔧 Aplicando Correção</h2>";

            if (!file_exists($autoloadRealFile)) {
                echo "<div class='box error'>❌ Arquivo não encontrado</div>";
                exit;
            }

            // Backup
            $backupFile = $autoloadRealFile . '.backup-' . date('YmdHis');
            if (copy($autoloadRealFile, $backupFile)) {
                echo "<div class='box success'>✅ Backup criado: " . basename($backupFile) . "</div>";
            } else {
                echo "<div class='box error'>❌ Erro ao criar backup</div>";
                exit;
            }

            // Ler arquivo
            $content = file_get_contents($autoloadRealFile);
            $lines = explode("\n", $content);

            echo "<div class='box info'>";
            echo "<h3>Processando linhas...</h3>";

            $modified = false;
            $modifiedLines = [];

            foreach ($lines as $i => $line) {
                // Buscar por qualquer referência ao phpunit
                if (stripos($line, 'phpunit') !== false) {
                    $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));

                    // Se tem require, comentar completamente
                    if (stripos($line, 'require') !== false) {
                        $lines[$i] = $indent . '// ' . trim($line) . ' // Desabilitado - PHPUnit não instalado';
                        $modified = true;
                        $modifiedLines[] = $i + 1;
                        echo "<p>✅ Linha " . ($i + 1) . " comentada: <code>" . htmlspecialchars(trim($line)) . "</code></p>";
                    }
                    // Se não tem require mas tem phpunit, também comentar
                    else if (!empty(trim($line))) {
                        $lines[$i] = $indent . '// ' . trim($line) . ' // Desabilitado - PHPUnit não instalado';
                        $modified = true;
                        $modifiedLines[] = $i + 1;
                        echo "<p>✅ Linha " . ($i + 1) . " comentada: <code>" . htmlspecialchars(trim($line)) . "</code></p>";
                    }
                }
            }

            echo "</div>";

            if ($modified) {
                // Salvar
                $newContent = implode("\n", $lines);
                if (file_put_contents($autoloadRealFile, $newContent)) {
                    echo "<div class='box success'>";
                    echo "<h3>✅ CORREÇÃO APLICADA COM SUCESSO!</h3>";
                    echo "<p><strong>Linhas modificadas:</strong> " . implode(', ', $modifiedLines) . "</p>";
                    echo "<p>Total de linhas modificadas: " . count($modifiedLines) . "</p>";
                    echo "</div>";

                    // Limpar cache do opcache
                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                        echo "<div class='box info'>✅ Cache do OPcache limpo</div>";
                    }

                    echo "<div class='box success'>";
                    echo "<h3>📋 Próximos Passos:</h3>";
                    echo "<ol>";
                    echo "<li><a href='/health' target='_blank' class='btn btn-primary' style='display: inline-block; margin: 10px 0;'>🔍 Testar Health Check</a></li>";
                    echo "<li><a href='/auth/login' target='_blank' class='btn btn-primary' style='display: inline-block; margin: 10px 0;'>🔐 Testar Login</a></li>";
                    echo "<li><a href='/diagnostico-erro-500.php' target='_blank' class='btn btn-primary' style='display: inline-block; margin: 10px 0;'>🔍 Ver Diagnóstico Completo</a></li>";
                    echo "</ol>";
                    echo "</div>";

                } else {
                    echo "<div class='box error'>❌ Erro ao salvar arquivo</div>";
                }
            } else {
                echo "<div class='box warning'>";
                echo "<h3>⚠️ Nenhuma Referência ao PHPUnit Encontrada</h3>";
                echo "<p>O arquivo não contém referências ao PHPUnit, ou elas já foram corrigidas.</p>";
                echo "<p>O erro pode estar vindo de:</p>";
                echo "<ul>";
                echo "<li>Cache do opcache</li>";
                echo "<li>Outro arquivo do Composer (autoload_files.php, autoload_static.php)</li>";
                echo "<li>Configuração do servidor</li>";
                echo "</ul>";
                echo "</div>";

                // Limpar cache mesmo assim
                if (function_exists('opcache_reset')) {
                    opcache_reset();
                    echo "<div class='box info'>✅ Cache do OPcache limpo (tente agora)</div>";
                }
            }
        }
        ?>

        <hr>
        <p><a href="/checagem-instalacao.php">← Voltar para Checagem</a></p>
    </div>
</body>
</html>
