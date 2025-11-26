<?php
/**
 * Diagnóstico Ultra-Profundo
 * Traça EXATAMENTE onde Boot::bootWeb() está falhando
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Diagnóstico Ultra-Profundo</title>";
echo "<style>
body{font-family:monospace;padding:20px;background:#f5f5f5;font-size:14px}
.success{color:green;font-weight:bold}.error{color:red;font-weight:bold}
.warning{color:orange;font-weight:bold}.info{color:blue}
pre{background:white;padding:10px;border:1px solid #ccc;overflow-x:auto;font-size:12px}
h2{background:#333;color:white;padding:10px;margin-top:20px}
.test-box{background:white;padding:15px;margin:10px 0;border:2px solid #333}
.step{margin:10px 0;padding:10px;background:#f9f9f9;border-left:4px solid #333}
</style></head><body>";

echo "<h1>🔬 Diagnóstico Ultra-Profundo</h1>";
echo "<p>Rastreando cada passo do Boot::bootWeb()...</p><hr>";

function showStep($step, $message, $status = 'info') {
    $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    echo "<div class='step'><strong>Passo $step:</strong> <span class='$status'>" .
         $icons[$status] . " $message</span></div>";
}

$rootDir = dirname(__DIR__);

// PASSO 1: Carregar ambiente CI
echo "<h2>Fase 1: Carregamento do Ambiente</h2>";

try {
    define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
    showStep(1, "FCPATH definido", 'success');

    require FCPATH . '../app/Config/Paths.php';
    $paths = new Config\Paths();
    showStep(2, "Paths carregado", 'success');

    require FCPATH . '../vendor/autoload.php';
    showStep(3, "Autoload carregado", 'success');

} catch (Throwable $e) {
    showStep("1-3", "ERRO: " . $e->getMessage(), 'error');
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

// PASSO 2: Testar conexão com banco de dados
echo "<h2>Fase 2: Teste de Banco de Dados</h2>";

try {
    // Carregar configuração do banco
    if (!defined('ENVIRONMENT')) {
        define('ENVIRONMENT', 'production');
    }

    require $rootDir . '/app/Config/Database.php';
    $dbConfig = new Config\Database();
    $db = $dbConfig->default;

    showStep(4, "Configuração do banco carregada", 'success');
    echo "<div class='info'>Host: {$db['hostname']}, Database: {$db['database']}, User: {$db['username']}</div>";

    // Tentar conexão PDO
    try {
        $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]);
        showStep(5, "Conexão PDO com banco de dados: SUCESSO", 'success');

        // Testar se tabela employees existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'employees'");
        if ($stmt->rowCount() > 0) {
            showStep(6, "Tabela 'employees' existe", 'success');

            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees");
            $count = $stmt->fetch()->count;
            echo "<div class='info'>Total de funcionários: $count</div>";
        } else {
            showStep(6, "Tabela 'employees' NÃO existe!", 'error');
        }

    } catch (PDOException $e) {
        showStep(5, "ERRO na conexão: " . $e->getMessage(), 'error');
    }

} catch (Throwable $e) {
    showStep(4, "ERRO ao carregar config: " . $e->getMessage(), 'error');
}

// PASSO 3: Testar inicialização de Sessão
echo "<h2>Fase 3: Teste de Sessão</h2>";

try {
    // Carregar Boot para ter acesso ao Services
    require $paths->systemDirectory . '/Boot.php';
    showStep(7, "Boot.php carregado", 'success');

    // Tentar inicializar sessão
    $session = \Config\Services::session();
    showStep(8, "Services::session() executado com sucesso", 'success');

    if ($session) {
        showStep(9, "Objeto Session criado", 'success');
        echo "<div class='info'>Session ID: " . $session->session_id . "</div>";
    } else {
        showStep(9, "Session retornou NULL!", 'error');
    }

} catch (Throwable $e) {
    showStep("7-9", "ERRO na sessão: " . $e->getMessage(), 'error');
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// PASSO 4: Testar carregamento do EmployeeModel
echo "<h2>Fase 4: Teste de Model</h2>";

try {
    $employeeModel = new \App\Models\EmployeeModel();
    showStep(10, "EmployeeModel instanciado", 'success');

    // Tentar buscar primeiro usuário
    $firstUser = $employeeModel->first();
    if ($firstUser) {
        showStep(11, "Conseguiu buscar usuário do banco", 'success');
        echo "<div class='info'>Primeiro usuário ID: " . ($firstUser->id ?? 'N/A') . "</div>";
    } else {
        showStep(11, "Nenhum usuário encontrado (banco vazio?)", 'warning');
    }

} catch (Throwable $e) {
    showStep("10-11", "ERRO no Model: " . $e->getMessage(), 'error');
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// PASSO 5: Testar instanciação do Home Controller
echo "<h2>Fase 5: Teste de Controller</h2>";

try {
    // Precisamos simular o request e response
    $request = \Config\Services::request();
    $response = \Config\Services::response();
    $logger = \Config\Services::logger();

    showStep(12, "Services (request, response, logger) carregados", 'success');

    // Tentar instanciar Home
    $homeController = new \App\Controllers\Home();
    showStep(13, "Home controller instanciado", 'success');

    // Tentar inicializar o controller
    $homeController->initController($request, $response, $logger);
    showStep(14, "Home controller inicializado (initController)", 'success');

} catch (Throwable $e) {
    showStep("12-14", "ERRO no Controller: " . $e->getMessage(), 'error');
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// PASSO 6: Testar execução do método index()
echo "<h2>Fase 6: Teste de Execução do index()</h2>";

try {
    ob_start();
    $result = $homeController->index();
    $output = ob_get_clean();

    showStep(15, "Home::index() executado sem exceção", 'success');

    if ($result) {
        $resultType = get_class($result);
        showStep(16, "Retornou: $resultType", 'info');

        if ($result instanceof \CodeIgniter\HTTP\RedirectResponse) {
            $location = $result->getHeaderLine('Location');
            showStep(17, "Redirecionando para: $location", 'info');
        }
    } else {
        showStep(16, "Retornou NULL", 'warning');
    }

    if (!empty($output)) {
        echo "<div class='info'>Output do método: <pre>" . htmlspecialchars($output) . "</pre></div>";
    }

} catch (Throwable $e) {
    showStep("15-17", "ERRO ao executar index(): " . $e->getMessage(), 'error');
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// PASSO 7: Analisar filtros globais
echo "<h2>Fase 7: Teste de Filtros Globais</h2>";

try {
    $filtersConfig = new \Config\Filters();
    showStep(18, "Filtros config carregado", 'success');

    echo "<div class='info'><strong>Filtros BEFORE globais:</strong><ul>";
    foreach ($filtersConfig->globals['before'] as $filter => $config) {
        if (is_string($filter)) {
            echo "<li>$filter</li>";
        } else {
            echo "<li>$config</li>";
        }
    }
    echo "</ul></div>";

    // Testar cada filtro manualmente
    $filterClasses = [
        'invalidchars' => \CodeIgniter\Filters\InvalidChars::class,
        'secureheaders' => \CodeIgniter\Filters\SecureHeaders::class,
    ];

    foreach ($filterClasses as $name => $class) {
        try {
            $filter = new $class();
            $filterResult = $filter->before($request);

            if ($filterResult === null) {
                showStep("19", "Filtro '$name': OK (retornou null)", 'success');
            } else {
                showStep("19", "Filtro '$name': Retornou algo! (pode estar bloqueando)", 'warning');
                echo "<pre>" . print_r($filterResult, true) . "</pre>";
            }
        } catch (Throwable $e) {
            showStep("19", "Filtro '$name': ERRO - " . $e->getMessage(), 'error');
        }
    }

} catch (Throwable $e) {
    showStep(18, "ERRO nos filtros: " . $e->getMessage(), 'error');
}

// PASSO 8: Teste REAL do Boot::bootWeb()
echo "<h2>Fase 8: Teste REAL do Boot::bootWeb()</h2>";

echo "<div class='test-box'>";
echo "<strong>⚠️ ATENÇÃO: Agora vou executar Boot::bootWeb() REAL.</strong><br>";
echo "Se a página parar de responder aqui, o problema está DENTRO do Boot::bootWeb().<br><br>";

// Criar handler para capturar shutdown
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<div class='error'><h3>❌ ERRO FATAL CAPTURADO:</h3>";
        echo "Tipo: {$error['type']}<br>";
        echo "Mensagem: {$error['message']}<br>";
        echo "Arquivo: {$error['file']}<br>";
        echo "Linha: {$error['line']}<br></div>";
    }
});

echo "<hr><strong>Executando Boot::bootWeb()...</strong><br><br>";

// Capturar TUDO
ob_start();
$bootStarted = microtime(true);

try {
    // Executar boot
    exit(CodeIgniter\Boot::bootWeb($paths));

} catch (Throwable $e) {
    $bootTime = microtime(true) - $bootStarted;
    ob_end_clean();

    echo "<div class='error'>";
    echo "❌ Boot::bootWeb() lançou exceção após " . round($bootTime, 3) . "s<br>";
    echo "<strong>Tipo:</strong> " . get_class($e) . "<br>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<strong>Stack trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

$bootOutput = ob_get_clean();
$bootTime = microtime(true) - $bootStarted;

echo "Boot executou por " . round($bootTime, 3) . " segundos<br>";

if (!empty($bootOutput)) {
    echo "<div class='success'>✅ Boot produziu output (" . strlen($bootOutput) . " bytes)</div>";
    echo "<pre>" . htmlspecialchars(substr($bootOutput, 0, 1000)) . "</pre>";
} else {
    echo "<div class='error'>❌ Boot NÃO produziu output!</div>";
}

echo "</div>";

echo "<hr><p><em>Diagnóstico concluído</em></p>";
echo "</body></html>";
