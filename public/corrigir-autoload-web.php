<?php
/**
 * Correção do Autoload via Web
 * Acesse: https://ponto.supportsondagens.com.br/corrigir-autoload-web.php
 */

// Desabilitar timeout
set_time_limit(300);

// Habilitar erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

$rootPath = dirname(__DIR__);
$vendorDir = $rootPath . '/vendor';
$autoloadRealFile = $vendorDir . '/composer/autoload_real.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Correção do Autoload</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .box {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 5px solid #ddd;
        }
        .success {
            background: #e8f5e9;
            border-left-color: #4CAF50;
        }
        .error {
            background: #ffebee;
            border-left-color: #f44336;
        }
        .warning {
            background: #fff3e0;
            border-left-color: #FF9800;
        }
        .info {
            background: #e3f2fd;
            border-left-color: #2196F3;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
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
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        .btn-danger {
            background: #f44336;
            color: white;
        }
        .btn-info {
            background: #2196F3;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Correção Automática do Autoload</h1>

        <?php
        // Se não foi solicitada a correção, mostrar informações
        if (!isset($_POST['action'])) {
        ?>

        <div class="box info">
            <h3>📋 Problema Detectado</h3>
            <p>O autoload do Composer está tentando carregar o PHPUnit (ferramenta de testes), mas os arquivos não estão no servidor.</p>
            <p><strong>Erro:</strong> Failed opening required 'vendor/phpunit/phpunit/src/Framework/Assert/Functions.php'</p>
        </div>

        <div class="box warning">
            <h3>🔍 Diagnóstico</h3>
            <p><strong>Causa:</strong> O vendor/ foi gerado com dependências de desenvolvimento (<code>composer install</code>), mas depois o PHPUnit foi removido manualmente.</p>
            <p><strong>Resultado:</strong> O autoload ainda referencia arquivos que não existem mais.</p>
        </div>

        <div class="box info">
            <h3>✅ Solução</h3>
            <p>Este script vai:</p>
            <ol>
                <li>Fazer backup do arquivo autoload_real.php</li>
                <li>Comentar as referências ao PHPUnit</li>
                <li>Testar se o autoload funciona</li>
                <li>Permitir reverter se algo der errado</li>
            </ol>
        </div>

        <form method="post" onsubmit="return confirm('Tem certeza que deseja aplicar a correção?');">
            <input type="hidden" name="action" value="fix">
            <button type="submit" class="btn btn-primary">🔧 Aplicar Correção Agora</button>
        </form>

        <p style="margin-top: 30px; color: #666; font-size: 14px;">
            <strong>Nota:</strong> Esta é uma correção temporária. Depois, execute <code>composer install --no-dev</code> no servidor para instalar apenas as dependências de produção.
        </p>

        <?php
        } else {
            // Aplicar correção
            echo "<h2>🚀 Aplicando Correção</h2>";

            // 1. Verificar arquivo
            echo "<div class='box info'>";
            echo "<strong>1. Verificando arquivo...</strong><br>";

            if (!file_exists($autoloadRealFile)) {
                echo "<div class='box error'>❌ Arquivo não encontrado: $autoloadRealFile</div>";
                exit;
            }

            echo "✅ Arquivo encontrado: $autoloadRealFile";
            echo "</div>";

            // 2. Backup
            echo "<div class='box info'>";
            echo "<strong>2. Criando backup...</strong><br>";

            $backupFile = $autoloadRealFile . '.backup-' . date('Y-m-d-His');
            if (!copy($autoloadRealFile, $backupFile)) {
                echo "<div class='box error'>❌ Erro ao criar backup</div>";
                exit;
            }

            echo "✅ Backup criado: " . basename($backupFile);
            echo "</div>";

            // 3. Ler arquivo
            $content = file_get_contents($autoloadRealFile);
            $originalSize = strlen($content);

            echo "<div class='box info'>";
            echo "<strong>3. Lendo arquivo...</strong><br>";
            echo "✅ Tamanho: $originalSize bytes";
            echo "</div>";

            // 4. Aplicar correção
            echo "<div class='box info'>";
            echo "<strong>4. Aplicando correção...</strong><br>";

            $lines = explode("\n", $content);
            $modified = false;
            $modifiedLines = [];

            foreach ($lines as $i => $line) {
                // Procurar por qualquer referência ao phpunit com require
                if (preg_match('/require.*phpunit.*Functions\.php/', $line)) {
                    $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                    $lines[$i] = $indent . '// ' . trim($line) . ' // Desabilitado - PHPUnit não instalado';
                    $modified = true;
                    $modifiedLines[] = $i + 1;
                    echo "✅ Linha " . ($i + 1) . " comentada<br>";
                }
            }

            if ($modified) {
                $newContent = implode("\n", $lines);

                // 5. Salvar
                if (file_put_contents($autoloadRealFile, $newContent)) {
                    echo "✅ Arquivo salvo com sucesso";
                    echo "</div>";

                    // 6. Testar
                    echo "<div class='box info'>";
                    echo "<strong>5. Testando autoload...</strong><br>";

                    try {
                        // Limpar cache do opcache se estiver habilitado
                        if (function_exists('opcache_reset')) {
                            opcache_reset();
                        }

                        // Tentar carregar o autoload
                        require $vendorDir . '/autoload.php';

                        echo "✅ <strong>Autoload carregado com SUCESSO!</strong>";
                        echo "</div>";

                        // Sucesso total
                        echo "<div class='box success'>";
                        echo "<h3>✅ CORREÇÃO APLICADA COM SUCESSO!</h3>";
                        echo "<p>O sistema agora deve funcionar normalmente.</p>";
                        echo "<p><strong>Linhas modificadas:</strong> " . implode(', ', $modifiedLines) . "</p>";
                        echo "</div>";

                        echo "<h3>📋 Próximos Passos:</h3>";
                        echo "<div class='box info'>";
                        echo "<ol>";
                        echo "<li>Teste o sistema: <a href='/health' target='_blank'>Health Check</a></li>";
                        echo "<li>Teste o login: <a href='/auth/login' target='_blank'>Login</a></li>";
                        echo "<li>Execute <code>composer install --no-dev</code> via SSH quando possível</li>";
                        echo "</ol>";
                        echo "</div>";

                        echo "<div style='margin-top: 30px;'>";
                        echo "<a href='/health' class='btn btn-primary' target='_blank'>🔍 Testar Health Check</a>";
                        echo "<a href='/auth/login' class='btn btn-info' target='_blank'>🔐 Ir para Login</a>";
                        echo "</div>";

                        // Botão para reverter
                        echo "<div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;'>";
                        echo "<h4>🔄 Reverter Alterações</h4>";
                        echo "<p>Se algo não funcionar, você pode reverter usando o backup:</p>";
                        echo "<form method='post'>";
                        echo "<input type='hidden' name='action' value='restore'>";
                        echo "<input type='hidden' name='backup_file' value='$backupFile'>";
                        echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Reverter as alterações?\")'>⬅️ Reverter para Backup</button>";
                        echo "</form>";
                        echo "</div>";

                    } catch (\Exception $e) {
                        echo "❌ <strong>ERRO ao testar autoload:</strong><br>";
                        echo htmlspecialchars($e->getMessage());
                        echo "</div>";

                        // Restaurar backup automaticamente
                        echo "<div class='box warning'>";
                        echo "<strong>Restaurando backup automaticamente...</strong><br>";
                        if (copy($backupFile, $autoloadRealFile)) {
                            echo "✅ Backup restaurado";
                        } else {
                            echo "❌ Erro ao restaurar backup";
                        }
                        echo "</div>";
                    }
                } else {
                    echo "❌ Erro ao salvar arquivo";
                    echo "</div>";
                }
            } else {
                echo "⚠️ Nenhuma referência ao PHPUnit encontrada no arquivo<br>";
                echo "O arquivo pode já estar corrigido ou ter formato diferente.";
                echo "</div>";

                echo "<div class='box warning'>";
                echo "<h4>🔍 Primeiras 30 linhas do arquivo:</h4>";
                echo "<pre>";
                $showLines = array_slice($lines, 0, 30);
                echo htmlspecialchars(implode("\n", $showLines));
                echo "</pre>";
                echo "</div>";
            }
        }

        // Ação de restaurar
        if (isset($_POST['action']) && $_POST['action'] === 'restore' && isset($_POST['backup_file'])) {
            $backupToRestore = $_POST['backup_file'];

            echo "<h2>🔄 Restaurando Backup</h2>";
            echo "<div class='box info'>";

            if (file_exists($backupToRestore)) {
                if (copy($backupToRestore, $autoloadRealFile)) {
                    echo "✅ <strong>Backup restaurado com sucesso!</strong><br>";
                    echo "O arquivo voltou ao estado original.";
                } else {
                    echo "❌ Erro ao restaurar backup";
                }
            } else {
                echo "❌ Arquivo de backup não encontrado: $backupToRestore";
            }

            echo "</div>";
        }
        ?>

    </div>
</body>
</html>
