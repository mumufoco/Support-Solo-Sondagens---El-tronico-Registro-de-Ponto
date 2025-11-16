<?php
/**
 * Script de Verificação da Pasta Vendor
 *
 * Acesse: http://seu-dominio.com/check-vendor.php
 *
 * Este script verifica se a pasta vendor foi instalada corretamente
 */

header('Content-Type: text/html; charset=utf-8');

$vendorPath = __DIR__ . '/../vendor';
$autoloadPath = $vendorPath . '/autoload.php';
$composerPharPath = __DIR__ . '/../composer.phar';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação Vendor - Sistema de Ponto Eletrônico</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .check-item {
            background: #f8f9fa;
            border-left: 4px solid #ddd;
            padding: 15px 20px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .check-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .check-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .check-item.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .check-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 16px;
        }
        .check-message {
            color: #666;
            font-size: 14px;
        }
        .icon {
            display: inline-block;
            margin-right: 10px;
            font-size: 20px;
        }
        .summary {
            background: #e7f3ff;
            border: 2px solid #2196F3;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        .summary h2 {
            color: #2196F3;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .summary-item {
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .action-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .action-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        .action-box a {
            display: inline-block;
            background: #ffc107;
            color: #000;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 10px;
        }
        .action-box a:hover {
            background: #e0a800;
        }
        .code {
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 13px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação da Pasta Vendor</h1>
        <p class="subtitle">Sistema de Ponto Eletrônico - Diagnóstico de Dependências</p>

        <?php
        $allOk = true;
        $issues = [];

        // Check 1: Composer.phar
        if (file_exists($composerPharPath)) {
            $composerSize = filesize($composerPharPath);
            echo '<div class="check-item success">';
            echo '<div class="check-title"><span class="icon">✅</span>Composer.phar Encontrado</div>';
            echo '<div class="check-message">Arquivo: composer.phar (' . number_format($composerSize / 1024 / 1024, 2) . ' MB)</div>';
            echo '</div>';
        } else {
            $allOk = false;
            $issues[] = 'Composer.phar não encontrado';
            echo '<div class="check-item error">';
            echo '<div class="check-title"><span class="icon">❌</span>Composer.phar NÃO Encontrado</div>';
            echo '<div class="check-message">O arquivo composer.phar não existe no diretório raiz</div>';
            echo '</div>';
        }

        // Check 2: Vendor Directory
        if (is_dir($vendorPath)) {
            // Count packages
            $packages = 0;
            if (is_dir($vendorPath)) {
                $dirs = array_filter(glob($vendorPath . '/*'), 'is_dir');
                foreach ($dirs as $dir) {
                    $subdirs = array_filter(glob($dir . '/*'), 'is_dir');
                    $packages += count($subdirs);
                }
            }

            // Calculate size
            function getDirSize($directory) {
                $size = 0;
                foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
                    if ($file->isFile()) {
                        $size += $file->getSize();
                    }
                }
                return $size;
            }

            $vendorSize = getDirSize($vendorPath);

            echo '<div class="check-item success">';
            echo '<div class="check-title"><span class="icon">✅</span>Pasta vendor/ Encontrada</div>';
            echo '<div class="check-message">Tamanho: ' . number_format($vendorSize / 1024 / 1024, 2) . ' MB | Pacotes: ~' . $packages . '</div>';
            echo '</div>';
        } else {
            $allOk = false;
            $issues[] = 'Pasta vendor/ não existe';
            echo '<div class="check-item error">';
            echo '<div class="check-title"><span class="icon">❌</span>Pasta vendor/ NÃO Encontrada</div>';
            echo '<div class="check-message">A pasta vendor/ não existe. Execute o instalador de dependências.</div>';
            echo '</div>';
        }

        // Check 3: Autoload
        if (file_exists($autoloadPath)) {
            echo '<div class="check-item success">';
            echo '<div class="check-title"><span class="icon">✅</span>Autoload Disponível</div>';
            echo '<div class="check-message">Arquivo: vendor/autoload.php</div>';
            echo '</div>';

            // Try to load autoload
            try {
                require_once $autoloadPath;

                // Check CodeIgniter
                if (class_exists('CodeIgniter\CodeIgniter')) {
                    $ciVersion = CodeIgniter\CodeIgniter::CI_VERSION;
                    echo '<div class="check-item success">';
                    echo '<div class="check-title"><span class="icon">✅</span>CodeIgniter Framework Carregado</div>';
                    echo '<div class="check-message">Versão: ' . $ciVersion . '</div>';
                    echo '</div>';
                } else {
                    $allOk = false;
                    $issues[] = 'CodeIgniter não detectado';
                    echo '<div class="check-item warning">';
                    echo '<div class="check-title"><span class="icon">⚠️</span>CodeIgniter Não Detectado</div>';
                    echo '<div class="check-message">O framework não pôde ser carregado via autoload</div>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                $allOk = false;
                $issues[] = 'Erro ao carregar autoload: ' . $e->getMessage();
                echo '<div class="check-item error">';
                echo '<div class="check-title"><span class="icon">❌</span>Erro ao Carregar Autoload</div>';
                echo '<div class="check-message">' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '</div>';
            }
        } else {
            $allOk = false;
            $issues[] = 'vendor/autoload.php não encontrado';
            echo '<div class="check-item error">';
            echo '<div class="check-title"><span class="icon">❌</span>Autoload NÃO Encontrado</div>';
            echo '<div class="check-message">O arquivo vendor/autoload.php não existe</div>';
            echo '</div>';
        }

        // Check 4: composer.json
        $composerJsonPath = __DIR__ . '/../composer.json';
        if (file_exists($composerJsonPath)) {
            $composerJson = json_decode(file_get_contents($composerJsonPath), true);
            $requiredPackages = count($composerJson['require'] ?? []);

            echo '<div class="check-item success">';
            echo '<div class="check-title"><span class="icon">✅</span>composer.json Encontrado</div>';
            echo '<div class="check-message">Dependências definidas: ' . $requiredPackages . '</div>';
            echo '</div>';
        } else {
            $allOk = false;
            $issues[] = 'composer.json não encontrado';
            echo '<div class="check-item error">';
            echo '<div class="check-title"><span class="icon">❌</span>composer.json NÃO Encontrado</div>';
            echo '<div class="check-message">O arquivo de configuração do Composer não existe</div>';
            echo '</div>';
        }

        // Summary
        echo '<div class="summary">';
        echo '<h2>📊 Resumo da Verificação</h2>';

        if ($allOk) {
            echo '<div class="summary-item"><strong>Status Geral:</strong> <span style="color: #28a745; font-weight: bold;">✅ TUDO OK!</span></div>';
            echo '<div class="summary-item"><strong>Dependências:</strong> <span style="color: #28a745;">Instaladas corretamente</span></div>';
            echo '<div class="summary-item"><strong>Autoload:</strong> <span style="color: #28a745;">Funcionando</span></div>';
            echo '<div class="summary-item"><strong>Sistema:</strong> <span style="color: #28a745;">Pronto para uso</span></div>';
        } else {
            echo '<div class="summary-item"><strong>Status Geral:</strong> <span style="color: #dc3545; font-weight: bold;">❌ PROBLEMAS DETECTADOS</span></div>';
            echo '<div class="summary-item"><strong>Problemas Encontrados:</strong> <span style="color: #dc3545;">' . count($issues) . '</span></div>';

            echo '<div style="margin-top: 15px;">';
            echo '<strong>Lista de Problemas:</strong>';
            echo '<ul style="margin-left: 20px; margin-top: 10px;">';
            foreach ($issues as $issue) {
                echo '<li style="color: #dc3545; margin: 5px 0;">' . htmlspecialchars($issue) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        echo '</div>';

        // Action Box
        if (!$allOk) {
            echo '<div class="action-box">';
            echo '<h3>🔧 Ação Necessária</h3>';
            echo '<p style="margin-bottom: 10px;">As dependências não estão instaladas. Execute o instalador:</p>';
            echo '<a href="install-dependencies.php">🚀 Instalar Dependências Agora</a>';
            echo '</div>';
        }

        // Info Box
        echo '<div style="background: #e7f3ff; border-radius: 10px; padding: 20px; margin-top: 20px;">';
        echo '<h3 style="color: #2196F3; margin-bottom: 10px;">ℹ️ Informações Importantes</h3>';
        echo '<ul style="margin-left: 20px; color: #666;">';
        echo '<li style="margin: 8px 0;">A pasta <code>vendor/</code> NÃO é versionada pelo Git (.gitignore)</li>';
        echo '<li style="margin: 8px 0;">Após clonar o repositório, é necessário executar <code>composer install</code></li>';
        echo '<li style="margin: 8px 0;">Em hospedagem compartilhada, use o instalador web: <code>install-dependencies.php</code></li>';
        echo '<li style="margin: 8px 0;">O vendor ocupa aproximadamente 70 MB de espaço em disco</li>';
        echo '</ul>';
        echo '</div>';

        // Path Information
        echo '<div class="code">';
        echo '<strong>Caminhos Verificados:</strong><br>';
        echo 'Vendor: ' . realpath($vendorPath) . '<br>';
        echo 'Autoload: ' . ($autoloadPath) . '<br>';
        echo 'Composer: ' . realpath($composerPharPath ?: '.') . '<br>';
        echo '<br><strong>Servidor:</strong><br>';
        echo 'PHP: ' . PHP_VERSION . '<br>';
        echo 'Sistema: ' . PHP_OS . '<br>';
        echo 'Memória: ' . ini_get('memory_limit') . '<br>';
        echo '</div>';
        ?>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; color: #999; font-size: 12px;">
            Sistema de Ponto Eletrônico v1.0 | Verificação de Dependências
        </div>
    </div>
</body>
</html>
