<?php
/**
 * REPARAR AUTOMATICAMENTE
 * Este script executa a correção automaticamente ao ser acessado
 */

// Configurações
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

// Caminhos absolutos
$composerDir = '/home/supportson/public_html/ponto/vendor/composer';
$autoloadRealFile = $composerDir . '/autoload_real.php';

// HTML início
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>🔧 Reparando Automaticamente...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            background: white;
            color: #333;
            padding: 40px;
            border-radius: 15px;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
        }
        .step {
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 5px solid #ddd;
        }
        .success {
            background: #e8f5e9;
            border-left-color: #4caf50;
        }
        .error {
            background: #ffebee;
            border-left-color: #f44336;
        }
        .warning {
            background: #fff3e0;
            border-left-color: #ff9800;
        }
        .info {
            background: #e3f2fd;
            border-left-color: #2196f3;
        }
        .progress {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px 5px;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
        }
        .icon {
            font-size: 24px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Reparação Automática do Sistema</h1>
        <p style="color: #666; margin-bottom: 30px;">Executando correções automaticamente...</p>

        <?php
        $totalSteps = 0;
        $successSteps = 0;
        $errors = [];

        // PASSO 1: Verificar arquivo
        echo "<div class='step info'>";
        echo "<div class='icon'>📁</div>";
        echo "<strong>PASSO 1: Verificando arquivo autoload_real.php</strong><br><br>";
        echo "Caminho: <code>$autoloadRealFile</code><br>";

        $totalSteps++;
        if (!file_exists($autoloadRealFile)) {
            echo "<br>❌ <strong>ERRO:</strong> Arquivo não encontrado!<br>";
            echo "Por favor, verifique se o caminho está correto.";
            echo "</div>";
            $errors[] = "Arquivo autoload_real.php não encontrado";
        } else {
            $filesize = filesize($autoloadRealFile);
            echo "✅ Arquivo encontrado!<br>";
            echo "Tamanho: " . number_format($filesize) . " bytes<br>";
            echo "Permissões: " . substr(sprintf('%o', fileperms($autoloadRealFile)), -4);
            echo "</div>";
            $successSteps++;

            // PASSO 2: Criar backup
            echo "<div class='step info'>";
            echo "<div class='icon'>💾</div>";
            echo "<strong>PASSO 2: Criando backup de segurança</strong><br><br>";

            $totalSteps++;
            $backupFile = $autoloadRealFile . '.backup-autorepair-' . date('YmdHis');
            if (copy($autoloadRealFile, $backupFile)) {
                echo "✅ Backup criado com sucesso!<br>";
                echo "Local: <code>" . basename($backupFile) . "</code>";
                echo "</div>";
                $successSteps++;

                // PASSO 3: Analisar conteúdo
                echo "<div class='step info'>";
                echo "<div class='icon'>🔍</div>";
                echo "<strong>PASSO 3: Analisando conteúdo do arquivo</strong><br><br>";

                $totalSteps++;
                $content = file_get_contents($autoloadRealFile);
                $lines = explode("\n", $content);
                echo "Total de linhas: " . count($lines) . "<br>";

                // Procurar por referências ao PHPUnit
                $phpunitLines = [];
                foreach ($lines as $i => $line) {
                    if (stripos($line, 'phpunit') !== false) {
                        $phpunitLines[] = [
                            'num' => $i + 1,
                            'content' => $line
                        ];
                    }
                }

                if (empty($phpunitLines)) {
                    echo "<br>⚠️ Nenhuma referência ao PHPUnit encontrada no arquivo.<br>";
                    echo "Possíveis causas:<br>";
                    echo "• Arquivo já foi corrigido anteriormente<br>";
                    echo "• Referência está em outro arquivo do Composer<br>";
                    echo "• Problema está no cache do opcache<br>";
                    echo "</div>";

                    // Tentar limpar opcache mesmo assim
                    if (function_exists('opcache_reset')) {
                        echo "<div class='step info'>";
                        echo "<div class='icon'>🗑️</div>";
                        echo "<strong>Limpando cache do OPcache</strong><br><br>";
                        opcache_reset();
                        echo "✅ Cache limpo!<br>";
                        echo "Tente acessar o sistema agora.";
                        echo "</div>";
                    }

                } else {
                    echo "✅ Encontradas <strong>" . count($phpunitLines) . "</strong> referência(s) ao PHPUnit:<br>";
                    echo "<pre>";
                    foreach ($phpunitLines as $pl) {
                        echo "Linha " . str_pad($pl['num'], 3, '0', STR_PAD_LEFT) . ": " . htmlspecialchars(trim($pl['content'])) . "\n";
                    }
                    echo "</pre>";
                    echo "</div>";
                    $successSteps++;

                    // PASSO 4: Aplicar correção
                    echo "<div class='step info progress'>";
                    echo "<div class='icon'>⚙️</div>";
                    echo "<strong>PASSO 4: Aplicando correção automaticamente</strong><br><br>";

                    $totalSteps++;
                    $modified = false;
                    $modifiedLines = [];

                    foreach ($lines as $i => $line) {
                        if (stripos($line, 'phpunit') !== false && !empty(trim($line))) {
                            $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                            $lines[$i] = $indent . '// ' . trim($line) . ' // Desabilitado automaticamente - PHPUnit não instalado';
                            $modified = true;
                            $modifiedLines[] = $i + 1;
                        }
                    }

                    if ($modified) {
                        echo "✅ Comentadas " . count($modifiedLines) . " linha(s)<br>";
                        echo "Linhas modificadas: " . implode(', ', $modifiedLines) . "<br>";

                        // PASSO 5: Salvar arquivo
                        echo "<br><strong>Salvando arquivo modificado...</strong><br>";

                        $newContent = implode("\n", $lines);
                        if (file_put_contents($autoloadRealFile, $newContent)) {
                            echo "✅ Arquivo salvo com sucesso!";
                            echo "</div>";
                            $successSteps++;

                            // PASSO 6: Limpar cache
                            echo "<div class='step info'>";
                            echo "<div class='icon'>🗑️</div>";
                            echo "<strong>PASSO 5: Limpando cache do sistema</strong><br><br>";

                            $totalSteps++;
                            if (function_exists('opcache_reset')) {
                                opcache_reset();
                                echo "✅ Cache do OPcache limpo!";
                                $successSteps++;
                            } else {
                                echo "⚠️ OPcache não disponível (não é um problema)";
                                $successSteps++;
                            }
                            echo "</div>";

                            // PASSO 7: Testar autoload
                            echo "<div class='step info'>";
                            echo "<div class='icon'>🧪</div>";
                            echo "<strong>PASSO 6: Testando autoload corrigido</strong><br><br>";

                            $totalSteps++;
                            try {
                                // Tentar incluir o autoload
                                require '/home/supportson/public_html/ponto/vendor/autoload.php';
                                echo "✅ <strong>Autoload carregado COM SUCESSO!</strong><br>";
                                echo "Sistema corrigido e funcionando!";
                                echo "</div>";
                                $successSteps++;

                                // Sucesso total!
                                echo "<div class='step success' style='margin-top: 30px; padding: 30px;'>";
                                echo "<h2 style='color: #4caf50; margin-top: 0;'>✅ REPARO CONCLUÍDO COM SUCESSO!</h2>";
                                echo "<p style='font-size: 18px;'><strong>" . $successSteps . " de " . $totalSteps . "</strong> passos executados com sucesso!</p>";
                                echo "<hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>";
                                echo "<h3>📋 O que foi feito:</h3>";
                                echo "<ul style='font-size: 16px; line-height: 1.8;'>";
                                echo "<li>✅ Backup criado em: <code>" . basename($backupFile) . "</code></li>";
                                echo "<li>✅ " . count($modifiedLines) . " linha(s) comentada(s) no autoload</li>";
                                echo "<li>✅ Arquivo salvo com sucesso</li>";
                                echo "<li>✅ Cache do sistema limpo</li>";
                                echo "<li>✅ Autoload testado e funcionando</li>";
                                echo "</ul>";
                                echo "<hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>";
                                echo "<h3>🚀 Próximos Passos:</h3>";
                                echo "<div style='margin-top: 20px;'>";
                                echo "<a href='/health' target='_blank' class='btn'>🔍 Testar Health Check</a>";
                                echo "<a href='/auth/login' target='_blank' class='btn'>🔐 Ir para Login</a>";
                                echo "<a href='/install.php' target='_blank' class='btn'>📦 Executar Instalador</a>";
                                echo "</div>";
                                echo "</div>";

                            } catch (\Exception $e) {
                                echo "❌ <strong>ERRO ao testar autoload:</strong><br>";
                                echo htmlspecialchars($e->getMessage());
                                echo "</div>";
                                $errors[] = "Erro ao testar autoload: " . $e->getMessage();

                                // Tentar restaurar backup
                                echo "<div class='step warning'>";
                                echo "<div class='icon'>⬅️</div>";
                                echo "<strong>Restaurando backup...</strong><br><br>";
                                if (copy($backupFile, $autoloadRealFile)) {
                                    echo "✅ Backup restaurado. Sistema voltou ao estado anterior.";
                                } else {
                                    echo "❌ Erro ao restaurar backup!";
                                }
                                echo "</div>";
                            }

                        } else {
                            echo "<br>❌ Erro ao salvar arquivo!";
                            echo "</div>";
                            $errors[] = "Não foi possível salvar o arquivo modificado";
                        }
                    } else {
                        echo "❌ Nenhuma modificação aplicada";
                        echo "</div>";
                    }
                }

            } else {
                echo "❌ <strong>ERRO:</strong> Não foi possível criar backup!<br>";
                echo "Verifique as permissões do diretório.";
                echo "</div>";
                $errors[] = "Não foi possível criar backup";
            }
        }

        // Resumo de erros (se houver)
        if (!empty($errors)) {
            echo "<div class='step error' style='margin-top: 30px;'>";
            echo "<h3>❌ Erros Encontrados:</h3>";
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            }
            echo "</ul>";
            echo "<p>Por favor, entre em contato com o suporte técnico.</p>";
            echo "</div>";
        }
        ?>

        <hr style="margin: 40px 0; border: none; border-top: 2px solid #ddd;">
        <p style="text-align: center; color: #999;">
            <a href="/checagem-instalacao.php" style="color: #2196f3;">← Voltar para Checagem de Instalação</a>
        </p>
    </div>
</body>
</html>
