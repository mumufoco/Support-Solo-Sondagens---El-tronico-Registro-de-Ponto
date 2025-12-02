<?php
/**
 * TESTE COMPLETO DE LOGIN - Vistoria Profunda
 * Simula login e rastreia TUDO
 * DELETE após resolver!
 */

// Habilitar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Carregar CodeIgniter
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require FCPATH . '../vendor/autoload.php';
require $paths->systemDirectory . '/Boot.php';

// Iniciar CodeIgniter
$app = \CodeIgniter\Config\Services::codeigniter();
$app->initialize();

// Criar request simulado
$request = \Config\Services::request();
$session = \Config\Services::session();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste Completo de Login</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; border-radius: 3px; }
        h2 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
    </style>
</head>
<body>

<h1>🔍 Vistoria Completa - Sistema de Login</h1>

<?php

// ==================================================
// TESTE 1: Verificar Banco de Dados
// ==================================================
echo "<div class='section'>";
echo "<h2>1️⃣ Verificação do Banco de Dados</h2>";

try {
    $db = \Config\Database::connect();
    echo "<div class='success'>✅ Conexão com banco estabelecida</div>";

    // Buscar usuário admin
    $query = $db->query("SELECT * FROM employees WHERE role = 'admin' AND active = 1 LIMIT 1");
    $admin = $query->getRow();

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
    } else {
        echo "<div class='error'>❌ Nenhum usuário admin ativo encontrado!</div>";
        exit;
    }

} catch (\Exception $e) {
    echo "<div class='error'>❌ Erro no banco: " . $e->getMessage() . "</div>";
    exit;
}

echo "</div>";

// ==================================================
// TESTE 2: Simular Login
// ==================================================
echo "<div class='section'>";
echo "<h2>2️⃣ Simulação de Login</h2>";

// Criar sessão simulada
$sessionData = [
    'user_id'       => $admin->id,
    'user_name'     => $admin->name,
    'user_email'    => $admin->email,
    'user_role'     => $admin->role,
    'user_active'   => (bool) $admin->active,
    'last_activity' => time(),
    'logged_in'     => true,
];

foreach ($sessionData as $key => $value) {
    $session->set($key, $value);
}

echo "<div class='success'>✅ Sessão criada</div>";
echo "<table>";
echo "<tr><th>Chave</th><th>Valor</th></tr>";
foreach ($sessionData as $key => $value) {
    $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
    echo "<tr><td>$key</td><td>$displayValue</td></tr>";
}
echo "</table>";

echo "</div>";

// ==================================================
// TESTE 3: Verificar Filtros
// ==================================================
echo "<div class='section'>";
echo "<h2>3️⃣ Teste dos Filtros de Autenticação</h2>";

// Testar AuthFilter
echo "<h3>AuthFilter:</h3>";
$authFilter = new \App\Filters\AuthFilter();

try {
    $mockRequest = \Config\Services::request();
    $result = $authFilter->before($mockRequest);

    if ($result === null) {
        echo "<div class='success'>✅ AuthFilter PASSOU - Usuário autenticado</div>";
    } else {
        echo "<div class='error'>❌ AuthFilter BLOQUEOU - Redirect para: " . $result->getHeaderLine('Location') . "</div>";
    }
} catch (\Exception $e) {
    echo "<div class='error'>❌ Erro no AuthFilter: " . $e->getMessage() . "</div>";
}

// Testar AdminFilter
echo "<h3>AdminFilter:</h3>";
$adminFilter = new \App\Filters\AdminFilter();

try {
    $mockRequest = \Config\Services::request();
    $result = $adminFilter->before($mockRequest);

    if ($result === null) {
        echo "<div class='success'>✅ AdminFilter PASSOU - Usuário é admin</div>";
    } else {
        echo "<div class='error'>❌ AdminFilter BLOQUEOU - Redirect para: ";
        if (method_exists($result, 'getHeaderLine')) {
            echo $result->getHeaderLine('Location');
        } else {
            echo "(redirect detectado)";
        }
        echo "</div>";

        echo "<div class='warning'>";
        echo "<strong>Diagnóstico:</strong><br>";
        echo "user_role na sessão: <code>" . $session->get('user_role') . "</code><br>";
        echo "strtolower(user_role): <code>" . strtolower($session->get('user_role')) . "</code><br>";
        echo "Comparação: strtolower('" . $session->get('user_role') . "') !== 'admin' = " .
             (strtolower($session->get('user_role')) !== 'admin' ? 'TRUE (BLOQUEIA!)' : 'FALSE (PASSA)');
        echo "</div>";
    }
} catch (\Exception $e) {
    echo "<div class='error'>❌ Erro no AdminFilter: " . $e->getMessage() . "</div>";
}

echo "</div>";

// ==================================================
// TESTE 4: Verificar DashboardController
// ==================================================
echo "<div class='section'>";
echo "<h2>4️⃣ Teste do DashboardController</h2>";

try {
    // Verificar se o método admin() existe
    if (method_exists(\App\Controllers\Dashboard\DashboardController::class, 'admin')) {
        echo "<div class='success'>✅ Método admin() existe</div>";

        // Tentar instanciar (sem executar)
        echo "<div class='info'>";
        echo "<strong>Classe:</strong> App\Controllers\Dashboard\DashboardController<br>";
        echo "<strong>Método:</strong> admin()<br>";
        echo "</div>";

    } else {
        echo "<div class='error'>❌ Método admin() NÃO EXISTE!</div>";
    }

} catch (\Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
}

echo "</div>";

// ==================================================
// TESTE 5: Testar Redirect Real
// ==================================================
echo "<div class='section'>";
echo "<h2>5️⃣ Teste de Redirect Real</h2>";

echo "<p>Simulando redirect após login para: <code>/dashboard/admin</code></p>";

// Usar curl para testar redirect
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://ponto.supportsondagens.com.br/dashboard/admin');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id()); // Enviar cookie de sessão

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);

curl_close($ch);

echo "<table>";
echo "<tr><th>Item</th><th>Valor</th></tr>";
echo "<tr><td>HTTP Code</td><td><strong>$httpCode</strong></td></tr>";

if ($httpCode >= 300 && $httpCode < 400) {
    if (preg_match('/Location:\s*(.+)/i', $headers, $matches)) {
        $location = trim($matches[1]);
        echo "<tr><td>Redirect Para</td><td class='error'>$location</td></tr>";

        if (strpos($location, '/auth/login') !== false) {
            echo "<tr><td colspan='2' class='error'><strong>❌ PROBLEMA: Redirecting para LOGIN!</strong></td></tr>";
        } elseif (strpos($location, '/dashboard/admin') !== false) {
            echo "<tr><td colspan='2' class='error'><strong>❌ PROBLEMA: LOOP para /dashboard/admin!</strong></td></tr>";
        }
    }
} elseif ($httpCode == 200) {
    echo "<tr><td colspan='2' class='success'>✅ Página carregou com sucesso!</td></tr>";
}

echo "</table>";

echo "</div>";

// ==================================================
// TESTE 6: Verificar Configurações de Sessão
// ==================================================
echo "<div class='section'>";
echo "<h2>6️⃣ Configurações de Sessão do Servidor</h2>";

echo "<table>";
echo "<tr><th>Configuração</th><th>Valor</th><th>Status</th></tr>";

$sessionConfigs = [
    'session.save_path' => ini_get('session.save_path'),
    'session.gc_divisor' => ini_get('session.gc_divisor'),
    'session.cookie_domain' => ini_get('session.cookie_domain'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
];

foreach ($sessionConfigs as $key => $value) {
    $status = '';
    if ($key === 'session.gc_divisor' && $value == 0) {
        $status = '❌ ERRO';
    } elseif ($key === 'session.gc_divisor' && $value > 0) {
        $status = '✅ OK';
    }

    echo "<tr><td>$key</td><td>$value</td><td>$status</td></tr>";
}

echo "</table>";

echo "</div>";

// ==================================================
// TESTE 7: Logs Recentes
// ==================================================
echo "<div class='section'>";
echo "<h2>7️⃣ Logs Recentes</h2>";

$logDir = dirname(__DIR__) . '/writable/logs';
$logFiles = glob($logDir . '/log-*.php');

if (!empty($logFiles)) {
    rsort($logFiles);
    $latestLog = $logFiles[0];

    echo "<p><strong>Arquivo:</strong> " . basename($latestLog) . "</p>";

    $logContent = file_get_contents($latestLog);
    $lines = explode("\n", $logContent);
    $lastLines = array_slice($lines, -30);

    echo "<pre style='max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars(implode("\n", $lastLines));
    echo "</pre>";
} else {
    echo "<div class='warning'>⚠️ Nenhum log encontrado</div>";
}

echo "</div>";

?>

<div class="section info">
    <h2>📊 Resumo do Diagnóstico</h2>
    <p>Este teste verificou todos os pontos críticos do sistema de login.</p>
    <p><strong>Se ainda houver loop de redirect, o problema está em:</strong></p>
    <ul>
        <li>❌ AdminFilter bloqueando incorretamente</li>
        <li>❌ Sessão não sendo persistida entre requests</li>
        <li>❌ Configuração do cPanel/Apache interferindo</li>
        <li>❌ Cache do OPcache com código antigo</li>
    </ul>
</div>

<hr>
<p><strong>⚠️ DELETE este arquivo após análise!</strong></p>

</body>
</html>
