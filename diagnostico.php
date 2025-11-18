<?php
/**
 * DIAGNÓSTICO DO INSTALADOR
 * Execute este arquivo para identificar problemas no seu servidor
 *
 * Acesse: http://seu-dominio.com/diagnostico.php
 */

// CSS para output bonito
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico do Instalador</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #00ff00; padding: 20px; }
        .ok { color: #00ff00; }
        .error { color: #ff4444; }
        .warning { color: #ffaa00; }
        .section { background: #2d2d2d; padding: 15px; margin: 15px 0; border-left: 4px solid #667eea; }
        h2 { color: #667eea; }
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<h1>🔍 DIAGNÓSTICO DO SISTEMA - INSTALADOR</h1>
<p>Servidor: <?= $_SERVER['SERVER_NAME'] ?? 'localhost' ?></p>
<p>Data: <?= date('Y-m-d H:i:s') ?></p>
<hr>

<?php

// Função auxiliar
function check($name, $condition, $success_msg, $error_msg) {
    echo '<div class="section">';
    echo "<strong>$name:</strong> ";
    if ($condition) {
        echo "<span class='ok'>✓ $success_msg</span>";
    } else {
        echo "<span class='error'>✗ $error_msg</span>";
    }
    echo '</div>';
    return $condition;
}

$allOk = true;

// 1. VERSÃO DO PHP
echo '<h2>1. VERSÃO DO PHP</h2>';
$phpOk = check(
    'PHP Version',
    version_compare(PHP_VERSION, '8.1.0', '>='),
    'PHP ' . PHP_VERSION . ' (OK)',
    'PHP ' . PHP_VERSION . ' (Requer 8.1+)'
);
$allOk = $allOk && $phpOk;

// 2. EXTENSÕES NECESSÁRIAS
echo '<h2>2. EXTENSÕES PHP</h2>';
$extensions = [
    'pdo' => 'PDO',
    'pdo_mysql' => 'PDO MySQL',
    'mysqli' => 'MySQLi',
    'mbstring' => 'Multibyte String',
    'json' => 'JSON',
    'openssl' => 'OpenSSL',
    'session' => 'Session'
];

foreach ($extensions as $ext => $name) {
    $loaded = extension_loaded($ext);
    check(
        $name,
        $loaded,
        'Instalada',
        'NÃO INSTALADA - Execute: sudo apt-get install php-' . $ext
    );
    $allOk = $allOk && $loaded;
}

// 3. DIRETÓRIOS E PERMISSÕES
echo '<h2>3. DIRETÓRIOS E PERMISSÕES</h2>';

$writablePath = __DIR__ . '/writable';
$envPath = __DIR__ . '/.env';
$installPath = __DIR__ . '/install.php';
$publicInstallPath = __DIR__ . '/public/install.php';

// Diretório writable
$writableExists = is_dir($writablePath);
check(
    'Diretório writable/',
    $writableExists,
    'Existe',
    'NÃO EXISTE - Crie: mkdir -p writable'
);

if ($writableExists) {
    $writableWritable = is_writable($writablePath);
    check(
        'Permissão writable/',
        $writableWritable,
        'Gravável (OK)',
        'SEM PERMISSÃO - Execute: chmod 777 writable/'
    );
    $allOk = $allOk && $writableWritable;
}

// Raiz do projeto
$rootWritable = is_writable(__DIR__);
check(
    'Permissão raiz (para criar .env)',
    $rootWritable,
    'Gravável (OK)',
    'SEM PERMISSÃO - Execute: chmod 755 .'
);

// Arquivos do instalador
check(
    'Arquivo install.php (raiz)',
    file_exists($installPath),
    'Existe (' . filesize($installPath) . ' bytes)',
    'NÃO EXISTE'
);

check(
    'Arquivo install.php (public/)',
    file_exists($publicInstallPath),
    'Existe (' . filesize($publicInstallPath) . ' bytes)',
    'NÃO EXISTE'
);

// 4. TESTE DE ESCRITA
echo '<h2>4. TESTE DE ESCRITA</h2>';
$testFile = $writablePath . '/_test_write_' . time() . '.txt';
$writeOk = @file_put_contents($testFile, 'test') !== false;
check(
    'Criar arquivo em writable/',
    $writeOk,
    'OK',
    'FALHOU - Verifique permissões'
);
if ($writeOk) @unlink($testFile);

// 5. PDO E MYSQL
echo '<h2>5. PDO E MYSQL</h2>';
$pdoDrivers = PDO::getAvailableDrivers();
echo '<div class="section">';
echo '<strong>Drivers PDO disponíveis:</strong> ' . implode(', ', $pdoDrivers) . '<br>';
$hasMysql = in_array('mysql', $pdoDrivers);
if ($hasMysql) {
    echo "<span class='ok'>✓ Driver MySQL disponível</span>";
} else {
    echo "<span class='error'>✗ Driver MySQL NÃO disponível</span>";
    $allOk = false;
}
echo '</div>';

// 6. TESTE DE CONEXÃO MYSQL (se credenciais fornecidas)
echo '<h2>6. TESTE DE CONEXÃO MYSQL</h2>';
echo '<div class="section">';
echo '<form method="POST" style="background: #333; padding: 15px; border-radius: 5px;">';
echo '<h3 style="color: #fff;">Testar Conexão MySQL:</h3>';
echo '<input type="text" name="test_host" placeholder="Host (localhost)" value="localhost" style="width: 200px; margin: 5px;"><br>';
echo '<input type="number" name="test_port" placeholder="Porta (3306)" value="3306" style="width: 200px; margin: 5px;"><br>';
echo '<input type="text" name="test_database" placeholder="Database" value="" style="width: 200px; margin: 5px;"><br>';
echo '<input type="text" name="test_username" placeholder="Usuário" value="" style="width: 200px; margin: 5px;"><br>';
echo '<input type="password" name="test_password" placeholder="Senha" value="" style="width: 200px; margin: 5px;"><br>';
echo '<button type="submit" name="test_connection" style="background: #667eea; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">🔍 Testar Conexão</button>';
echo '</form>';

if (isset($_POST['test_connection'])) {
    echo '<div style="margin-top: 15px; background: #000; padding: 15px; border-radius: 5px;">';
    echo '<h4 style="color: #ffaa00;">Resultado do Teste:</h4>';

    $host = $_POST['test_host'] ?? 'localhost';
    $port = $_POST['test_port'] ?? '3306';
    $database = $_POST['test_database'] ?? '';
    $username = $_POST['test_username'] ?? '';
    $password = $_POST['test_password'] ?? '';

    if (empty($database) || empty($username)) {
        echo "<span class='error'>✗ Preencha Database e Usuário</span>";
    } else {
        try {
            echo "Tentando conectar: {$username}@{$host}:{$port}<br>";

            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            echo "<span class='ok'>✓ Conexão estabelecida!</span><br>";

            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            echo "Versão MySQL: {$version}<br>";

            // Verificar se database existe
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$database}'");
            if ($stmt->rowCount() > 0) {
                echo "<span class='ok'>✓ Database '{$database}' existe</span><br>";

                // Conectar ao database
                $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);

                // Listar tabelas
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (count($tables) > 0) {
                    echo "<span class='warning'>⚠ Database contém " . count($tables) . " tabela(s):</span><br>";
                    echo "<pre>" . implode("\n", array_slice($tables, 0, 10)) . (count($tables) > 10 ? "\n..." : "") . "</pre>";
                } else {
                    echo "<span class='ok'>✓ Database vazio (pronto para instalação)</span><br>";
                }
            } else {
                echo "<span class='warning'>⚠ Database '{$database}' NÃO existe (será criado)</span><br>";
            }

        } catch (PDOException $e) {
            echo "<span class='error'>✗ ERRO: " . $e->getMessage() . "</span><br>";
            echo "Código: " . $e->getCode() . "<br><br>";

            if ($e->getCode() == 1045) {
                echo "<span class='warning'>Dica: Usuário ou senha incorretos</span>";
            } elseif ($e->getCode() == 2002) {
                echo "<span class='warning'>Dica: MySQL não está rodando. Execute: systemctl status mysql</span>";
            }
        }
    }
    echo '</div>';
}
echo '</div>';

// 7. INFORMAÇÕES DO SERVIDOR
echo '<h2>7. INFORMAÇÕES DO SERVIDOR</h2>';
echo '<div class="section">';
echo '<strong>Sistema Operacional:</strong> ' . PHP_OS . '<br>';
echo '<strong>SAPI:</strong> ' . php_sapi_name() . '<br>';
echo '<strong>Document Root:</strong> ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . '<br>';
echo '<strong>Script Filename:</strong> ' . __FILE__ . '<br>';
echo '<strong>Server Software:</strong> ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . '<br>';
echo '<strong>PHP Memory Limit:</strong> ' . ini_get('memory_limit') . '<br>';
echo '<strong>Max Execution Time:</strong> ' . ini_get('max_execution_time') . 's<br>';
echo '</div>';

// 8. TESTE DO INSTALADOR
echo '<h2>8. STATUS DO INSTALADOR</h2>';
echo '<div class="section">';

// Verificar qual instalador usar
$usePublic = strpos($_SERVER['SCRIPT_FILENAME'] ?? __FILE__, '/public/') !== false;
$installerUrl = $usePublic ? '/install.php' : '/install.php';

echo '<strong>Instalador detectado:</strong> ' . ($usePublic ? 'public/install.php' : 'install.php') . '<br>';
echo '<strong>URL sugerida:</strong> <a href="' . $installerUrl . '" target="_blank" style="color: #00ff00;">' . $installerUrl . '</a><br><br>';

if (file_exists(LOCK_FILE ?? __DIR__ . '/writable/installed.lock')) {
    echo "<span class='warning'>⚠ Sistema já instalado (lock file existe)</span><br>";
    echo "Para reinstalar: <a href='$installerUrl?force_reinstall' style='color: #ffaa00;'>$installerUrl?force_reinstall</a>";
} else {
    echo "<span class='ok'>✓ Pronto para instalação</span>";
}
echo '</div>';

// 9. RESUMO
echo '<h2>9. RESUMO</h2>';
echo '<div class="section">';
if ($allOk) {
    echo "<h3 class='ok'>✓ TUDO OK! O instalador deve funcionar</h3>";
    echo "<p>Acesse: <a href='$installerUrl' style='color: #00ff00; font-size: 18px; font-weight: bold;'>$installerUrl</a></p>";
} else {
    echo "<h3 class='error'>✗ HÁ PROBLEMAS A CORRIGIR</h3>";
    echo "<p>Revise os itens marcados com ✗ acima e corrija antes de prosseguir.</p>";
}
echo '</div>';

// 10. COMANDOS ÚTEIS
echo '<h2>10. COMANDOS ÚTEIS</h2>';
echo '<div class="section">';
echo '<pre style="color: #00ff00;">';
echo "# Verificar MySQL\n";
echo "systemctl status mysql\n\n";
echo "# Corrigir permissões\n";
echo "chmod -R 755 .\n";
echo "chmod -R 777 writable/\n\n";
echo "# Instalar extensões PHP\n";
echo "sudo apt-get install php-{pdo,mysql,mysqli,mbstring,json}\n\n";
echo "# Criar database manualmente\n";
echo "mysql -u root -p\n";
echo "CREATE DATABASE supportson_suppPONTO;\n";
echo "GRANT ALL ON supportson_suppPONTO.* TO 'supportson_support'@'%';\n";
echo "FLUSH PRIVILEGES;\n";
echo '</pre>';
echo '</div>';

?>

<hr>
<p style="text-align: center; color: #666;">Diagnóstico gerado por: Sistema de Ponto Eletrônico v3.0.0</p>
</body>
</html>
