<?php
/**
 * Script de diagnóstico ultra-simples
 * Captura qualquer erro PHP que esteja causando a página branca
 */

// Habilitar TODOS os erros
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Capturar erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<h1>ERRO FATAL DETECTADO:</h1>";
        echo "<pre>";
        print_r($error);
        echo "</pre>";
    }
});

// Capturar warnings e notices
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<div style='background: #ffcccc; padding: 10px; margin: 5px; border: 1px solid red;'>";
    echo "<strong>ERRO PHP (Tipo: $errno):</strong><br>";
    echo "<strong>Mensagem:</strong> $errstr<br>";
    echo "<strong>Arquivo:</strong> $errfile<br>";
    echo "<strong>Linha:</strong> $errline<br>";
    echo "</div>";
    return false; // Continua processamento normal
});

echo "<h1>Diagnóstico de Erros PHP</h1>";
echo "<hr>";

// 1. Informações básicas
echo "<h2>1. Informações do PHP</h2>";
echo "Versão PHP: " . phpversion() . "<br>";
echo "SAPI: " . php_sapi_name() . "<br>";
echo "Sistema: " . PHP_OS . "<br>";
echo "<hr>";

// 2. Verificar configurações de sessão
echo "<h2>2. Configurações de Sessão</h2>";
echo "session.auto_start: " . ini_get('session.auto_start') . "<br>";
echo "session.gc_divisor: " . ini_get('session.gc_divisor') . "<br>";
echo "session.gc_probability: " . ini_get('session.gc_probability') . "<br>";
echo "session.save_path: " . ini_get('session.save_path') . "<br>";
echo "Status da sessão: " . session_status() . " (1=disabled, 2=active, 3=none)<br>";
echo "<hr>";

// 3. Testar início de sessão
echo "<h2>3. Teste de Início de Sessão</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "✅ Sessão iniciada com sucesso!<br>";
    } else {
        echo "⚠️ Sessão já estava ativa<br>";
    }
} catch (Throwable $e) {
    echo "❌ ERRO ao iniciar sessão: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// 4. Verificar estrutura de arquivos
echo "<h2>4. Estrutura de Arquivos</h2>";
$rootDir = dirname(__DIR__);
echo "Diretório raiz: $rootDir<br>";
echo "Diretório public: " . __DIR__ . "<br><br>";

$arquivos = [
    'vendor/autoload.php' => $rootDir . '/vendor/autoload.php',
    'app/Config/Paths.php' => $rootDir . '/app/Config/Paths.php',
    'system/bootstrap.php' => $rootDir . '/system/bootstrap.php',
    '.env' => $rootDir . '/.env',
    'writable/logs' => $rootDir . '/writable/logs',
];

foreach ($arquivos as $nome => $caminho) {
    $existe = file_exists($caminho);
    $legivel = $existe && is_readable($caminho);

    echo "$nome: ";
    if ($existe) {
        echo "✅ Existe";
        if (is_dir($caminho)) {
            $escrevivel = is_writable($caminho);
            echo " | " . ($escrevivel ? "✅ Escrevível" : "❌ NÃO escrevível");
        } elseif (!$legivel) {
            echo " | ❌ NÃO legível";
        }
    } else {
        echo "❌ NÃO existe";
    }
    echo "<br>";
}
echo "<hr>";

// 5. Tentar carregar CodeIgniter
echo "<h2>5. Teste de Carregamento do CodeIgniter</h2>";
try {
    $autoloadPath = $rootDir . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require $autoloadPath;
        echo "✅ Autoload carregado com sucesso<br>";

        // Verificar se classes do CI estão disponíveis
        if (class_exists('CodeIgniter\CodeIgniter')) {
            echo "✅ Classe CodeIgniter\\CodeIgniter disponível<br>";
        } else {
            echo "❌ Classe CodeIgniter\\CodeIgniter NÃO disponível<br>";
        }
    } else {
        echo "❌ Arquivo autoload.php não encontrado<br>";
    }
} catch (Throwable $e) {
    echo "❌ ERRO ao carregar autoload: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// 6. Verificar index.php
echo "<h2>6. Análise do index.php</h2>";
$indexPath = __DIR__ . '/index.php';
if (file_exists($indexPath)) {
    echo "✅ index.php existe<br>";
    echo "Tamanho: " . filesize($indexPath) . " bytes<br>";

    // Tentar executar index.php e capturar output
    echo "<br><strong>Tentando executar index.php:</strong><br>";
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;'>";

    ob_start();
    try {
        include $indexPath;
        $output = ob_get_clean();
        if (empty($output)) {
            echo "⚠️ index.php não produziu output<br>";
        } else {
            echo "✅ Output do index.php:<br>";
            echo htmlspecialchars(substr($output, 0, 500));
            if (strlen($output) > 500) {
                echo "... (truncado)";
            }
        }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "❌ ERRO ao executar index.php:<br>";
        echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>Arquivo:</strong> " . $e->getFile() . "<br>";
        echo "<strong>Linha:</strong> " . $e->getLine() . "<br>";
        echo "<strong>Stack trace:</strong><br><pre>" . $e->getTraceAsString() . "</pre>";
    }

    echo "</div>";
} else {
    echo "❌ index.php NÃO encontrado<br>";
}
echo "<hr>";

// 7. Informações adicionais
echo "<h2>7. Informações Adicionais</h2>";
echo "Memória usada: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB<br>";
echo "Memória máxima: " . ini_get('memory_limit') . "<br>";
echo "Tempo de execução: " . ini_get('max_execution_time') . "s<br>";

echo "<hr>";
echo "<h3>FIM DO DIAGNÓSTICO</h3>";
echo "<p>Acesse este script em: <strong>https://ponto.supportsondagens.com.br/erro.php</strong></p>";
