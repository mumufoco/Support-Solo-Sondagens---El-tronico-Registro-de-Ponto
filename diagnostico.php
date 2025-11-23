<?php
/**
 * Diagnóstico de Erro 500
 *
 * Este script identifica a causa do erro 500 na URL raiz
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Erro 500</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #1a1a2e;
            color: #eee;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #16213e;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        h1 {
            color: #0f3460;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .check-item {
            background: #0f3460;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .check-item.success { border-left-color: #10b981; }
        .check-item.error { border-left-color: #ef4444; }
        .check-item.warning { border-left-color: #f59e0b; }
        .status { font-weight: bold; margin-right: 10px; }
        .success .status { color: #10b981; }
        .error .status { color: #ef4444; }
        .warning .status { color: #f59e0b; }
        pre {
            background: #0a0e27;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin-top: 10px;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Erro 500</h1>

        <?php
        $checks = [];

        // 1. Verificar se .env existe
        $envExists = file_exists(__DIR__ . '/.env');
        $checks[] = [
            'name' => 'Arquivo .env',
            'status' => $envExists ? 'success' : 'error',
            'message' => $envExists ? 'Encontrado' : 'NÃO ENCONTRADO - Execute o instalador primeiro!',
            'solution' => $envExists ? '' : 'Acesse: <a href="install.php">install.php</a> para criar o .env'
        ];

        // 2. Verificar vendor/autoload.php
        $composerExists = file_exists(__DIR__ . '/vendor/autoload.php');
        $checks[] = [
            'name' => 'Composer Autoloader',
            'status' => $composerExists ? 'success' : 'error',
            'message' => $composerExists ? 'OK' : 'NÃO ENCONTRADO',
            'solution' => $composerExists ? '' : 'Execute: composer install --no-dev'
        ];

        // 3. Verificar writable permissions
        $writableDirs = ['writable/cache', 'writable/logs', 'writable/session'];
        $writableOk = true;
        $writableDetails = [];
        foreach ($writableDirs as $dir) {
            $path = __DIR__ . '/' . $dir;
            $isWritable = is_writable($path);
            if (!$isWritable) {
                $writableOk = false;
                $writableDetails[] = "$dir não é gravável";
            }
        }
        $checks[] = [
            'name' => 'Permissões writable/',
            'status' => $writableOk ? 'success' : 'warning',
            'message' => $writableOk ? 'OK' : implode(', ', $writableDetails),
            'solution' => $writableOk ? '' : 'Execute: chmod -R 755 writable/'
        ];

        // 4. Verificar PHP version
        $phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
        $checks[] = [
            'name' => 'Versão do PHP',
            'status' => $phpOk ? 'success' : 'error',
            'message' => PHP_VERSION . ($phpOk ? ' ✓' : ' - Requer 8.1+'),
            'solution' => ''
        ];

        // 5. Tentar ler logs de erro do CodeIgniter
        $logFiles = glob(__DIR__ . '/writable/logs/log-*.log');
        $lastLog = '';
        if (!empty($logFiles)) {
            rsort($logFiles);
            $lastLogFile = $logFiles[0];
            $lastLog = file_get_contents($lastLogFile);
            // Pegar últimas 50 linhas
            $logLines = explode("\n", $lastLog);
            $lastLog = implode("\n", array_slice($logLines, -50));
        }

        // 6. Tentar incluir o CodeIgniter para ver o erro
        $codeIgniterError = '';
        if ($envExists && $composerExists) {
            ob_start();
            try {
                // Definir ENVIRONMENT
                if (!defined('ENVIRONMENT')) {
                    define('ENVIRONMENT', 'development');
                }

                // Tentar carregar
                require __DIR__ . '/vendor/autoload.php';

                // Verificar se consegue carregar o bootstrap
                if (file_exists(__DIR__ . '/app/Config/Paths.php')) {
                    $pathsConfig = require __DIR__ . '/app/Config/Paths.php';
                }

            } catch (Exception $e) {
                $codeIgniterError = $e->getMessage() . "\n" . $e->getTraceAsString();
            } catch (Error $e) {
                $codeIgniterError = $e->getMessage() . "\n" . $e->getTraceAsString();
            }
            $output = ob_get_clean();
            if ($output) {
                $codeIgniterError .= "\n\nOutput:\n" . $output;
            }
        }

        $checks[] = [
            'name' => 'Carregamento do CodeIgniter',
            'status' => empty($codeIgniterError) ? 'success' : 'error',
            'message' => empty($codeIgniterError) ? 'OK' : 'ERRO ao carregar',
            'solution' => '',
            'details' => $codeIgniterError
        ];

        // Exibir resultados
        foreach ($checks as $check) {
            echo '<div class="check-item ' . $check['status'] . '">';
            echo '<span class="status">' . ($check['status'] === 'success' ? '✓' : ($check['status'] === 'error' ? '✗' : '⚠')) . '</span>';
            echo '<strong>' . $check['name'] . ':</strong> ' . $check['message'];

            if (!empty($check['solution'])) {
                echo '<div style="margin-top: 10px; color: #f59e0b;">💡 ' . $check['solution'] . '</div>';
            }

            if (!empty($check['details'])) {
                echo '<pre>' . htmlspecialchars($check['details']) . '</pre>';
            }
            echo '</div>';
        }

        // Exibir logs se houver
        if ($lastLog) {
            echo '<div class="check-item warning">';
            echo '<span class="status">📋</span>';
            echo '<strong>Últimas Entradas do Log do CodeIgniter:</strong>';
            echo '<pre>' . htmlspecialchars($lastLog) . '</pre>';
            echo '</div>';
        }

        // Verificar error log do PHP
        $phpErrorLog = __DIR__ . '/writable/logs/php-errors.log';
        if (file_exists($phpErrorLog)) {
            $phpErrors = file_get_contents($phpErrorLog);
            $phpErrorLines = explode("\n", $phpErrors);
            $recentErrors = implode("\n", array_slice($phpErrorLines, -30));

            if (trim($recentErrors)) {
                echo '<div class="check-item error">';
                echo '<span class="status">🚨</span>';
                echo '<strong>Erros PHP Recentes:</strong>';
                echo '<pre>' . htmlspecialchars($recentErrors) . '</pre>';
                echo '</div>';
            }
        }

        // Verificar se o sistema já foi instalado
        if (!$envExists) {
            echo '<div class="check-item error" style="margin-top: 30px;">';
            echo '<h2 style="color: #ef4444; margin-bottom: 15px;">⚠️ Sistema não instalado!</h2>';
            echo '<p>O arquivo .env não foi encontrado. Você precisa executar o instalador primeiro.</p>';
            echo '<a href="install.php" class="btn">🚀 Executar Instalador</a>';
            echo '</div>';
        } else {
            echo '<div class="check-item" style="margin-top: 30px; border-left-color: #667eea;">';
            echo '<h2 style="color: #667eea; margin-bottom: 15px;">📝 Próximos Passos</h2>';
            echo '<ol style="margin-left: 20px; line-height: 1.8;">';
            echo '<li>Revise os erros acima</li>';
            echo '<li>Corrija os problemas identificados</li>';
            echo '<li>Verifique os logs do servidor PHP (error_log)</li>';
            echo '<li>Tente acessar <a href="/" style="color: #667eea;">a página inicial</a> novamente</li>';
            echo '</ol>';
            echo '</div>';
        }
        ?>

        <div style="margin-top: 30px; padding: 20px; background: #0a0e27; border-radius: 5px;">
            <h3 style="color: #667eea; margin-bottom: 10px;">🔧 Comandos Úteis</h3>
            <pre style="margin: 0;">
# Ver logs do PHP (Apache)
tail -f /var/log/apache2/error.log

# Ver logs do CodeIgniter
tail -f writable/logs/log-<?= date('Y-m-d') ?>.log

# Corrigir permissões
chmod -R 755 writable/
chown -R www-data:www-data writable/

# Recarregar Apache
sudo systemctl reload apache2
            </pre>
        </div>
    </div>
</body>
</html>
