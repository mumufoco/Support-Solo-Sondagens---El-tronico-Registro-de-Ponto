<?php
/**
 * Script de Teste de Conexão com Banco de Dados
 * Diagnóstico para identificar problemas de conexão
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Teste de Conexão com Banco de Dados</h1>";
echo "<hr>";

// Carregar .env
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    echo "✅ Arquivo .env encontrado<br>";
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $dbConfig = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (strpos($key, 'database.default') === 0 || strpos($key, 'DB_') === 0) {
                $dbConfig[$key] = $value;
            }
        }
    }

    echo "<h3>Configurações do Banco:</h3>";
    echo "<pre>";
    print_r($dbConfig);
    echo "</pre>";
} else {
    echo "❌ Arquivo .env NÃO encontrado<br>";
}

echo "<hr>";
echo "<h3>Testando Conexão MySQLi</h3>";

$hostname = $dbConfig['database.default.hostname'] ?? 'localhost';
$username = $dbConfig['database.default.username'] ?? 'root';
$password = $dbConfig['database.default.password'] ?? '';
$database = $dbConfig['database.default.database'] ?? 'ponto_eletronico';
$port = $dbConfig['database.default.port'] ?? 3306;

echo "Host: <strong>$hostname</strong><br>";
echo "User: <strong>$username</strong><br>";
echo "Database: <strong>$database</strong><br>";
echo "Port: <strong>$port</strong><br><br>";

// Tentar conexão
$mysqli = @new mysqli($hostname, $username, $password, $database, $port);

if ($mysqli->connect_error) {
    echo "❌ <strong>ERRO DE CONEXÃO:</strong><br>";
    echo "Código: " . $mysqli->connect_errno . "<br>";
    echo "Mensagem: " . $mysqli->connect_error . "<br><br>";

    echo "<h4>Possíveis Causas:</h4>";
    echo "<ul>";
    echo "<li>MySQL não está rodando</li>";
    echo "<li>Credenciais incorretas</li>";
    echo "<li>Banco de dados não existe</li>";
    echo "<li>Firewall bloqueando conexão</li>";
    echo "</ul>";

    // Tentar conexão sem database para verificar se é problema de database inexistente
    echo "<h4>Testando conexão sem database...</h4>";
    $mysqli2 = @new mysqli($hostname, $username, $password, '', $port);
    if ($mysqli2->connect_error) {
        echo "❌ Falha na conexão com MySQL Server<br>";
        echo "Mensagem: " . $mysqli2->connect_error . "<br>";
    } else {
        echo "✅ Conexão com MySQL Server OK<br>";
        echo "❌ Mas o database '<strong>$database</strong>' não existe!<br><br>";

        echo "<h4>Databases disponíveis:</h4>";
        $result = $mysqli2->query("SHOW DATABASES");
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";

        $mysqli2->close();
    }
} else {
    echo "✅ <strong>CONEXÃO ESTABELECIDA COM SUCESSO!</strong><br><br>";

    echo "Versão do MySQL: " . $mysqli->server_info . "<br>";
    echo "Character Set: " . $mysqli->character_set_name() . "<br><br>";

    // Listar tabelas
    echo "<h4>Tabelas no database '$database':</h4>";
    $result = $mysqli->query("SHOW TABLES");

    if ($result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "⚠️ Nenhuma tabela encontrada (database vazio)<br>";
    }

    $mysqli->close();
}

echo "<hr>";
echo "<h3>Verificando extensões PHP necessárias</h3>";

$extensions = ['mysqli', 'pdo', 'pdo_mysql', 'intl', 'json', 'mbstring', 'xml'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? "✅" : "❌") . " $ext<br>";
}

echo "<hr>";
echo "<small>Teste concluído em " . date('Y-m-d H:i:s') . "</small>";
