<?php
/**
 * Diagnóstico Avançado - Análise Profunda da Página Branca
 * Este script simula exatamente o que index.php faz e mostra onde falha
 */

// Buffer TUDO para capturar até erros fatais
ob_start();

// Mostrar TODOS os erros
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Avançado</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5}";
echo ".success{color:green;font-weight:bold}.error{color:red;font-weight:bold}";
echo ".warning{color:orange;font-weight:bold}.info{color:blue}";
echo "pre{background:white;padding:10px;border:1px solid #ccc;overflow-x:auto}";
echo "h2{background:#333;color:white;padding:10px;margin-top:20px}</style></head><body>";

echo "<h1>🔍 Diagnóstico Avançado - Análise Profunda</h1>";
echo "<p>Simulando exatamente o que index.php faz...</p><hr>";

// Função helper para mostrar status
function showStatus($message, $status = 'info') {
    $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    echo "<div class='$status'>" . $icons[$status] . " $message</div>";
}

// 1. VERIFICAR PHP E AMBIENTE
echo "<h2>1. Ambiente PHP</h2>";
showStatus("PHP Version: " . PHP_VERSION, 'info');
showStatus("SAPI: " . php_sapi_name(), 'info');
showStatus("Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'não definido'), 'info');
showStatus("Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'não definido'), 'info');
showStatus("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'não definido'), 'info');
echo "<br>";

// 2. VERIFICAR ESTRUTURA DE ARQUIVOS
echo "<h2>2. Estrutura de Arquivos Críticos</h2>";
$rootDir = dirname(__DIR__);
$criticalFiles = [
    'index.php' => __DIR__ . '/index.php',
    'Paths.php' => $rootDir . '/app/Config/Paths.php',
    'autoload.php' => $rootDir . '/vendor/autoload.php',
    'Routes.php' => $rootDir . '/app/Config/Routes.php',
    'Home Controller' => $rootDir . '/app/Controllers/Home.php',
    '.env' => $rootDir . '/.env',
];

foreach ($criticalFiles as $name => $path) {
    if (file_exists($path)) {
        showStatus("$name: existe (" . filesize($path) . " bytes)", 'success');
    } else {
        showStatus("$name: NÃO EXISTE!", 'error');
    }
}
echo "<br>";

// 3. VERIFICAR .ENV E ENVIRONMENT
echo "<h2>3. Configuração de Ambiente</h2>";
$envFile = $rootDir . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);

    // Extrair variáveis importantes (sem mostrar senhas)
    $importantVars = ['CI_ENVIRONMENT', 'app.baseURL', 'database.default.hostname', 'database.default.database'];
    foreach ($importantVars as $var) {
        if (preg_match('/^\s*' . preg_quote($var, '/') . '\s*=\s*(.+)$/m', $envContent, $matches)) {
            $value = trim($matches[1], '"\'');
            // Ocultar senhas
            if (strpos($var, 'password') !== false) {
                $value = '***OCULTO***';
            }
            showStatus("$var = $value", 'info');
        }
    }
} else {
    showStatus(".env não encontrado!", 'error');
}
echo "<br>";

// 4. SIMULAR CARREGAMENTO DO INDEX.PHP
echo "<h2>4. Simulação do Fluxo do index.php</h2>";

try {
    // Define FCPATH
    define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
    showStatus("FCPATH definido: " . FCPATH, 'success');

    // Carregar Paths
    require FCPATH . '../app/Config/Paths.php';
    $paths = new Config\Paths();
    showStatus("Paths.php carregado com sucesso", 'success');
    showStatus("System Directory: " . $paths->systemDirectory, 'info');
    showStatus("App Directory: " . $paths->appDirectory, 'info');

    // Carregar Composer
    if (is_file(FCPATH . '../vendor/autoload.php')) {
        require FCPATH . '../vendor/autoload.php';
        showStatus("Composer autoload carregado", 'success');
    } else {
        showStatus("Composer autoload NÃO encontrado!", 'error');
    }

    // Verificar se bootstrap existe
    $bootstrapPath = $paths->systemDirectory . '/Boot.php';
    if (file_exists($bootstrapPath)) {
        showStatus("Boot.php encontrado: $bootstrapPath", 'success');
    } else {
        showStatus("Boot.php NÃO encontrado em: $bootstrapPath", 'error');
    }

} catch (Throwable $e) {
    showStatus("ERRO ao simular index.php: " . $e->getMessage(), 'error');
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<br>";

// 5. VERIFICAR ROTAS
echo "<h2>5. Verificação de Rotas</h2>";
try {
    $routesFile = $rootDir . '/app/Config/Routes.php';
    if (file_exists($routesFile)) {
        $routesContent = file_get_contents($routesFile);

        // Procurar rota padrão
        if (preg_match('/\$routes->get\([\'"]\/[\'"]\s*,\s*[\'"]([^"\']+)/', $routesContent, $matches)) {
            showStatus("Rota padrão (/) encontrada: " . $matches[1], 'success');
        } else {
            showStatus("Rota padrão (/) NÃO encontrada no Routes.php!", 'warning');
        }

        // Contar rotas
        $routeCount = preg_match_all('/\$routes->(get|post|put|delete|patch)/', $routesContent);
        showStatus("Total de rotas definidas: $routeCount", 'info');
    }
} catch (Throwable $e) {
    showStatus("Erro ao verificar rotas: " . $e->getMessage(), 'error');
}
echo "<br>";

// 6. TESTAR BOOTSTRAP COMPLETO
echo "<h2>6. Teste de Bootstrap Completo do CodeIgniter</h2>";
echo "<div style='background:white;padding:15px;border:2px solid #333;margin:10px 0'>";
echo "<strong>Tentando executar Boot::bootWeb()...</strong><br><br>";

// Capturar output do boot
ob_start();
$bootSuccess = false;
$bootError = null;

try {
    // Limpar defines anteriores se existirem
    if (!defined('ENVIRONMENT')) {
        define('ENVIRONMENT', 'production');
    }

    require $paths->systemDirectory . '/Boot.php';

    // Tentar bootar (isso pode causar a página branca)
    CodeIgniter\Boot::bootWeb($paths);

    $bootSuccess = true;

} catch (Throwable $e) {
    $bootError = $e;
}

$bootOutput = ob_get_clean();

if ($bootSuccess) {
    showStatus("Boot executado com sucesso!", 'success');
    if (!empty($bootOutput)) {
        echo "<strong>Output do boot:</strong><pre>" . htmlspecialchars($bootOutput) . "</pre>";
    } else {
        showStatus("Boot não produziu output (isso pode ser o problema!)", 'warning');
    }
} else {
    showStatus("ERRO no boot!", 'error');
    if ($bootError) {
        echo "<strong>Mensagem:</strong> " . htmlspecialchars($bootError->getMessage()) . "<br>";
        echo "<strong>Arquivo:</strong> " . $bootError->getFile() . ":" . $bootError->getLine() . "<br>";
        echo "<pre>" . htmlspecialchars($bootError->getTraceAsString()) . "</pre>";
    }
}

echo "</div><br>";

// 7. VERIFICAR LOGS RECENTES
echo "<h2>7. Logs Recentes do Sistema</h2>";
$logFile = $rootDir . '/writable/logs/log-' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -20); // Últimas 20 linhas

    if (count($recentLines) > 0) {
        echo "<pre>" . htmlspecialchars(implode("\n", $recentLines)) . "</pre>";
    } else {
        showStatus("Log vazio", 'info');
    }
} else {
    showStatus("Arquivo de log não existe: $logFile", 'warning');
}
echo "<br>";

// 8. VERIFICAR HEADERS
echo "<h2>8. Headers e Output Buffering</h2>";
showStatus("Headers enviados? " . (headers_sent() ? 'SIM' : 'NÃO'), headers_sent() ? 'warning' : 'success');
showStatus("Output buffering level: " . ob_get_level(), 'info');
showStatus("Output buffering length: " . ob_get_length() . " bytes", 'info');

// Mostrar headers que serão enviados
$headers = headers_list();
if (count($headers) > 0) {
    echo "<strong>Headers preparados:</strong><pre>";
    foreach ($headers as $header) {
        echo htmlspecialchars($header) . "\n";
    }
    echo "</pre>";
} else {
    showStatus("Nenhum header preparado", 'info');
}
echo "<br>";

// 9. TESTE DIRETO DO CONTROLLER HOME
echo "<h2>9. Teste Direto do Controller Home</h2>";
try {
    $homeController = $rootDir . '/app/Controllers/Home.php';
    if (file_exists($homeController)) {
        showStatus("Home.php encontrado", 'success');

        // Tentar instanciar
        if (class_exists('App\Controllers\Home')) {
            showStatus("Classe App\\Controllers\\Home existe", 'success');

            // Verificar métodos
            $reflection = new ReflectionClass('App\Controllers\Home');
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            showStatus("Métodos públicos: " . count($methods), 'info');
            foreach ($methods as $method) {
                if (!$method->isConstructor() && !$method->isDestructor()) {
                    echo "  - " . $method->getName() . "<br>";
                }
            }
        } else {
            showStatus("Classe App\\Controllers\\Home NÃO existe!", 'error');
        }
    } else {
        showStatus("Home.php NÃO encontrado!", 'error');
    }
} catch (Throwable $e) {
    showStatus("Erro ao verificar Home controller: " . $e->getMessage(), 'error');
}
echo "<br>";

// 10. RESUMO E RECOMENDAÇÕES
echo "<h2>10. Resumo e Próximos Passos</h2>";
echo "<div style='background:#ffffcc;padding:15px;border:2px solid #ffcc00'>";
echo "<strong>Possíveis causas da página branca:</strong><ul>";
echo "<li>Boot::bootWeb() não está retornando output</li>";
echo "<li>Erro silencioso no controller padrão</li>";
echo "<li>Problema com rotas</li>";
echo "<li>Output buffering sendo descartado</li>";
echo "<li>Erro no .htaccess causando problemas de redirecionamento</li>";
echo "</ul>";
echo "<strong>Recomendações:</strong><ul>";
echo "<li>Verificar logs do Apache no cPanel</li>";
echo "<li>Testar acessar diretamente: /index.php/</li>";
echo "<li>Verificar se .htaccess está causando loop de redirecionamento</li>";
echo "<li>Habilitar modo debug no .env</li>";
echo "</ul></div>";

echo "<hr><p><em>Diagnóstico concluído em " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";

// Enviar todo o output
$finalOutput = ob_get_clean();
echo $finalOutput;
