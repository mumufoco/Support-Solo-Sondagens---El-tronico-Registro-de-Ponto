<?php
/**
 * Buscar referências ao PHPUnit em todos os arquivos do Composer
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$rootPath = dirname(__DIR__);
$vendorDir = $rootPath . '/vendor';
$composerDir = $vendorDir . '/composer';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>🔍 Buscar PHPUnit nos Arquivos do Composer</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 1200px;
            margin: 0 auto;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .highlight {
            background: yellow;
            color: black;
        }
        .file-section {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #2196F3;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Buscar Referências ao PHPUnit</h1>

        <?php
        echo "<h2>Arquivos do Composer a verificar:</h2>";

        $filesToCheck = [
            'autoload_real.php',
            'autoload_files.php',
            'autoload_static.php',
            'autoload_classmap.php',
            'autoload_namespaces.php',
            'autoload_psr4.php',
        ];

        $foundReferences = [];

        foreach ($filesToCheck as $filename) {
            $filepath = $composerDir . '/' . $filename;

            echo "<div class='file-section'>";
            echo "<h3>📄 $filename</h3>";

            if (file_exists($filepath)) {
                echo "<p>✅ Arquivo existe (" . filesize($filepath) . " bytes)</p>";

                $content = file_get_contents($filepath);
                $lines = explode("\n", $content);

                // Buscar por "phpunit" (case insensitive)
                $matches = [];
                foreach ($lines as $lineNum => $line) {
                    if (stripos($line, 'phpunit') !== false) {
                        $matches[] = [
                            'line' => $lineNum + 1,
                            'content' => $line
                        ];
                    }
                }

                if (!empty($matches)) {
                    echo "<p><strong style='color: #f44336;'>❌ ENCONTROU " . count($matches) . " referência(s) ao PHPUnit!</strong></p>";
                    echo "<pre>";
                    foreach ($matches as $match) {
                        echo "Linha {$match['line']}: " . htmlspecialchars($match['content']) . "\n";
                    }
                    echo "</pre>";

                    $foundReferences[$filename] = $matches;
                } else {
                    echo "<p style='color: #4caf50;'>✅ Nenhuma referência ao PHPUnit</p>";
                }

                // Mostrar primeiras 50 linhas
                echo "<details>";
                echo "<summary>Ver primeiras 50 linhas</summary>";
                echo "<pre>";
                echo htmlspecialchars(implode("\n", array_slice($lines, 0, 50)));
                echo "</pre>";
                echo "</details>";

            } else {
                echo "<p style='color: #999;'>⚠️ Arquivo não existe</p>";
            }

            echo "</div>";
        }

        // Resumo
        echo "<hr>";
        echo "<h2>📊 Resumo</h2>";

        if (empty($foundReferences)) {
            echo "<div style='background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50;'>";
            echo "<p><strong>✅ Nenhuma referência ao PHPUnit encontrada nos arquivos do Composer!</strong></p>";
            echo "<p>O problema pode estar em outro lugar. Vamos verificar:</p>";
            echo "<ol>";
            echo "<li>Arquivo autoload.php principal</li>";
            echo "<li>Possível cache do opcache</li>";
            echo "<li>Erro vindo de outro local</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
            echo "<p><strong>❌ REFERÊNCIAS ENCONTRADAS!</strong></p>";
            echo "<p>Arquivos com referências ao PHPUnit:</p>";
            echo "<ul>";
            foreach ($foundReferences as $file => $matches) {
                echo "<li><strong>$file</strong> - " . count($matches) . " referência(s)</li>";
            }
            echo "</ul>";

            echo "<form method='post'>";
            echo "<input type='hidden' name='action' value='fix_all'>";
            echo "<input type='hidden' name='files' value='" . htmlspecialchars(json_encode(array_keys($foundReferences))) . "'>";
            echo "<button type='submit' style='background: #4caf50; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 15px;'>🔧 Corrigir Todos os Arquivos</button>";
            echo "</form>";
            echo "</div>";
        }

        // Se foi solicitada a correção
        if (isset($_POST['action']) && $_POST['action'] === 'fix_all') {
            echo "<hr>";
            echo "<h2>🔧 Aplicando Correções</h2>";

            $filesToFix = json_decode($_POST['files'], true);

            foreach ($filesToFix as $filename) {
                $filepath = $composerDir . '/' . $filename;

                echo "<div class='file-section'>";
                echo "<h3>Corrigindo: $filename</h3>";

                // Backup
                $backupFile = $filepath . '.backup-' . date('YmdHis');
                copy($filepath, $backupFile);
                echo "<p>✅ Backup: " . basename($backupFile) . "</p>";

                // Ler arquivo
                $content = file_get_contents($filepath);
                $lines = explode("\n", $content);
                $modified = false;

                // Comentar linhas com phpunit
                foreach ($lines as $i => $line) {
                    if (stripos($line, 'phpunit') !== false && stripos($line, 'require') !== false) {
                        $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                        $lines[$i] = $indent . '// ' . trim($line) . ' // Desabilitado - PHPUnit não instalado';
                        $modified = true;
                        echo "<p>✅ Linha " . ($i + 1) . " comentada</p>";
                    }
                }

                if ($modified) {
                    file_put_contents($filepath, implode("\n", $lines));
                    echo "<p><strong style='color: #4caf50;'>✅ Arquivo corrigido!</strong></p>";
                } else {
                    echo "<p>⚠️ Nenhuma modificação necessária</p>";
                }

                echo "</div>";
            }

            echo "<div style='background: #e8f5e9; padding: 20px; border-left: 4px solid #4caf50; margin-top: 20px;'>";
            echo "<h3>✅ CORREÇÕES APLICADAS!</h3>";
            echo "<p>Teste o sistema agora:</p>";
            echo "<p>";
            echo "<a href='/health' target='_blank' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔍 Testar Health</a>";
            echo "<a href='/auth/login' target='_blank' style='background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔐 Testar Login</a>";
            echo "</p>";
            echo "</div>";
        }

        // Verificar autoload principal também
        echo "<hr>";
        echo "<h2>📄 Verificando autoload.php principal</h2>";
        $autoloadMain = $vendorDir . '/autoload.php';

        if (file_exists($autoloadMain)) {
            $content = file_get_contents($autoloadMain);
            echo "<pre>";
            echo htmlspecialchars($content);
            echo "</pre>";
        }
        ?>

        <hr>
        <p><a href="/checagem-instalacao.php">← Voltar para Checagem</a></p>
    </div>
</body>
</html>
