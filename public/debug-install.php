<?php
/**
 * Debug Installer - Verificação de Instalação
 *
 * Este script verifica se a instalação foi bem-sucedida e testa o login do admin.
 *
 * IMPORTANTE: DELETE este arquivo após corrigir os problemas!
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load .env file
define('BASEPATH', dirname(__DIR__));
define('ENV_FILE', BASEPATH . '/.env');

if (!file_exists(ENV_FILE)) {
    die("❌ Arquivo .env não encontrado! Execute a instalação primeiro.");
}

// Parse .env
$env = file_get_contents(ENV_FILE);
preg_match('/database\.default\.hostname=(.+)/', $env, $host);
preg_match('/database\.default\.database=(.+)/', $env, $db);
preg_match('/database\.default\.username=(.+)/', $env, $user);
preg_match('/database\.default\.password=(.+)/', $env, $pass);
preg_match('/database\.default\.port=(.+)/', $env, $port);

$db_host = trim($host[1] ?? 'localhost');
$db_name = trim($db[1] ?? '');
$db_user = trim($user[1] ?? 'root');
$db_pass = trim($pass[1] ?? '');
$db_port = (int)trim($port[1] ?? 3306);

if (empty($db_name)) {
    die("❌ Configuração do banco de dados não encontrada no .env");
}

// Connect to database
try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    if ($mysqli->connect_error) {
        die("❌ Erro de conexão: " . $mysqli->connect_error);
    }

    $mysqli->set_charset('utf8mb4');

} catch (Exception $e) {
    die("❌ Erro: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Instalador</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #2d3748; margin-bottom: 20px; }
        h2 { color: #4a5568; margin: 30px 0 15px; font-size: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 6px; font-size: 14px; }
        .success { background: #f0fff4; border-left: 4px solid #38a169; color: #22543d; }
        .error { background: #fff5f5; border-left: 4px solid #e53e3e; color: #742a2a; }
        .warning { background: #fffaf0; border-left: 4px solid #ed8936; color: #7c2d12; }
        .info { background: #ebf8ff; border-left: 4px solid #4299e1; color: #2c5282; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; color: #2d3748; }
        code { background: #edf2f7; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; font-size: 13px; }
        .btn { background: #4299e1; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px 10px 0; }
        .btn:hover { background: #3182ce; }
        .btn-danger { background: #e53e3e; }
        .btn-danger:hover { background: #c53030; }
        form { margin: 20px 0; padding: 20px; background: #f7fafc; border-radius: 6px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #2d3748; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; margin-bottom: 15px; }
        .hash-display { background: #2d3748; color: #48bb78; padding: 15px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 12px; overflow-x: auto; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug do Instalador</h1>

        <?php
        // Check database connection
        echo "<h2>1. Conexão com Banco de Dados</h2>";
        echo "<div class='status success'>✅ Conectado com sucesso ao banco: <code>{$db_name}</code> em <code>{$db_host}:{$db_port}</code></div>";

        // Check if employees table exists
        echo "<h2>2. Verificação de Tabelas</h2>";
        $result = $mysqli->query("SHOW TABLES LIKE 'employees'");
        if ($result->num_rows > 0) {
            echo "<div class='status success'>✅ Tabela <code>employees</code> existe</div>";
        } else {
            echo "<div class='status error'>❌ Tabela <code>employees</code> NÃO encontrada! Execute o instalador primeiro.</div>";
            exit;
        }

        // Check table structure
        echo "<h2>3. Estrutura da Tabela employees</h2>";
        $result = $mysqli->query("DESCRIBE employees");
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td><code>{$row['Field']}</code></td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>" . ($row['Default'] ?? '<em>NULL</em>') . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Check admin users
        echo "<h2>4. Usuários Administradores Cadastrados</h2>";
        $result = $mysqli->query("SELECT id, name, email, cpf, unique_code, role, active, created_at, LENGTH(password) as password_length FROM employees WHERE role = 'admin'");

        if ($result->num_rows === 0) {
            echo "<div class='status error'>❌ Nenhum administrador encontrado! A instalação pode ter falho.</div>";
        } else {
            echo "<div class='status success'>✅ Encontrados {$result->num_rows} administrador(es)</div>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>CPF</th><th>Código</th><th>Ativo</th><th>Senha (tamanho)</th><th>Criado em</th></tr>";
            $admins = [];
            while ($row = $result->fetch_assoc()) {
                $admins[] = $row;
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['name']}</td>";
                echo "<td><code>{$row['email']}</code></td>";
                echo "<td>{$row['cpf']}</td>";
                echo "<td><code>{$row['unique_code']}</code></td>";
                echo "<td>" . ($row['active'] ? '✅ Sim' : '❌ Não') . "</td>";
                echo "<td>{$row['password_length']} caracteres</td>";
                echo "<td>{$row['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";

            // Verify password hash format
            echo "<h2>5. Verificação do Hash da Senha</h2>";
            $stmt = $mysqli->prepare("SELECT id, email, password FROM employees WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $stmt->bind_result($admin_id, $admin_email, $password_hash);
            $stmt->fetch();
            $stmt->close();

            echo "<p><strong>Email do admin:</strong> <code>{$admin_email}</code></p>";
            echo "<p><strong>Hash armazenado:</strong></p>";
            echo "<div class='hash-display'>{$password_hash}</div>";

            // Check hash format
            if (strlen($password_hash) === 60 && substr($password_hash, 0, 4) === '$2y$') {
                echo "<div class='status success'>✅ Hash está no formato BCRYPT correto (60 caracteres, começa com \$2y\$)</div>";
            } elseif (strlen($password_hash) === 60 && substr($password_hash, 0, 4) === '$2a$') {
                echo "<div class='status success'>✅ Hash está no formato BCRYPT correto (60 caracteres, começa com \$2a\$)</div>";
            } else {
                echo "<div class='status error'>❌ Hash NÃO está no formato BCRYPT esperado!<br>";
                echo "Tamanho: " . strlen($password_hash) . " caracteres (esperado: 60)<br>";
                echo "Prefixo: " . substr($password_hash, 0, 4) . " (esperado: \$2y\$ ou \$2a\$)</div>";
            }
        }

        // Test login form
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
            echo "<h2>6. Resultado do Teste de Login</h2>";

            $test_email = trim($_POST['email'] ?? '');
            $test_password = $_POST['password'] ?? '';

            if (empty($test_email) || empty($test_password)) {
                echo "<div class='status error'>❌ Email e senha são obrigatórios</div>";
            } else {
                // Get user from database
                $stmt = $mysqli->prepare("SELECT id, name, email, password, active FROM employees WHERE email = ? AND role = 'admin'");
                $stmt->bind_param('s', $test_email);
                $stmt->execute();
                $stmt->bind_result($user_id, $user_name, $user_email, $stored_hash, $user_active);
                $found = $stmt->fetch();
                $stmt->close();

                if (!$found) {
                    echo "<div class='status error'>❌ Usuário não encontrado com email: <code>{$test_email}</code></div>";
                } else {
                    echo "<div class='status info'>ℹ️ Usuário encontrado: {$user_name} (ID: {$user_id})</div>";

                    if (!$user_active) {
                        echo "<div class='status error'>❌ Usuário está INATIVO</div>";
                    } else {
                        echo "<div class='status success'>✅ Usuário está ATIVO</div>";
                    }

                    // Test password verification
                    echo "<p><strong>Testando verificação de senha...</strong></p>";

                    if (password_verify($test_password, $stored_hash)) {
                        echo "<div class='status success'>✅ SENHA CORRETA! password_verify() retornou TRUE</div>";
                        echo "<div class='status success'>🎉 O login deveria funcionar! Se não está funcionando, o problema está no código de login do sistema.</div>";
                    } else {
                        echo "<div class='status error'>❌ SENHA INCORRETA! password_verify() retornou FALSE</div>";
                        echo "<div class='status warning'>⚠️ Possíveis causas:<br>";
                        echo "1. Você digitou a senha errada<br>";
                        echo "2. A senha foi corrompida durante a instalação<br>";
                        echo "3. O hash não está correto no banco de dados</div>";

                        // Test with a new hash
                        echo "<p><strong>Gerando novo hash para comparação:</strong></p>";
                        $new_hash = password_hash($test_password, PASSWORD_BCRYPT);
                        echo "<div class='hash-display'>{$new_hash}</div>";

                        if (password_verify($test_password, $new_hash)) {
                            echo "<div class='status success'>✅ Novo hash funciona corretamente com password_verify()</div>";
                            echo "<div class='status warning'>⚠️ Isso indica que o problema está na senha armazenada no banco!<br>";
                            echo "Você pode usar o botão abaixo para atualizar a senha no banco de dados.</div>";

                            // Show update button
                            echo "<form method='post' onsubmit='return confirm(\"Tem certeza que deseja atualizar a senha no banco de dados?\")'>";
                            echo "<input type='hidden' name='update_password' value='1'>";
                            echo "<input type='hidden' name='user_id' value='{$user_id}'>";
                            echo "<input type='hidden' name='new_hash' value='{$new_hash}'>";
                            echo "<button type='submit' class='btn btn-danger'>🔧 Corrigir Senha no Banco de Dados</button>";
                            echo "</form>";
                        }
                    }
                }
            }
        }

        // Handle password update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
            echo "<h2>7. Atualização de Senha</h2>";

            $user_id = (int)$_POST['user_id'];
            $new_hash = $_POST['new_hash'];

            $stmt = $mysqli->prepare("UPDATE employees SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $new_hash, $user_id);

            if ($stmt->execute()) {
                echo "<div class='status success'>✅ Senha atualizada com sucesso! Tente fazer login novamente.</div>";
            } else {
                echo "<div class='status error'>❌ Erro ao atualizar senha: " . $stmt->error . "</div>";
            }

            $stmt->close();
        }

        // Login test form
        if (!isset($_POST['test_login'])) {
            echo "<h2>6. Testar Login</h2>";
            echo "<p>Digite as credenciais do administrador para testar se o login funcionaria:</p>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='test_login' value='1'>";
            echo "<label>Email:</label>";
            echo "<input type='email' name='email' required placeholder='admin@exemplo.com'>";
            echo "<label>Senha:</label>";
            echo "<input type='password' name='password' required placeholder='A senha que você definiu'>";
            echo "<button type='submit' class='btn'>🔐 Testar Login</button>";
            echo "</form>";
        }

        // Close connection
        $mysqli->close();
        ?>

        <h2>Ações Disponíveis</h2>
        <a href="?refresh=1" class="btn">🔄 Atualizar Página</a>
        <a href="/auth/login" class="btn">🔑 Ir para Login</a>
        <a href="/install.php" class="btn">⚙️ Reinstalar Sistema</a>

        <div class="status warning" style="margin-top: 30px;">
            <strong>⚠️ IMPORTANTE:</strong> Este arquivo contém informações sensíveis!<br>
            DELETE o arquivo <code>public/debug-install.php</code> após corrigir os problemas.
        </div>
    </div>
</body>
</html>
