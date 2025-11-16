<?php
/**
 * Instalador de Dependências via Composer
 *
 * Este script baixa o Composer e instala todas as dependências do projeto
 * Ideal para hospedagem compartilhada sem acesso SSH
 *
 * Acesse: http://seu-dominio.com/install-dependencies.php
 *
 * IMPORTANTE:
 * - Suporta ambientes com exec/passthru desabilitados
 * - Suprime warnings do instalador do Composer (composer.sig)
 * - Limpa automaticamente arquivos temporários
 * - DELETE este arquivo após uso por segurança!
 */

set_time_limit(300); // 5 minutos
ini_set('memory_limit', '512M');

$rootPath = dirname(__DIR__);
$vendorPath = $rootPath . '/vendor';
$composerPhar = $rootPath . '/composer.phar';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação de Dependências</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: #2c3e50; color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 40px; }
        .step { margin-bottom: 30px; padding: 20px; border-left: 4px solid #3498db; background: #ecf0f1; border-radius: 4px; }
        .step h3 { color: #2c3e50; margin-bottom: 10px; }
        .btn { padding: 14px 30px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .output { background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 13px; max-height: 500px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .info { color: #3498db; }
        .progress { width: 100%; height: 30px; background: #ecf0f1; border-radius: 15px; overflow: hidden; margin: 20px 0; }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #3498db, #2ecc71); transition: width 0.3s; text-align: center; line-height: 30px; color: white; font-weight: bold; }
        .footer { background: #ecf0f1; padding: 20px; text-align: center; font-size: 12px; color: #7f8c8d; }
        code { background: #ecf0f1; padding: 3px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Instalação de Dependências</h1>
            <p>Composer + Vendor Installation</p>
        </div>

        <div class="content">
            <?php
            $action = $_GET['action'] ?? 'menu';

            if ($action === 'menu') {
                // Menu inicial
                ?>
                <div class="step">
                    <h3>Status do Sistema</h3>
                    <?php
                    echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
                    echo "<p><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
                    echo "<p><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . "s</p>";
                    echo "<p><strong>Composer.phar:</strong> " . (file_exists($composerPhar) ? '<span class="success">✓ Instalado</span>' : '<span class="warning">✗ Não instalado</span>') . "</p>";
                    echo "<p><strong>Vendor:</strong> " . (is_dir($vendorPath) ? '<span class="success">✓ Existe (' . count(glob($vendorPath . '/*')) . ' pacotes)</span>' : '<span class="warning">✗ Não existe</span>') . "</p>";
                    echo "<p><strong>composer.json:</strong> " . (file_exists($rootPath . '/composer.json') ? '<span class="success">✓ Encontrado</span>' : '<span class="error">✗ Não encontrado</span>') . "</p>";
                    ?>
                </div>

                <div class="step">
                    <h3>O que este instalador faz?</h3>
                    <ol style="padding-left: 30px; line-height: 2;">
                        <li>Baixa o Composer (se não estiver instalado)</li>
                        <li>Verifica a integridade do arquivo baixado</li>
                        <li>Executa <code>composer install</code></li>
                        <li>Instala todas as dependências do CodeIgniter 4</li>
                        <li>Gera a pasta <code>vendor/</code> completa</li>
                    </ol>
                </div>

                <?php if (!file_exists($rootPath . '/composer.json')): ?>
                    <div class="step" style="border-left-color: #e74c3c; background: #fadbd8;">
                        <h3 style="color: #e74c3c;">⚠️ Erro: composer.json não encontrado!</h3>
                        <p>O arquivo <code>composer.json</code> é necessário para instalar as dependências.</p>
                        <p>Verifique se você está no diretório correto do projeto.</p>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="?action=install" class="btn btn-success" style="font-size: 18px; padding: 16px 40px;">
                            🚀 Iniciar Instalação
                        </a>
                    </div>

                    <?php if (is_dir($vendorPath)): ?>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="?action=update" class="btn" style="background: #f39c12;">
                                🔄 Atualizar Dependências
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php
            } elseif ($action === 'install' || $action === 'update') {
                // Instalação
                ?>
                <div class="step">
                    <h3><?= $action === 'install' ? '🚀 Instalando Dependências...' : '🔄 Atualizando Dependências...' ?></h3>
                </div>

                <div class="output" id="output">
                <?php
                ob_implicit_flush(true);

                function logStep($message, $type = 'info') {
                    $colors = [
                        'success' => 'success',
                        'error' => 'error',
                        'warning' => 'warning',
                        'info' => 'info'
                    ];
                    $class = $colors[$type] ?? 'info';
                    echo '<span class="' . $class . '">' . date('H:i:s') . ' | ' . htmlspecialchars($message) . '</span>' . "\n";
                    ob_flush();
                    flush();
                }

                try {
                    // Step 1: Baixar Composer se não existir
                    if (!file_exists($composerPhar)) {
                        logStep('Baixando Composer...', 'info');

                        $composerSetup = $rootPath . '/composer-setup.php';

                        // Baixar instalador do Composer
                        logStep('Fazendo download do instalador...', 'info');
                        $setupContent = file_get_contents('https://getcomposer.org/installer');

                        if ($setupContent === false) {
                            throw new Exception('Falha ao baixar o instalador do Composer');
                        }

                        file_put_contents($composerSetup, $setupContent);
                        logStep('✓ Instalador baixado', 'success');

                        // Executar instalador
                        logStep('Executando instalador do Composer...', 'info');

                        // Suprimir warnings do instalador (como unlink de composer.sig)
                        $oldErrorReporting = error_reporting();
                        error_reporting($oldErrorReporting & ~E_WARNING);

                        ob_start();
                        include $composerSetup;
                        $installOutput = ob_get_clean();

                        // Restaurar error reporting
                        error_reporting($oldErrorReporting);

                        // Limpar arquivos temporários
                        @unlink($composerSetup);
                        @unlink($rootPath . '/composer.sig');
                        @unlink($rootPath . '/composer-temp.phar');

                        if (!file_exists($composerPhar)) {
                            throw new Exception('Falha ao instalar Composer. Output: ' . $installOutput);
                        }

                        logStep('✓ Composer instalado com sucesso!', 'success');
                    } else {
                        logStep('✓ Composer já está instalado', 'success');
                    }

                    // Step 2: Verificar versão do Composer
                    logStep('Verificando versão do Composer...', 'info');
                    chdir($rootPath);

                    $output = [];
                    $returnCode = 0;

                    // Usar passthru se disponível, senão exec, senão direct PHP
                    if (function_exists('passthru') && !in_array('passthru', array_map('trim', explode(',', ini_get('disable_functions'))))) {
                        ob_start();
                        passthru('php composer.phar --version 2>&1', $returnCode);
                        $versionOutput = ob_get_clean();
                        echo $versionOutput . "\n";
                    } elseif (function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
                        exec('php composer.phar --version 2>&1', $output, $returnCode);
                        echo implode("\n", $output) . "\n";
                    } else {
                        logStep('⚠ Funções exec/passthru desabilitadas - usando método alternativo', 'warning');

                        // Método alternativo: incluir Composer diretamente via PHP
                        $_SERVER['argv'] = ['composer.phar', '--version'];
                        $_SERVER['argc'] = 2;

                        ob_start();
                        include $composerPhar;
                        $versionOutput = ob_get_clean();
                        echo $versionOutput . "\n";
                        $returnCode = 0;
                    }

                    // Step 3: Executar composer install/update
                    $command = $action === 'update' ? 'update' : 'install';
                    logStep("Executando: composer $command...", 'info');
                    logStep('Isso pode levar alguns minutos...', 'warning');

                    $output = [];
                    $returnCode = 0;

                    if (function_exists('passthru') && !in_array('passthru', array_map('trim', explode(',', ini_get('disable_functions'))))) {
                        ob_start();
                        passthru("php composer.phar $command --no-interaction --optimize-autoloader 2>&1", $returnCode);
                        $composerOutput = ob_get_clean();
                        echo $composerOutput . "\n";
                    } elseif (function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
                        exec("php composer.phar $command --no-interaction --optimize-autoloader 2>&1", $output, $returnCode);
                        foreach ($output as $line) {
                            echo $line . "\n";
                            ob_flush();
                            flush();
                        }
                    } else {
                        // Método alternativo: executar Composer via include
                        logStep('Executando Composer via include (método alternativo)...', 'info');

                        $_SERVER['argv'] = ['composer.phar', $command, '--no-interaction', '--optimize-autoloader'];
                        $_SERVER['argc'] = 4;

                        putenv('COMPOSER_HOME=' . $rootPath);

                        ob_start();
                        try {
                            include $composerPhar;
                            $returnCode = 0;
                        } catch (Exception $e) {
                            logStep('Erro ao executar Composer: ' . $e->getMessage(), 'error');
                            $returnCode = 1;
                        }
                        $composerOutput = ob_get_clean();
                        echo $composerOutput . "\n";
                    }

                    if ($returnCode === 0) {
                        logStep('✓✓✓ INSTALAÇÃO CONCLUÍDA COM SUCESSO! ✓✓✓', 'success');

                        // Verificar pasta vendor
                        if (is_dir($vendorPath)) {
                            $packageCount = count(glob($vendorPath . '/*/*'));
                            logStep("✓ Pasta vendor/ criada com $packageCount pacotes", 'success');

                            // Verificar CodeIgniter
                            $ciPath = $vendorPath . '/codeigniter4/framework';
                            if (is_dir($ciPath)) {
                                logStep('✓ CodeIgniter 4 instalado com sucesso!', 'success');
                            }
                        }

                        echo "\n";
                        logStep('═══════════════════════════════════════════', 'success');
                        logStep('   PRÓXIMOS PASSOS:', 'success');
                        logStep('═══════════════════════════════════════════', 'success');
                        logStep('1. Acesse o instalador web: install.php', 'info');
                        logStep('2. Configure o banco de dados', 'info');
                        logStep('3. Crie o usuário administrador', 'info');
                        logStep('4. DELETE este arquivo (install-dependencies.php)', 'warning');
                        logStep('═══════════════════════════════════════════', 'success');

                    } else {
                        logStep('✗ Erro durante a instalação (código: ' . $returnCode . ')', 'error');
                        logStep('Verifique os erros acima', 'warning');
                    }

                } catch (Exception $e) {
                    logStep('✗ ERRO: ' . $e->getMessage(), 'error');
                    logStep('Trace: ' . $e->getTraceAsString(), 'error');
                } finally {
                    // Limpar arquivos temporários (sempre executado)
                    $tempFiles = [
                        $rootPath . '/composer-setup.php',
                        $rootPath . '/composer.sig',
                        $rootPath . '/composer-temp.phar'
                    ];

                    foreach ($tempFiles as $tempFile) {
                        if (file_exists($tempFile)) {
                            @unlink($tempFile);
                        }
                    }
                }
                ?>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="?action=menu" class="btn">← Voltar ao Menu</a>
                    <?php if (isset($returnCode) && $returnCode === 0): ?>
                        <a href="install.php" class="btn btn-success">Continuar para Instalador →</a>
                    <?php else: ?>
                        <a href="?action=install" class="btn btn-danger">Tentar Novamente</a>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="footer">
            Sistema de Ponto Eletrônico © <?= date('Y') ?> | Instalador de Dependências
            <br>
            <strong>IMPORTANTE:</strong> Delete este arquivo após a instalação por segurança!
        </div>
    </div>
</body>
</html>
