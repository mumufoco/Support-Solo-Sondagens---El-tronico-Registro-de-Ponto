<?php
/**
 * Debug Avançado - Página em Branco
 *
 * Este script identifica a causa exata de páginas em branco
 * testando cada componente do CodeIgniter separadamente.
 *
 * Acesse: https://ponto.supportsondagens.com.br/debug-blank-page.php
 */

// ===================================================================
// CONFIGURAÇÃO DE DEBUG
// ===================================================================

// Forçar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');

// Desabilitar output buffering que pode esconder erros
if (ob_get_level()) {
    ob_end_clean();
}

// ===================================================================
// FUNÇÕES AUXILIARES
// ===================================================================

$testResults = [];
$errorMessages = [];

function addTest($name, $success, $message = '', $details = []) {
    global $testResults, $errorMessages;
    $testResults[] = [
        'name' => $name,
        'success' => $success,
        'message' => $message,
        'details' => $details
    ];
    if (!$success && $message) {
        $errorMessages[] = $message;
    }
}

function testStep($stepName, $callback) {
    try {
        echo "<div class='test-step'>";
        echo "<h3>🔍 {$stepName}</h3>";

        $result = $callback();

        if ($result === true) {
            echo "<div class='success'>✅ Sucesso</div>";
            addTest($stepName, true);
        } elseif (is_array($result)) {
            if ($result['success']) {
                echo "<div class='success'>✅ {$result['message']}</div>";
            } else {
                echo "<div class='error'>❌ {$result['message']}</div>";
            }
            addTest($stepName, $result['success'], $result['message'], $result['details'] ?? []);
        }

        echo "</div>";
        return $result;
    } catch (Throwable $e) {
        echo "<div class='error'>";
        echo "<strong>❌ ERRO:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "<strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . ":{$e->getLine()}<br>";
        echo "<strong>Stack Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
        echo "</div>";

        addTest($stepName, false, $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return false;
    }
}

// ===================================================================
// HTML E CSS
// ===================================================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Página em Branco</title>
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
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        .content {
            padding: 30px;
        }
        .test-step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .test-step h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        .code {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            margin: 10px 0;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .summary {
            background: #f8f9fa;
            padding: 30px;
            border-top: 3px solid #667eea;
            margin-top: 30px;
        }
        .summary h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .stat {
            display: inline-block;
            padding: 15px 25px;
            margin: 10px;
            border-radius: 5px;
            font-size: 1.2em;
            font-weight: bold;
        }
        .stat.success { background: #28a745; color: white; }
        .stat.error { background: #dc3545; color: white; }
        .recommendations {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .recommendations h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .recommendations ol {
            margin-left: 20px;
        }
        .recommendations li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .recommendations li:last-child {
            border-bottom: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
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
        tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Debug Avançado - Página em Branco</h1>
            <p>Sistema de Ponto Eletrônico - Diagnóstico Completo</p>
            <p style="font-size: 0.9em; margin-top: 10px;">
                Executado em: <?= date('d/m/Y H:i:s') ?>
            </p>
        </div>

        <div class="content">

<?php

// ===================================================================
// TESTE 1: Informações do PHP
// ===================================================================

testStep("1. Informações do PHP e Servidor", function() {
    echo "<table>";
    echo "<tr><th>Configuração</th><th>Valor</th></tr>";
    echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
    echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td>Document Root</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td>Script Filename</td><td>" . __FILE__ . "</td></tr>";
    echo "<tr><td>Current User</td><td>" . get_current_user() . "</td></tr>";
    echo "<tr><td>Display Errors</td><td>" . ini_get('display_errors') . "</td></tr>";
    echo "<tr><td>Error Reporting</td><td>" . error_reporting() . "</td></tr>";
    echo "<tr><td>Memory Limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
    echo "<tr><td>Max Execution Time</td><td>" . ini_get('max_execution_time') . "s</td></tr>";
    echo "</table>";
    return true;
});

// ===================================================================
// TESTE 2: Estrutura de Arquivos
// ===================================================================

testStep("2. Verificação da Estrutura de Arquivos", function() {
    $publicDir = __DIR__;
    $rootDir = dirname($publicDir);

    $requiredFiles = [
        'public/index.php' => $publicDir . '/index.php',
        'app/Config/Paths.php' => $rootDir . '/app/Config/Paths.php',
        'app/Config/App.php' => $rootDir . '/app/Config/App.php',
        'app/Config/Database.php' => $rootDir . '/app/Config/Database.php',
        'vendor/autoload.php' => $rootDir . '/vendor/autoload.php',
        '.env' => $rootDir . '/.env',
    ];

    $allExist = true;
    echo "<table>";
    echo "<tr><th>Arquivo</th><th>Status</th><th>Permissões</th></tr>";

    foreach ($requiredFiles as $name => $path) {
        $exists = file_exists($path);
        $allExist = $allExist && $exists;
        $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
        $status = $exists ? '✅ Existe' : '❌ Faltando';
        $class = $exists ? 'success' : 'error';

        echo "<tr>";
        echo "<td>{$name}</td>";
        echo "<td class='{$class}'>{$status}</td>";
        echo "<td>{$perms}</td>";
        echo "</tr>";
    }

    echo "</table>";

    return [
        'success' => $allExist,
        'message' => $allExist ? 'Todos os arquivos essenciais existem' : 'Alguns arquivos estão faltando'
    ];
});

// ===================================================================
// TESTE 3: Carregar Paths.php
// ===================================================================

$pathsLoaded = false;
$paths = null;

testStep("3. Carregamento de app/Config/Paths.php", function() use (&$pathsLoaded, &$paths) {
    $publicDir = __DIR__;
    $rootDir = dirname($publicDir);
    $pathsFile = $rootDir . '/app/Config/Paths.php';

    if (!file_exists($pathsFile)) {
        return [
            'success' => false,
            'message' => "Arquivo Paths.php não encontrado em: {$pathsFile}"
        ];
    }

    require_once $pathsFile;

    if (!class_exists('Config\Paths')) {
        return [
            'success' => false,
            'message' => "Classe Config\\Paths não foi definida em Paths.php"
        ];
    }

    $paths = new Config\Paths();
    $pathsLoaded = true;

    echo "<div class='info'>";
    echo "<strong>Paths configurados:</strong><br>";
    echo "System Path: " . ($paths->systemDirectory ?? 'N/A') . "<br>";
    echo "App Path: " . ($paths->appDirectory ?? 'N/A') . "<br>";
    echo "Writable Path: " . ($paths->writableDirectory ?? 'N/A') . "<br>";
    echo "</div>";

    return [
        'success' => true,
        'message' => 'Paths.php carregado com sucesso'
    ];
});

// ===================================================================
// TESTE 4: Carregar Autoload do Composer
// ===================================================================

$autoloadLoaded = false;

testStep("4. Carregamento do Composer Autoload", function() use (&$autoloadLoaded) {
    $publicDir = __DIR__;
    $rootDir = dirname($publicDir);
    $autoloadFile = $rootDir . '/vendor/autoload.php';

    if (!file_exists($autoloadFile)) {
        return [
            'success' => false,
            'message' => "vendor/autoload.php não encontrado. Execute: composer install"
        ];
    }

    require_once $autoloadFile;
    $autoloadLoaded = true;

    return [
        'success' => true,
        'message' => 'Composer autoload carregado com sucesso'
    ];
});

// ===================================================================
// TESTE 5: Verificar Classes do CodeIgniter
// ===================================================================

testStep("5. Verificação de Classes do CodeIgniter", function() use ($autoloadLoaded) {
    if (!$autoloadLoaded) {
        return [
            'success' => false,
            'message' => 'Autoload não foi carregado - pulando teste'
        ];
    }

    $requiredClasses = [
        'CodeIgniter\\Boot',
        'CodeIgniter\\CodeIgniter',
        'CodeIgniter\\Config\\Services',
        'CodeIgniter\\HTTP\\Request',
        'CodeIgniter\\HTTP\\Response',
    ];

    $allExist = true;
    echo "<table>";
    echo "<tr><th>Classe</th><th>Status</th></tr>";

    foreach ($requiredClasses as $class) {
        $exists = class_exists($class);
        $allExist = $allExist && $exists;
        $status = $exists ? '✅ Disponível' : '❌ Não encontrada';
        $cssClass = $exists ? 'success' : 'error';

        echo "<tr>";
        echo "<td>{$class}</td>";
        echo "<td class='{$cssClass}'>{$status}</td>";
        echo "</tr>";
    }

    echo "</table>";

    return [
        'success' => $allExist,
        'message' => $allExist ? 'Todas as classes do CI4 estão disponíveis' : 'Algumas classes do CI4 não foram encontradas'
    ];
});

// ===================================================================
// TESTE 6: Verificar Arquivo .env
// ===================================================================

testStep("6. Análise do Arquivo .env", function() {
    $rootDir = dirname(__DIR__);
    $envFile = $rootDir . '/.env';

    if (!file_exists($envFile)) {
        return [
            'success' => false,
            'message' => 'Arquivo .env não existe. Copie .env.example para .env'
        ];
    }

    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);

    $config = [];
    $warnings = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;

        if (preg_match('/^([^=]+)\s*=\s*(.*)$/', $line, $matches)) {
            $key = trim($matches[1]);
            $value = trim($matches[2]);
            $config[$key] = $value;
        }
    }

    // Verificar configurações críticas
    echo "<table>";
    echo "<tr><th>Configuração</th><th>Valor</th><th>Status</th></tr>";

    $criticalConfigs = [
        'CI_ENVIRONMENT' => 'production',
        'app.baseURL' => 'https://ponto.supportsondagens.com.br/',
        'database.default.hostname' => 'localhost',
        'database.default.database' => '[configurado]',
        'database.default.username' => '[configurado]',
        'database.default.password' => '[configurado]',
    ];

    foreach ($criticalConfigs as $key => $expected) {
        $value = $config[$key] ?? null;

        if ($value === null) {
            echo "<tr><td>{$key}</td><td class='error'>NÃO CONFIGURADO</td><td>❌</td></tr>";
            $warnings[] = "{$key} não está configurado";
        } else {
            // Ocultar senhas
            if (strpos($key, 'password') !== false) {
                $displayValue = str_repeat('*', min(strlen($value), 10));
            } else {
                $displayValue = htmlspecialchars($value);
            }
            echo "<tr><td>{$key}</td><td>{$displayValue}</td><td>✅</td></tr>";
        }
    }

    echo "</table>";

    if (count($warnings) > 0) {
        echo "<div class='warning'>";
        echo "<strong>Avisos de Configuração:</strong><ul>";
        foreach ($warnings as $warning) {
            echo "<li>{$warning}</li>";
        }
        echo "</ul></div>";
    }

    return [
        'success' => count($warnings) === 0,
        'message' => count($warnings) === 0 ? 'Arquivo .env configurado corretamente' : 'Há problemas na configuração do .env',
        'details' => ['warnings' => $warnings]
    ];
});

// ===================================================================
// TESTE 7: Testar Conexão com Banco de Dados
// ===================================================================

testStep("7. Teste de Conexão com Banco de Dados", function() use ($autoloadLoaded) {
    if (!$autoloadLoaded) {
        return [
            'success' => false,
            'message' => 'Autoload não carregado - pulando teste de banco'
        ];
    }

    try {
        // Carregar configuração do banco
        $rootDir = dirname(__DIR__);
        require_once $rootDir . '/app/Config/Database.php';

        $dbConfig = new Config\Database();
        $default = $dbConfig->default;

        echo "<div class='info'>";
        echo "<strong>Configuração do Banco:</strong><br>";
        echo "Driver: " . $default['DBDriver'] . "<br>";
        echo "Hostname: " . $default['hostname'] . "<br>";
        echo "Database: " . $default['database'] . "<br>";
        echo "Username: " . $default['username'] . "<br>";
        echo "</div>";

        // Tentar conectar
        $dsn = "mysql:host={$default['hostname']};dbname={$default['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $default['username'], $default['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        echo "<div class='success'>";
        echo "✅ Conexão com banco de dados estabelecida com sucesso!<br>";
        echo "Versão MySQL: " . $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "</div>";

        return [
            'success' => true,
            'message' => 'Banco de dados conectado com sucesso'
        ];

    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo "<strong>❌ Erro de Conexão com Banco:</strong><br>";
        echo htmlspecialchars($e->getMessage());
        echo "</div>";

        return [
            'success' => false,
            'message' => 'Falha na conexão com banco de dados: ' . $e->getMessage()
        ];
    } catch (Throwable $e) {
        return [
            'success' => false,
            'message' => 'Erro ao testar banco: ' . $e->getMessage()
        ];
    }
});

// ===================================================================
// TESTE 8: Tentar Inicializar CodeIgniter
// ===================================================================

testStep("8. Tentativa de Inicialização do CodeIgniter", function() use ($pathsLoaded, $autoloadLoaded, $paths) {
    if (!$pathsLoaded || !$autoloadLoaded) {
        return [
            'success' => false,
            'message' => 'Pré-requisitos não atendidos - pulando inicialização do CI4'
        ];
    }

    try {
        // Tentar inicializar o Boot
        if (!class_exists('CodeIgniter\\Boot')) {
            return [
                'success' => false,
                'message' => 'Classe CodeIgniter\\Boot não encontrada'
            ];
        }

        echo "<div class='info'>";
        echo "CodeIgniter Boot class está disponível<br>";
        echo "Tentando inicializar o framework...<br>";
        echo "</div>";

        // Verificar se consegue criar instância do CodeIgniter
        if (class_exists('CodeIgniter\\CodeIgniter')) {
            echo "<div class='success'>";
            echo "✅ Classe CodeIgniter\\CodeIgniter encontrada<br>";
            echo "</div>";
        }

        return [
            'success' => true,
            'message' => 'CodeIgniter pode ser inicializado (classes disponíveis)'
        ];

    } catch (Throwable $e) {
        return [
            'success' => false,
            'message' => 'Erro ao tentar inicializar CI4: ' . $e->getMessage()
        ];
    }
});

// ===================================================================
// TESTE 9: Verificar Logs de Erro
// ===================================================================

testStep("9. Análise de Logs de Erro Recentes", function() {
    $rootDir = dirname(__DIR__);
    $logDir = $rootDir . '/writable/logs';

    if (!is_dir($logDir)) {
        return [
            'success' => false,
            'message' => "Diretório de logs não existe: {$logDir}"
        ];
    }

    // Encontrar o log mais recente
    $logFiles = glob($logDir . '/log-*.php');

    if (empty($logFiles)) {
        echo "<div class='info'>Nenhum arquivo de log encontrado (isso pode ser bom - sem erros)</div>";
        return [
            'success' => true,
            'message' => 'Nenhum log de erro encontrado'
        ];
    }

    // Pegar o mais recente
    usort($logFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $latestLog = $logFiles[0];
    $logContent = file_get_contents($latestLog);
    $logLines = explode("\n", $logContent);

    // Pegar últimas 20 linhas
    $recentLines = array_slice($logLines, -20);
    $recentText = implode("\n", $recentLines);

    echo "<div class='info'>";
    echo "<strong>Arquivo de Log:</strong> " . basename($latestLog) . "<br>";
    echo "<strong>Última Modificação:</strong> " . date('d/m/Y H:i:s', filemtime($latestLog)) . "<br>";
    echo "<strong>Tamanho:</strong> " . number_format(filesize($latestLog)) . " bytes<br>";
    echo "</div>";

    echo "<div class='code'>";
    echo "<strong>Últimas 20 linhas do log:</strong><pre>";
    echo htmlspecialchars($recentText);
    echo "</pre></div>";

    // Procurar por erros críticos
    $criticalErrors = [];
    foreach ($logLines as $line) {
        if (stripos($line, 'CRITICAL') !== false || stripos($line, 'ERROR') !== false) {
            $criticalErrors[] = $line;
        }
    }

    if (!empty($criticalErrors)) {
        $recentCritical = array_slice($criticalErrors, -5);
        echo "<div class='error'>";
        echo "<strong>⚠️ Erros Críticos Encontrados:</strong><pre>";
        echo htmlspecialchars(implode("\n", $recentCritical));
        echo "</pre></div>";
    }

    return [
        'success' => empty($criticalErrors),
        'message' => empty($criticalErrors) ? 'Nenhum erro crítico nos logs' : count($criticalErrors) . ' erros críticos encontrados'
    ];
});

// ===================================================================
// TESTE 10: Testar index.php Diretamente
// ===================================================================

testStep("10. Análise do Arquivo index.php", function() {
    $indexFile = __DIR__ . '/index.php';

    if (!file_exists($indexFile)) {
        return [
            'success' => false,
            'message' => 'index.php não encontrado em public/'
        ];
    }

    $indexContent = file_get_contents($indexFile);

    echo "<div class='info'>";
    echo "<strong>Arquivo:</strong> {$indexFile}<br>";
    echo "<strong>Tamanho:</strong> " . strlen($indexContent) . " bytes<br>";
    echo "<strong>Linhas:</strong> " . count(explode("\n", $indexContent)) . "<br>";
    echo "</div>";

    // Verificar se tem as linhas essenciais
    $essentialChecks = [
        'require.*Paths\.php' => 'Carrega Paths.php',
        'require.*autoload\.php' => 'Carrega Composer autoload',
        'Boot::bootWeb' => 'Inicializa o CodeIgniter'
    ];

    echo "<table>";
    echo "<tr><th>Verificação</th><th>Status</th></tr>";

    $allChecksPass = true;
    foreach ($essentialChecks as $pattern => $description) {
        $found = preg_match('/' . $pattern . '/i', $indexContent);
        $status = $found ? '✅ Encontrado' : '❌ Faltando';
        $class = $found ? 'success' : 'error';
        $allChecksPass = $allChecksPass && $found;

        echo "<tr><td>{$description}</td><td class='{$class}'>{$status}</td></tr>";
    }

    echo "</table>";

    return [
        'success' => $allChecksPass,
        'message' => $allChecksPass ? 'index.php parece estar correto' : 'index.php tem problemas'
    ];
});

// ===================================================================
// RESUMO FINAL
// ===================================================================
?>

        </div>

        <div class="summary">
            <h2>📊 Resumo Geral</h2>

            <?php
            $totalTests = count($testResults);
            $successCount = count(array_filter($testResults, function($r) { return $r['success']; }));
            $failCount = $totalTests - $successCount;
            ?>

            <div>
                <span class="stat success">✅ <?= $successCount ?> Sucessos</span>
                <span class="stat error">❌ <?= $failCount ?> Falhas</span>
            </div>

            <?php if (!empty($errorMessages)): ?>
            <div class="recommendations">
                <h3>🔧 Ações Recomendadas para Resolver a Página em Branco:</h3>
                <ol>
                    <?php foreach ($errorMessages as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <?php endif; ?>

            <?php if ($failCount === 0): ?>
            <div class="success" style="margin-top: 20px; padding: 20px;">
                <h3>🎉 Todos os Testes Passaram!</h3>
                <p><strong>A aplicação deveria estar funcionando.</strong></p>
                <p>Se ainda vê uma página em branco, tente:</p>
                <ol>
                    <li>Limpar cache do navegador (Ctrl+Shift+Delete)</li>
                    <li>Acessar em modo anônimo/privado</li>
                    <li>Verificar console do navegador (F12) para erros JavaScript</li>
                    <li>Acessar diretamente: <a href="/">https://ponto.supportsondagens.com.br/</a></li>
                </ol>
            </div>
            <?php endif; ?>

            <div class="info" style="margin-top: 20px;">
                <strong>Próximos Passos:</strong><br>
                1. Corrija os erros listados acima<br>
                2. Execute este script novamente para verificar<br>
                3. Tente acessar: <a href="/">https://ponto.supportsondagens.com.br/</a><br>
                4. Se ainda tiver problemas, verifique os logs em writable/logs/
            </div>
        </div>
    </div>
</body>
</html>
