<?php
/**
 * Script de Diagnóstico Ultra-Completo - VPS
 * Sistema de Ponto Eletrônico
 *
 * Acesse: https://ponto.supportsondagens.com.br/erro.php
 * DELETE este arquivo após resolver os problemas!
 */

// Habilitar TODOS os erros
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Capturar erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<div style='background: #ff0000; color: white; padding: 20px; margin: 10px 0;'>";
        echo "<h2>❌ ERRO FATAL DETECTADO:</h2>";
        echo "<pre>";
        print_r($error);
        echo "</pre>";
        echo "</div>";
    }
});

// Capturar warnings e notices
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<div style='background: #ffcccc; padding: 10px; margin: 5px; border: 1px solid red;'>";
    echo "<strong>⚠️ ERRO PHP (Tipo: $errno):</strong><br>";
    echo "<strong>Mensagem:</strong> $errstr<br>";
    echo "<strong>Arquivo:</strong> $errfile<br>";
    echo "<strong>Linha:</strong> $errline<br>";
    echo "</div>";
    return false; // Continua processamento normal
});

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Sistema de Ponto Eletrônico</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            background: #e9ecef;
            padding: 10px;
            border-left: 4px solid #007bff;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #007bff;
            color: white;
            font-weight: bold;
        }
        tr:hover { background: #f8f9fa; }
        .status-box {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .status-ok { background: #d4edda; border-color: #28a745; }
        .status-error { background: #f8d7da; border-color: #dc3545; }
        .status-warning { background: #fff3cd; border-color: #ffc107; }
    </style>
</head>
<body>
<div class="container">

<h1>🔍 Diagnóstico Completo - Sistema de Ponto Eletrônico</h1>
<p><strong>Data/Hora:</strong> <?= date('d/m/Y H:i:s') ?></p>
<p><strong>Servidor:</strong> <?= $_SERVER['SERVER_NAME'] ?? 'Desconhecido' ?></p>

<?php

// ====================================================================
// 1. INFORMAÇÕES DO SISTEMA
// ====================================================================
echo "<h2>1️⃣ Informações do Sistema</h2>";
echo "<table>";
echo "<tr><th>Item</th><th>Valor</th></tr>";
echo "<tr><td>Versão PHP</td><td class='info'>" . phpversion() . "</td></tr>";
echo "<tr><td>SAPI</td><td>" . php_sapi_name() . "</td></tr>";
echo "<tr><td>Sistema Operacional</td><td>" . PHP_OS . " (" . php_uname('s') . " " . php_uname('r') . ")</td></tr>";
echo "<tr><td>Usuário do Servidor</td><td>" . get_current_user() . "</td></tr>";
echo "<tr><td>Document Root</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</td></tr>";
echo "</table>";

// ====================================================================
// 2. CONFIGURAÇÕES CRÍTICAS DO PHP
// ====================================================================
echo "<h2>2️⃣ Configurações Críticas do PHP</h2>";

$configs = [
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => error_reporting(),
    'date.timezone' => ini_get('date.timezone') ?: 'NÃO CONFIGURADO',
];

echo "<table>";
echo "<tr><th>Configuração</th><th>Valor</th></tr>";
foreach ($configs as $key => $value) {
    $class = ($key === 'date.timezone' && $value === 'NÃO CONFIGURADO') ? 'error' : '';
    echo "<tr><td>$key</td><td class='$class'>$value</td></tr>";
}
echo "</table>";

// ====================================================================
// 3. CONFIGURAÇÕES DE SESSÃO (PROBLEMA PRINCIPAL)
// ====================================================================
echo "<h2>3️⃣ Configurações de Sessão ⚠️ CRÍTICO</h2>";

$sessionConfigs = [
    'session.auto_start' => ini_get('session.auto_start'),
    'session.gc_divisor' => ini_get('session.gc_divisor'),
    'session.gc_probability' => ini_get('session.gc_probability'),
    'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'session.save_path' => ini_get('session.save_path'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_samesite' => ini_get('session.cookie_samesite'),
    'session.use_strict_mode' => ini_get('session.use_strict_mode'),
];

echo "<table>";
echo "<tr><th>Configuração</th><th>Valor</th><th>Status</th></tr>";
foreach ($sessionConfigs as $key => $value) {
    $status = '';
    $class = '';

    if ($key === 'session.gc_divisor') {
        if ($value == 0) {
            $status = '❌ ERRO! Deve ser > 0';
            $class = 'error';
        } else {
            $status = '✅ OK';
            $class = 'success';
        }
    } elseif ($key === 'session.auto_start') {
        if ($value == 1) {
            $status = '⚠️ Ativo (pode causar problemas)';
            $class = 'warning';
        } else {
            $status = '✅ Desativado (correto)';
            $class = 'success';
        }
    }

    echo "<tr><td>$key</td><td>$value</td><td class='$class'>$status</td></tr>";
}

echo "<tr><td><strong>Status da Sessão</strong></td><td colspan='2'>";
$sessionStatus = session_status();
switch ($sessionStatus) {
    case PHP_SESSION_DISABLED:
        echo "<span class='error'>❌ DESABILITADO</span>";
        break;
    case PHP_SESSION_NONE:
        echo "<span class='info'>⏸️ Não iniciada</span>";
        break;
    case PHP_SESSION_ACTIVE:
        echo "<span class='success'>✅ ATIVA</span>";
        break;
}
echo "</td></tr>";
echo "</table>";

// ====================================================================
// 4. TESTE DE INÍCIO DE SESSÃO
// ====================================================================
echo "<h2>4️⃣ Teste de Início de Sessão</h2>";
echo "<div class='status-box ";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "status-ok'>";
        echo "✅ <strong>SUCESSO!</strong> Sessão iniciada com sucesso!<br>";
        echo "Session ID: " . session_id();
    } else {
        echo "status-warning'>";
        echo "⚠️ Sessão já estava ativa";
    }
} catch (Throwable $e) {
    echo "status-error'>";
    echo "❌ <strong>ERRO ao iniciar sessão:</strong><br>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine();
}
echo "</div>";

// ====================================================================
// 5. EXTENSÕES PHP
// ====================================================================
echo "<h2>5️⃣ Extensões PHP Necessárias</h2>";

$requiredExtensions = [
    'intl' => 'Internacionalização (OBRIGATÓRIO para CI4)',
    'mbstring' => 'Strings multibyte',
    'mysqli' => 'MySQL Improved',
    'curl' => 'Requisições HTTP',
    'json' => 'Processamento JSON',
    'xml' => 'Processamento XML',
    'zip' => 'Compressão de arquivos',
    'gd' => 'Manipulação de imagens',
    'pdo' => 'PDO Database',
    'pdo_mysql' => 'PDO MySQL Driver',
    'openssl' => 'Criptografia',
    'opcache' => 'Cache de código (performance)',
];

echo "<table>";
echo "<tr><th>Extensão</th><th>Descrição</th><th>Status</th></tr>";
foreach ($requiredExtensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    $class = $loaded ? 'success' : 'error';
    $status = $loaded ? '✅ Instalada' : '❌ NÃO instalada';

    // Marcar extensões críticas
    if (!$loaded && in_array($ext, ['intl', 'mbstring', 'mysqli'])) {
        $status .= ' <strong>(CRÍTICO!)</strong>';
    }

    echo "<tr><td><code>$ext</code></td><td>$desc</td><td class='$class'>$status</td></tr>";
}
echo "</table>";

// ====================================================================
// 6. ESTRUTURA DE ARQUIVOS E DIRETÓRIOS
// ====================================================================
echo "<h2>6️⃣ Estrutura de Arquivos e Diretórios</h2>";

$rootDir = dirname(__DIR__);
$checkPaths = [
    'Composer Autoload' => $rootDir . '/vendor/autoload.php',
    'Paths Config' => $rootDir . '/app/Config/Paths.php',
    'Services Config' => $rootDir . '/app/Config/Services.php',
    'Database Config' => $rootDir . '/app/Config/Database.php',
    'Routes Config' => $rootDir . '/app/Config/Routes.php',
    'Arquivo .env' => $rootDir . '/.env',
    'System Bootstrap' => $rootDir . '/system/bootstrap.php',
    'writable/cache' => $rootDir . '/writable/cache',
    'writable/logs' => $rootDir . '/writable/logs',
    'writable/session' => $rootDir . '/writable/session',
    'writable/uploads' => $rootDir . '/writable/uploads',
    'public/uploads' => $rootDir . '/public/uploads',
];

echo "<table>";
echo "<tr><th>Item</th><th>Caminho</th><th>Status</th></tr>";
foreach ($checkPaths as $name => $path) {
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);
    $writable = $exists && is_writable($path);
    $isDir = is_dir($path);

    $status = '';
    $class = '';

    if (!$exists) {
        $status = '❌ NÃO existe';
        $class = 'error';
    } else {
        $status = '✅ Existe';
        $class = 'success';

        if ($isDir) {
            if ($writable) {
                $status .= ' | ✅ Escrevível';
            } else {
                $status .= ' | ❌ NÃO escrevível';
                $class = 'error';
            }
        } elseif (!$readable) {
            $status .= ' | ❌ NÃO legível';
            $class = 'error';
        }
    }

    echo "<tr><td><strong>$name</strong></td><td><code>" . str_replace($rootDir, '~', $path) . "</code></td><td class='$class'>$status</td></tr>";
}
echo "</table>";

// ====================================================================
// 7. TESTE DE CARREGAMENTO DO CODEIGNITER
// ====================================================================
echo "<h2>7️⃣ Teste de Carregamento do CodeIgniter</h2>";

echo "<div class='status-box ";
try {
    $autoloadPath = $rootDir . '/vendor/autoload.php';

    if (!file_exists($autoloadPath)) {
        throw new Exception("Arquivo autoload.php não encontrado!");
    }

    require_once $autoloadPath;
    echo "status-ok'>";
    echo "✅ <strong>Autoload carregado com sucesso!</strong><br><br>";

    // Verificar classes do CI
    $ciClasses = [
        'CodeIgniter\CodeIgniter',
        'CodeIgniter\Config\BaseConfig',
        'CodeIgniter\Database\BaseConnection',
        'CodeIgniter\HTTP\IncomingRequest',
        'CodeIgniter\Session\Session',
    ];

    echo "<strong>Classes CodeIgniter:</strong><br>";
    foreach ($ciClasses as $class) {
        $exists = class_exists($class);
        $icon = $exists ? '✅' : '❌';
        echo "$icon <code>$class</code><br>";
    }

} catch (Throwable $e) {
    echo "status-error'>";
    echo "❌ <strong>ERRO ao carregar CodeIgniter:</strong><br>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<details><summary>Stack trace</summary><pre>" . $e->getTraceAsString() . "</pre></details>";
}
echo "</div>";

// ====================================================================
// 8. TESTE DE CONEXÃO COM BANCO DE DADOS
// ====================================================================
echo "<h2>8️⃣ Teste de Conexão com Banco de Dados</h2>";

echo "<div class='status-box ";
try {
    // Carregar configuração do banco
    $envPath = $rootDir . '/.env';

    if (!file_exists($envPath)) {
        throw new Exception("Arquivo .env não encontrado!");
    }

    // Parse .env manualmente
    $envContent = file_get_contents($envPath);
    preg_match('/database\.default\.hostname\s*=\s*(.+)/m', $envContent, $host);
    preg_match('/database\.default\.database\s*=\s*(.+)/m', $envContent, $dbname);
    preg_match('/database\.default\.username\s*=\s*(.+)/m', $envContent, $user);
    preg_match('/database\.default\.password\s*=\s*(.+)/m', $envContent, $pass);

    $hostname = trim($host[1] ?? '');
    $database = trim($dbname[1] ?? '');
    $username = trim($user[1] ?? '');
    $password = trim($pass[1] ?? '');

    if (empty($hostname) || empty($database) || empty($username)) {
        throw new Exception("Configurações do banco não encontradas no .env");
    }

    echo "status-ok'>";
    echo "<strong>Configurações do .env:</strong><br>";
    echo "Host: <code>$hostname</code><br>";
    echo "Database: <code>$database</code><br>";
    echo "Username: <code>$username</code><br>";
    echo "Password: <code>" . (empty($password) ? '(vazia)' : str_repeat('*', 8)) . "</code><br><br>";

    // Tentar conectar
    $mysqli = new mysqli($hostname, $username, $password, $database);

    if ($mysqli->connect_error) {
        throw new Exception("Erro de conexão: " . $mysqli->connect_error);
    }

    echo "✅ <strong>CONEXÃO ESTABELECIDA COM SUCESSO!</strong><br><br>";
    echo "Versão MySQL: <code>" . $mysqli->server_info . "</code><br>";

    // Listar tabelas
    $result = $mysqli->query("SHOW TABLES");
    if ($result) {
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }

        echo "<br><strong>Tabelas no banco (" . count($tables) . "):</strong><br>";
        if (empty($tables)) {
            echo "<span class='warning'>⚠️ Nenhuma tabela encontrada (migrations não executadas?)</span>";
        } else {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li><code>$table</code></li>";
            }
            echo "</ul>";
        }
    }

    $mysqli->close();

} catch (Throwable $e) {
    echo "status-error'>";
    echo "❌ <strong>ERRO na conexão com banco:</strong><br>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    if (isset($e)) {
        echo "<strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine();
    }
}
echo "</div>";

// ====================================================================
// 9. INFORMAÇÕES DE MEMÓRIA E PERFORMANCE
// ====================================================================
echo "<h2>9️⃣ Informações de Memória e Performance</h2>";
echo "<table>";
echo "<tr><th>Métrica</th><th>Valor</th></tr>";
echo "<tr><td>Memória usada</td><td>" . round(memory_get_usage() / 1024 / 1024, 2) . " MB</td></tr>";
echo "<tr><td>Memória pico</td><td>" . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB</td></tr>";
echo "<tr><td>Limite de memória</td><td>" . ini_get('memory_limit') . "</td></tr>";
echo "<tr><td>Tempo máximo execução</td><td>" . ini_get('max_execution_time') . "s</td></tr>";
echo "</table>";

// ====================================================================
// 10. VARIÁVEIS DE AMBIENTE
// ====================================================================
echo "<h2>🔟 Variáveis de Ambiente Importantes</h2>";
echo "<table>";
echo "<tr><th>Variável</th><th>Valor</th></tr>";
$envVars = ['HTTP_HOST', 'HTTPS', 'SERVER_PORT', 'REQUEST_URI', 'REMOTE_ADDR', 'REQUEST_METHOD'];
foreach ($envVars as $var) {
    echo "<tr><td><code>\$_SERVER['$var']</code></td><td>" . ($_SERVER[$var] ?? '<em>não definida</em>') . "</td></tr>";
}
echo "</table>";

?>

<hr>
<h2>✅ FIM DO DIAGNÓSTICO</h2>
<p><strong>Acesso:</strong> <code>https://ponto.supportsondagens.com.br/erro.php</code></p>
<p class="error"><strong>⚠️ IMPORTANTE:</strong> DELETE este arquivo após resolver os problemas de configuração!</p>
<p><em>Script gerado em: <?= date('d/m/Y H:i:s') ?></em></p>

</div>
</body>
</html>
