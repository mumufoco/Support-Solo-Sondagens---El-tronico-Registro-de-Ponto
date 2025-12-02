<?php
/**
 * TESTE COMPLETO DE LOGIN - Vistoria Profunda
 * Diagnóstico sem bootstrap completo do CI4
 * DELETE após resolver!
 */

// Habilitar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', '1');

// IMPORTANTE: Iniciar sessão ANTES de qualquer output HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir FCPATH
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Carregar apenas o que precisamos
require FCPATH . '../vendor/autoload.php';

// Carregar configuração do banco manualmente
$envFile = FCPATH . '../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste Completo de Login</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 10px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 10px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 10px 0; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 10px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; border-radius: 3px; }
        h2 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>

<h1>🔍 Vistoria Completa - Sistema de Login</h1>

<?php

// ==================================================
// TESTE 1: Verificar Variáveis de Ambiente
// ==================================================
echo "<div class='section'>";
echo "<h2>1️⃣ Verificação de Variáveis de Ambiente</h2>";

$requiredEnvVars = [
    'database.default.hostname',
    'database.default.database',
    'database.default.username',
    'database.default.password',
    'CI_ENVIRONMENT'
];

echo "<table>";
echo "<tr><th>Variável</th><th>Status</th></tr>";
foreach ($requiredEnvVars as $var) {
    $value = getenv($var);
    $status = $value ? '✅ Definida' : '❌ Ausente';
    echo "<tr><td class='code'>$var</td><td>$status</td></tr>";
}
echo "</table>";

echo "</div>";

// ==================================================
// TESTE 2: Verificar Banco de Dados
// ==================================================
echo "<div class='section'>";
echo "<h2>2️⃣ Verificação do Banco de Dados</h2>";

try {
    $host = getenv('database.default.hostname') ?: 'localhost';
    $database = getenv('database.default.database');
    $username = getenv('database.default.username');
    $password = getenv('database.default.password');

    if (!$database || !$username) {
        echo "<div class='error'>❌ Credenciais do banco não encontradas no .env</div>";
    } else {
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ]);

        echo "<div class='success'>✅ Conexão com banco estabelecida</div>";

        // Buscar usuário admin
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE role = 'admin' AND active = 1 LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();

        if ($admin) {
            echo "<div class='success'>✅ Usuário admin encontrado</div>";
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td>ID</td><td>{$admin->id}</td></tr>";
            echo "<tr><td>Nome</td><td>{$admin->name}</td></tr>";
            echo "<tr><td>Email</td><td>{$admin->email}</td></tr>";
            echo "<tr><td>Role</td><td><strong>{$admin->role}</strong></td></tr>";
            echo "<tr><td>Active</td><td>" . ($admin->active ? '✅ Sim' : '❌ Não') . "</td></tr>";
            echo "</table>";

            // Guardar para testes posteriores
            $GLOBALS['test_admin'] = $admin;
        } else {
            echo "<div class='error'>❌ Nenhum usuário admin ativo encontrado!</div>";
        }
    }

} catch (PDOException $e) {
    echo "<div class='error'>❌ Erro no banco: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// ==================================================
// TESTE 3: Verificar Sessão Atual
// ==================================================
echo "<div class='section'>";
echo "<h2>3️⃣ Verificação de Sessão</h2>";

echo "<table>";
echo "<tr><th>Item</th><th>Valor</th></tr>";
echo "<tr><td>Session Status</td><td>" . (session_status() === PHP_SESSION_ACTIVE ? '✅ Ativa' : '❌ Inativa') . "</td></tr>";
echo "<tr><td>Session ID</td><td>" . session_id() . "</td></tr>";
echo "<tr><td>Session Name</td><td>" . session_name() . "</td></tr>";
echo "<tr><td>Session Save Path</td><td>" . session_save_path() . "</td></tr>";
echo "<tr><td>Session Cookie Secure</td><td>" . (ini_get('session.cookie_secure') ? '✅ Habilitado' : '❌ Desabilitado') . "</td></tr>";
echo "<tr><td>Session Cookie HTTPOnly</td><td>" . (ini_get('session.cookie_httponly') ? '✅ Habilitado' : '❌ Desabilitado') . "</td></tr>";
echo "<tr><td>Session Cookie Lifetime</td><td>" . ini_get('session.cookie_lifetime') . " segundos</td></tr>";
echo "<tr><td>Session GC Max Lifetime</td><td>" . ini_get('session.gc_maxlifetime') . " segundos</td></tr>";
echo "</table>";

if (!empty($_SESSION)) {
    echo "<div class='info'>";
    echo "<strong>Dados na Sessão:</strong>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    echo "</div>";
} else {
    echo "<div class='warning'>⚠️ Sessão vazia (esperado em teste direto)</div>";
}

// Alertas de segurança
$securityIssues = [];
if (!ini_get('session.cookie_secure') && isset($_SERVER['HTTPS'])) {
    $securityIssues[] = "session.cookie_secure está DESABILITADO (recomendado para HTTPS)";
}
if (!ini_get('session.cookie_httponly')) {
    $securityIssues[] = "session.cookie_httponly está DESABILITADO (previne XSS)";
}

if (!empty($securityIssues)) {
    echo "<div class='warning'>";
    echo "<strong>⚠️ Alertas de Segurança da Sessão PHP:</strong><ul>";
    foreach ($securityIssues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "<p><small>Nota: O CodeIgniter pode sobrescrever estas configurações via app/Config/Session.php</small></p>";
    echo "</div>";
}

echo "</div>";

// ==================================================
// TESTE 3.5: Verificar Configuração de Sessão do CodeIgniter
// ==================================================
echo "<div class='section'>";
echo "<h2>3.5️⃣ Configuração de Sessão do CodeIgniter</h2>";

$sessionConfigFile = FCPATH . '../app/Config/Session.php';

if (file_exists($sessionConfigFile)) {
    echo "<div class='success'>✅ Arquivo de configuração existe</div>";

    $content = file_get_contents($sessionConfigFile);

    // Extrair configurações importantes
    $configs = [];

    if (preg_match('/public\s+string\s+\$driver\s*=\s*[\'"]([^\'"]+)/', $content, $match)) {
        $configs['driver'] = $match[1];
    }
    if (preg_match('/public\s+string\s+\$cookieName\s*=\s*[\'"]([^\'"]+)/', $content, $match)) {
        $configs['cookieName'] = $match[1];
    }
    if (preg_match('/public\s+int\s+\$expiration\s*=\s*(\d+)/', $content, $match)) {
        $configs['expiration'] = $match[1] . ' segundos (' . ($match[1] / 3600) . ' horas)';
    }
    if (preg_match('/public\s+string\s+\$savePath\s*=\s*[\'"]([^\'"]+)/', $content, $match)) {
        $configs['savePath'] = $match[1];
    }
    if (preg_match('/public\s+bool\s+\$matchIP\s*=\s*(true|false)/', $content, $match)) {
        $configs['matchIP'] = $match[1];
    }
    if (preg_match('/public\s+int\s+\$timeToUpdate\s*=\s*(\d+)/', $content, $match)) {
        $configs['timeToUpdate'] = $match[1] . ' segundos';
    }
    if (preg_match('/public\s+bool\s+\$regenerateDestroy\s*=\s*(true|false)/', $content, $match)) {
        $configs['regenerateDestroy'] = $match[1];
    }

    if (!empty($configs)) {
        echo "<table>";
        echo "<tr><th>Configuração</th><th>Valor</th></tr>";
        foreach ($configs as $key => $value) {
            echo "<tr><td class='code'>$key</td><td>$value</td></tr>";
        }
        echo "</table>";
    }

    // Verificar se savePath existe e é gravável
    if (isset($configs['savePath'])) {
        $savePath = str_replace('WRITEPATH', dirname(__DIR__) . '/writable/', $configs['savePath']);
        if (file_exists($savePath)) {
            $writable = is_writable($savePath);
            if ($writable) {
                echo "<div class='success'>✅ Diretório de sessão é gravável</div>";
            } else {
                echo "<div class='error'>❌ Diretório de sessão NÃO é gravável: <code>$savePath</code></div>";
            }
        } else {
            echo "<div class='error'>❌ Diretório de sessão não existe: <code>$savePath</code></div>";
        }
    }

} else {
    echo "<div class='error'>❌ Arquivo de configuração não encontrado</div>";
}

echo "</div>";

// ==================================================
// TESTE 4: Verificar Arquivos de Filtro
// ==================================================
echo "<div class='section'>";
echo "<h2>4️⃣ Verificação dos Arquivos de Filtro</h2>";

$filterFiles = [
    'AuthFilter' => FCPATH . '../app/Filters/AuthFilter.php',
    'AdminFilter' => FCPATH . '../app/Filters/AdminFilter.php'
];

echo "<table>";
echo "<tr><th>Filtro</th><th>Status</th><th>Última Modificação</th></tr>";

foreach ($filterFiles as $name => $path) {
    if (file_exists($path)) {
        $mtime = filemtime($path);
        $modified = date('Y-m-d H:i:s', $mtime);
        echo "<tr><td class='code'>$name</td><td>✅ Existe</td><td>$modified</td></tr>";

        // Ler e mostrar snippet do filtro
        $content = file_get_contents($path);

        // Extrair a lógica de verificação do AdminFilter
        if ($name === 'AdminFilter' && preg_match('/function before.*?\{(.*?)\n    \}/s', $content, $matches)) {
            echo "<tr><td colspan='3'>";
            echo "<details><summary>Ver lógica do AdminFilter</summary>";
            echo "<pre>" . htmlspecialchars(trim($matches[1])) . "</pre>";
            echo "</details>";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td class='code'>$name</td><td>❌ Não encontrado</td><td>-</td></tr>";
    }
}

echo "</table>";

echo "</div>";

// ==================================================
// TESTE 5: Verificar DashboardController
// ==================================================
echo "<div class='section'>";
echo "<h2>5️⃣ Verificação do DashboardController</h2>";

$controllerFile = FCPATH . '../app/Controllers/Dashboard/DashboardController.php';

if (file_exists($controllerFile)) {
    echo "<div class='success'>✅ Arquivo existe</div>";

    $content = file_get_contents($controllerFile);

    // Verificar se método admin() existe
    if (preg_match('/function\s+admin\s*\(/', $content)) {
        echo "<div class='success'>✅ Método admin() encontrado</div>";

        // Extrair o método
        if (preg_match('/public\s+function\s+admin\s*\([^)]*\)(.*?)(?=\n\s{4}(public|protected|private|}\s*$))/s', $content, $matches)) {
            echo "<details><summary>Ver código do método admin()</summary>";
            echo "<pre>" . htmlspecialchars('public function admin()' . trim($matches[1])) . "</pre>";
            echo "</details>";
        }
    } else {
        echo "<div class='error'>❌ Método admin() NÃO ENCONTRADO</div>";
    }
} else {
    echo "<div class='error'>❌ DashboardController não encontrado</div>";
}

echo "</div>";

// ==================================================
// TESTE 6: Verificar Routes
// ==================================================
echo "<div class='section'>";
echo "<h2>6️⃣ Verificação das Routes</h2>";

$routesFile = FCPATH . '../app/Config/Routes.php';

if (file_exists($routesFile)) {
    echo "<div class='success'>✅ Arquivo de rotas existe</div>";

    $content = file_get_contents($routesFile);

    // Procurar rotas relacionadas a dashboard
    if (preg_match_all('/\$routes->.*dashboard.*$/mi', $content, $matches)) {
        echo "<div class='info'>";
        echo "<strong>Rotas do Dashboard encontradas:</strong>";
        echo "<pre>";
        foreach ($matches[0] as $route) {
            echo htmlspecialchars($route) . "\n";
        }
        echo "</pre>";
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠️ Nenhuma rota explícita de dashboard encontrada (pode estar usando rotas padrão)</div>";
    }
} else {
    echo "<div class='error'>❌ Arquivo de rotas não encontrado</div>";
}

echo "</div>";

// ==================================================
// TESTE 7: Teste de Headers e Cookies
// ==================================================
echo "<div class='section'>";
echo "<h2>7️⃣ Informações de Headers e Cookies</h2>";

echo "<table>";
echo "<tr><th>Item</th><th>Valor</th></tr>";
echo "<tr><td>Request URI</td><td>" . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>HTTP Host</td><td>" . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>Server Software</td><td>" . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td>Session Cookie Secure</td><td>" . (ini_get('session.cookie_secure') ? '✅ Sim' : '❌ Não') . "</td></tr>";
echo "<tr><td>Session Cookie HTTPOnly</td><td>" . (ini_get('session.cookie_httponly') ? '✅ Sim' : '❌ Não') . "</td></tr>";
echo "</table>";

if (!empty($_COOKIE)) {
    echo "<div class='info'>";
    echo "<strong>Cookies Presentes:</strong>";
    echo "<table>";
    echo "<tr><th>Nome</th><th>Valor (primeiros 50 chars)</th></tr>";
    foreach ($_COOKIE as $name => $value) {
        $displayValue = htmlspecialchars(substr($value, 0, 50));
        if (strlen($value) > 50) $displayValue .= '...';
        echo "<tr><td class='code'>$name</td><td>$displayValue</td></tr>";
    }
    echo "</table>";
    echo "</div>";
}

echo "</div>";

// ==================================================
// TESTE 8: Logs Recentes
// ==================================================
echo "<div class='section'>";
echo "<h2>8️⃣ Logs Recentes</h2>";

$logDir = dirname(__DIR__) . '/writable/logs';

if (is_dir($logDir)) {
    $logFiles = glob($logDir . '/log-*.php');

    if (!empty($logFiles)) {
        rsort($logFiles);
        $latestLog = $logFiles[0];

        echo "<p><strong>Arquivo:</strong> <span class='code'>" . basename($latestLog) . "</span></p>";

        $logContent = file_get_contents($latestLog);
        $lines = explode("\n", $logContent);
        $lastLines = array_slice($lines, -50); // Últimas 50 linhas

        echo "<pre style='max-height: 400px; overflow-y: auto; background: #1e1e1e; color: #d4d4d4; padding: 15px;'>";
        echo htmlspecialchars(implode("\n", $lastLines));
        echo "</pre>";
    } else {
        echo "<div class='warning'>⚠️ Nenhum arquivo de log encontrado</div>";
    }
} else {
    echo "<div class='error'>❌ Diretório de logs não encontrado: <span class='code'>$logDir</span></div>";
}

echo "</div>";

// ==================================================
// TESTE 9: Verificar Permissões
// ==================================================
echo "<div class='section'>";
echo "<h2>9️⃣ Verificação de Permissões</h2>";

$checkPaths = [
    'writable/' => dirname(__DIR__) . '/writable',
    'writable/cache/' => dirname(__DIR__) . '/writable/cache',
    'writable/logs/' => dirname(__DIR__) . '/writable/logs',
    'writable/session/' => dirname(__DIR__) . '/writable/session',
];

echo "<table>";
echo "<tr><th>Diretório</th><th>Existe</th><th>Gravável</th><th>Permissões</th></tr>";

foreach ($checkPaths as $name => $path) {
    $exists = file_exists($path);
    $writable = is_writable($path);
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';

    $existsIcon = $exists ? '✅' : '❌';
    $writableIcon = $writable ? '✅' : '❌';

    echo "<tr>";
    echo "<td class='code'>$name</td>";
    echo "<td>$existsIcon</td>";
    echo "<td>$writableIcon</td>";
    echo "<td class='code'>$perms</td>";
    echo "</tr>";
}

echo "</table>";

echo "</div>";

?>

<div class="section info">
    <h2>📊 Resumo do Diagnóstico</h2>
    <p>Este teste verificou:</p>
    <ul>
        <li>✓ Variáveis de ambiente e configuração</li>
        <li>✓ Conexão com banco de dados e usuário admin</li>
        <li>✓ Estado da sessão PHP</li>
        <li>✓ Existência e conteúdo dos filtros (AuthFilter, AdminFilter)</li>
        <li>✓ Existência do DashboardController e método admin()</li>
        <li>✓ Configuração de rotas</li>
        <li>✓ Headers, cookies e configurações do servidor</li>
        <li>✓ Logs recentes do sistema</li>
        <li>✓ Permissões de diretórios</li>
    </ul>

    <p><strong>Se ainda houver problemas de login/redirect:</strong></p>
    <ol>
        <li>Verifique se o AdminFilter está usando <span class='code'>strtolower()</span> para comparação de roles</li>
        <li>Confirme que a sessão está sendo persistida entre requests</li>
        <li>Verifique se há cache do OPcache que precisa ser limpo</li>
        <li>Confira os logs acima para mensagens de erro específicas</li>
    </ol>
</div>

<hr>
<p><strong>⚠️ DELETE este arquivo após análise!</strong></p>
<p><small>Criado em: <?php echo date('Y-m-d H:i:s'); ?></small></p>

</body>
</html>
