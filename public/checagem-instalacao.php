<?php
/**
 * Checagem de Requisitos para Instalação
 * Acesse: https://ponto.supportsondagens.com.br/checagem-instalacao.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$rootPath = dirname(__DIR__);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Checagem de Instalação - Servidor Compartilhado</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
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
        h2 {
            color: #555;
            margin-top: 30px;
            padding: 10px;
            background: #f9f9f9;
            border-left: 4px solid #2196F3;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 5px solid #ddd;
        }
        .check-item.success {
            background: #e8f5e9;
            border-left-color: #4CAF50;
        }
        .check-item.warning {
            background: #fff3e0;
            border-left-color: #FF9800;
        }
        .check-item.error {
            background: #ffebee;
            border-left-color: #f44336;
        }
        .check-item strong {
            display: block;
            margin-bottom: 5px;
        }
        .icon {
            font-size: 20px;
            margin-right: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .summary {
            display: flex;
            gap: 20px;
            margin: 30px 0;
        }
        .summary-card {
            flex: 1;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-card .number {
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
        }
        .card-success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .card-warning {
            background: #fff3e0;
            color: #f57c00;
        }
        .card-error {
            background: #ffebee;
            color: #c62828;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .action-buttons {
            margin: 30px 0;
            display: flex;
            gap: 15px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        .btn-secondary {
            background: #2196F3;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Checagem de Requisitos - Servidor Compartilhado</h1>
        <p><strong>URL do Servidor:</strong> <?= $_SERVER['HTTP_HOST'] ?? 'N/A' ?></p>
        <p><strong>Data/Hora do Teste:</strong> <?= date('d/m/Y H:i:s') ?></p>

        <?php
        $checks = [
            'success' => 0,
            'warning' => 0,
            'error' => 0
        ];

        function checkItem($title, $description, $condition, $type = 'success') {
            global $checks;
            $checks[$type]++;

            $icons = [
                'success' => '✅',
                'warning' => '⚠️',
                'error' => '❌'
            ];

            echo "<div class='check-item $type'>";
            echo "<strong><span class='icon'>{$icons[$type]}</span>$title</strong>";
            echo "<div>$description</div>";
            echo "</div>";
        }
        ?>

        <!-- RESUMO -->
        <div class="summary">
            <div class="summary-card card-success">
                <h3>Sucesso</h3>
                <div class="number" id="success-count">0</div>
            </div>
            <div class="summary-card card-warning">
                <h3>Avisos</h3>
                <div class="number" id="warning-count">0</div>
            </div>
            <div class="summary-card card-error">
                <h3>Erros</h3>
                <div class="number" id="error-count">0</div>
            </div>
        </div>

        <!-- 1. PHP VERSION -->
        <h2>1. Versão do PHP</h2>
        <?php
        $phpVersion = PHP_VERSION;
        $minVersion = '8.1.0';
        $isValid = version_compare($phpVersion, $minVersion, '>=');

        if ($isValid) {
            checkItem(
                "PHP $phpVersion",
                "Versão do PHP compatível (mínimo: $minVersion)",
                true,
                'success'
            );
        } else {
            checkItem(
                "PHP $phpVersion",
                "Versão do PHP INCOMPATÍVEL! Mínimo requerido: $minVersion",
                false,
                'error'
            );
        }
        ?>

        <!-- 2. EXTENSÕES PHP -->
        <h2>2. Extensões PHP Requeridas</h2>
        <?php
        $requiredExtensions = [
            'intl' => 'Internacionalização',
            'mbstring' => 'Strings multi-byte',
            'json' => 'Processamento JSON',
            'mysqlnd' => 'MySQL Native Driver',
            'curl' => 'Requisições HTTP',
            'fileinfo' => 'Informações de arquivo',
            'gd' => 'Processamento de imagens',
            'openssl' => 'Criptografia SSL',
        ];

        foreach ($requiredExtensions as $ext => $desc) {
            $loaded = extension_loaded($ext);
            checkItem(
                "Extensão: $ext",
                $desc,
                $loaded,
                $loaded ? 'success' : 'error'
            );
        }
        ?>

        <!-- 3. CONFIGURAÇÕES PHP -->
        <h2>3. Configurações PHP</h2>
        <?php
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = return_bytes($memoryLimit);
        $minMemory = 128 * 1024 * 1024; // 128MB

        checkItem(
            "Memory Limit: $memoryLimit",
            "Limite de memória " . ($memoryLimitBytes >= $minMemory ? "adequado" : "BAIXO (recomendado: 128M)"),
            $memoryLimitBytes >= $minMemory,
            $memoryLimitBytes >= $minMemory ? 'success' : 'warning'
        );

        $maxExecution = ini_get('max_execution_time');
        checkItem(
            "Max Execution Time: {$maxExecution}s",
            "Tempo máximo de execução " . ($maxExecution >= 60 || $maxExecution == 0 ? "adequado" : "pode ser insuficiente"),
            $maxExecution >= 60 || $maxExecution == 0,
            ($maxExecution >= 60 || $maxExecution == 0) ? 'success' : 'warning'
        );

        $uploadMax = ini_get('upload_max_filesize');
        checkItem(
            "Upload Max Filesize: $uploadMax",
            "Tamanho máximo de upload",
            true,
            'success'
        );

        function return_bytes($val) {
            $val = trim($val);
            $last = strtolower($val[strlen($val)-1]);
            $val = (int)$val;
            switch($last) {
                case 'g': $val *= 1024;
                case 'm': $val *= 1024;
                case 'k': $val *= 1024;
            }
            return $val;
        }
        ?>

        <!-- 4. ESTRUTURA DE DIRETÓRIOS -->
        <h2>4. Estrutura de Diretórios</h2>
        <?php
        $requiredDirs = [
            'app' => 'Aplicação principal',
            'app/Controllers' => 'Controllers',
            'app/Models' => 'Models',
            'app/Views' => 'Views',
            'vendor' => 'Dependências do Composer',
            'writable' => 'Arquivos graváveis',
            'writable/cache' => 'Cache',
            'writable/logs' => 'Logs',
            'writable/session' => 'Sessões',
            'writable/uploads' => 'Uploads',
            'writable/database' => 'Database JSON (backup)',
            'public' => 'Arquivos públicos',
        ];

        foreach ($requiredDirs as $dir => $desc) {
            $path = $rootPath . '/' . $dir;
            $exists = is_dir($path);
            checkItem(
                "$dir/",
                "$desc - " . ($exists ? "Existe" : "NÃO EXISTE"),
                $exists,
                $exists ? 'success' : 'error'
            );
        }
        ?>

        <!-- 5. PERMISSÕES DE ESCRITA -->
        <h2>5. Permissões de Escrita</h2>
        <?php
        $writableDirs = [
            'writable',
            'writable/cache',
            'writable/logs',
            'writable/session',
            'writable/uploads',
            'writable/database',
        ];

        foreach ($writableDirs as $dir) {
            $path = $rootPath . '/' . $dir;
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';

            checkItem(
                "$dir/ ($perms)",
                ($writable ? "Gravável" : "NÃO GRAVÁVEL - Execute: chmod 777 $dir"),
                $writable,
                $writable ? 'success' : 'error'
            );
        }
        ?>

        <!-- 6. ARQUIVOS ESSENCIAIS -->
        <h2>6. Arquivos Essenciais</h2>
        <?php
        $requiredFiles = [
            'vendor/autoload.php' => 'Autoloader do Composer',
            'app/Config/Database.php' => 'Configuração do banco',
            'app/Config/Routes.php' => 'Rotas do sistema',
            '.env' => 'Variáveis de ambiente',
            'public/index.php' => 'Entry point público',
        ];

        foreach ($requiredFiles as $file => $desc) {
            $path = $rootPath . '/' . $file;
            $exists = file_exists($path);
            checkItem(
                $file,
                "$desc - " . ($exists ? "Existe" : "NÃO EXISTE"),
                $exists,
                $exists ? 'success' : 'error'
            );
        }
        ?>

        <!-- 7. CONFIGURAÇÃO .ENV -->
        <h2>7. Configuração do Arquivo .env</h2>
        <?php
        $envFile = $rootPath . '/.env';
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);

            // Check database config
            $hasDbHost = strpos($envContent, 'database.default.hostname') !== false;
            $hasDbName = strpos($envContent, 'database.default.database') !== false;
            $hasDbUser = strpos($envContent, 'database.default.username') !== false;

            $hasPlaceholder = strpos($envContent, 'PREENCHA_AQUI') !== false;

            checkItem(
                "Arquivo .env existe",
                "Arquivo de configuração encontrado",
                true,
                'success'
            );

            if ($hasPlaceholder) {
                checkItem(
                    "Credenciais do MySQL",
                    "ATENÇÃO: Você precisa preencher as credenciais do MySQL no arquivo .env!",
                    false,
                    'error'
                );
            } else {
                checkItem(
                    "Credenciais do MySQL",
                    "Credenciais configuradas (verifique se estão corretas)",
                    true,
                    'warning'
                );
            }
        } else {
            checkItem(
                "Arquivo .env",
                "Arquivo .env NÃO EXISTE! Copie o arquivo env.servidor-compartilhado.example para .env",
                false,
                'error'
            );
        }
        ?>

        <!-- 8. TESTE DE CONEXÃO MYSQL -->
        <h2>8. Teste de Conexão com MySQL</h2>
        <?php
        if (file_exists($envFile)) {
            // Parse .env manually
            $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $dbConfig = [];

            foreach ($envLines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') continue;

                if (strpos($line, 'database.default.hostname') !== false) {
                    $dbConfig['host'] = trim(explode('=', $line, 2)[1] ?? '');
                }
                if (strpos($line, 'database.default.database') !== false) {
                    $dbConfig['database'] = trim(explode('=', $line, 2)[1] ?? '');
                }
                if (strpos($line, 'database.default.username') !== false) {
                    $dbConfig['username'] = trim(explode('=', $line, 2)[1] ?? '');
                }
                if (strpos($line, 'database.default.password') !== false) {
                    $dbConfig['password'] = trim(explode('=', $line, 2)[1] ?? '');
                }
            }

            if (isset($dbConfig['host']) && !empty($dbConfig['host']) &&
                strpos($dbConfig['host'], 'PREENCHA') === false) {

                try {
                    $mysqli = new mysqli(
                        $dbConfig['host'],
                        $dbConfig['username'] ?? '',
                        $dbConfig['password'] ?? '',
                        $dbConfig['database'] ?? ''
                    );

                    if ($mysqli->connect_error) {
                        checkItem(
                            "Conexão MySQL",
                            "ERRO: " . $mysqli->connect_error,
                            false,
                            'error'
                        );
                    } else {
                        checkItem(
                            "Conexão MySQL",
                            "Conexão bem-sucedida! Servidor: " . $mysqli->server_info,
                            true,
                            'success'
                        );
                        $mysqli->close();
                    }
                } catch (Exception $e) {
                    checkItem(
                        "Conexão MySQL",
                        "ERRO: " . $e->getMessage(),
                        false,
                        'error'
                    );
                }
            } else {
                checkItem(
                    "Conexão MySQL",
                    "Impossível testar - preencha as credenciais no .env primeiro",
                    false,
                    'warning'
                );
            }
        }
        ?>

        <!-- ATUALIZAR CONTADORES -->
        <script>
            document.getElementById('success-count').textContent = <?= $checks['success'] ?>;
            document.getElementById('warning-count').textContent = <?= $checks['warning'] ?>;
            document.getElementById('error-count').textContent = <?= $checks['error'] ?>;
        </script>

        <!-- PRÓXIMOS PASSOS -->
        <h2>📋 Próximos Passos</h2>

        <?php if ($checks['error'] == 0 && $checks['warning'] <= 2): ?>
            <div class="check-item success">
                <strong>✅ Sistema Pronto para Instalação!</strong>
                <p>Todos os requisitos foram atendidos. Você pode prosseguir com a instalação.</p>
            </div>

            <div class="action-buttons">
                <a href="/install.php" class="btn btn-primary">▶️ Executar Instalador</a>
                <a href="/debug.php" class="btn btn-secondary">🔍 Ver Debug Completo</a>
            </div>
        <?php else: ?>
            <div class="check-item error">
                <strong>❌ Corrija os Erros Antes de Continuar</strong>
                <p>Existem <?= $checks['error'] ?> erro(s) e <?= $checks['warning'] ?> aviso(s) que devem ser corrigidos.</p>
            </div>

            <h3>Como Corrigir:</h3>
            <div class="code-block">
# 1. Ajustar permissões dos diretórios writable
chmod -R 777 writable/

# 2. Preencher credenciais no arquivo .env
# Edite o arquivo .env e configure:
database.default.hostname = localhost
database.default.database = SEU_BANCO
database.default.username = SEU_USUARIO
database.default.password = SUA_SENHA

# 3. Verificar novamente
# Recarregue esta página
            </div>
        <?php endif; ?>

        <!-- INFORMAÇÕES ADICIONAIS -->
        <h2>ℹ️ Informações do Servidor</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>PHP Version</td>
                <td><?= PHP_VERSION ?></td>
            </tr>
            <tr>
                <td>Server Software</td>
                <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <td>Document Root</td>
                <td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <td>Server IP</td>
                <td><?= $_SERVER['SERVER_ADDR'] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <td>HTTPS</td>
                <td><?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? '✅ Sim' : '❌ Não' ?></td>
            </tr>
        </table>

        <p style="text-align: center; margin-top: 40px; color: #999;">
            Sistema de Ponto Eletrônico - Checagem de Instalação v1.0<br>
            <?= date('Y') ?>
        </p>
    </div>
</body>
</html>
