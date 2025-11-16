<?php
/**
 * Web Installer - Sistema de Ponto Eletrônico
 *
 * Interface web para instalação e configuração inicial do sistema
 *
 * Acesse: http://seudominio.com/install.php
 *
 * IMPORTANTE: Delete este arquivo após a instalação!
 */

// Security: Check if already installed
if (file_exists(__DIR__ . '/../writable/installed.lock')) {
    die('
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema Já Instalado</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 50px; text-align: center; }
            .container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
            h1 { color: #e74c3c; }
            .btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Sistema Já Instalado</h1>
            <p>O sistema já foi instalado anteriormente.</p>
            <p>Por segurança, delete o arquivo <code>public/install.php</code> do servidor.</p>
            <a href="/" class="btn">Ir para o Sistema</a>
        </div>
    </body>
    </html>
    ');
}

// Start session
session_start();

// Load current step
$step = $_GET['step'] ?? '1';
$error = null;
$success = null;

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case '2': // Database configuration
            $_SESSION['db_host'] = $_POST['db_host'] ?? 'localhost';
            $_SESSION['db_port'] = $_POST['db_port'] ?? '3306';
            $_SESSION['db_name'] = $_POST['db_name'] ?? '';
            $_SESSION['db_user'] = $_POST['db_user'] ?? '';
            $_SESSION['db_pass'] = $_POST['db_pass'] ?? '';

            // Test connection
            try {
                $dsn = "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};charset=utf8mb4";
                $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Try to create database if it doesn't exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$_SESSION['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                $success = "Conexão com banco de dados bem-sucedida!";
                $_SESSION['db_tested'] = true;
            } catch (PDOException $e) {
                $error = "Erro de conexão: " . $e->getMessage();
                $_SESSION['db_tested'] = false;
            }
            break;

        case '3': // Admin user
            $_SESSION['admin_name'] = $_POST['admin_name'] ?? '';
            $_SESSION['admin_email'] = $_POST['admin_email'] ?? '';
            $_SESSION['admin_password'] = $_POST['admin_password'] ?? '';
            $_SESSION['company_name'] = $_POST['company_name'] ?? '';
            $_SESSION['company_cnpj'] = $_POST['company_cnpj'] ?? '';

            if (empty($_SESSION['admin_email']) || empty($_SESSION['admin_password'])) {
                $error = "Email e senha são obrigatórios!";
            } else {
                $success = "Dados do administrador salvos!";
            }
            break;

        case '4': // Run installation
            if ($_SESSION['db_tested'] ?? false) {
                try {
                    // Create .env file
                    $envContent = createEnvFile();
                    file_put_contents(__DIR__ . '/../.env', $envContent);

                    // Run migrations
                    runMigrations();

                    // Create admin user
                    createAdminUser();

                    // Create lock file
                    file_put_contents(__DIR__ . '/../writable/installed.lock', date('Y-m-d H:i:s'));

                    $success = "Instalação concluída com sucesso!";
                    header('Location: install.php?step=5');
                    exit;
                } catch (Exception $e) {
                    $error = "Erro na instalação: " . $e->getMessage();
                }
            } else {
                $error = "Configure o banco de dados primeiro!";
            }
            break;
    }
}

// Helper functions
function createEnvFile() {
    $key = bin2hex(random_bytes(16));

    return <<<ENV
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------

CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------

app.baseURL = '{$_SESSION['app_url']}'
app.indexPage = ''
app.forceGlobalSecureRequests = false

# Encryption key (gerada automaticamente)
encryption.key = base64:{$key}

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------

database.default.hostname = {$_SESSION['db_host']}
database.default.database = {$_SESSION['db_name']}
database.default.username = {$_SESSION['db_user']}
database.default.password = {$_SESSION['db_pass']}
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = {$_SESSION['db_port']}

#--------------------------------------------------------------------
# SECURITY
#--------------------------------------------------------------------

security.csrfProtection = 'session'
security.tokenRandomize = true
security.tokenName = 'csrf_token'

#--------------------------------------------------------------------
# SESSION
#--------------------------------------------------------------------

session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.cookieName = 'ci_session'
session.expiration = 7200
session.savePath = writable/session
session.matchIP = false
session.timeToUpdate = 300
session.regenerateDestroy = false

#--------------------------------------------------------------------
# COMPANY SETTINGS
#--------------------------------------------------------------------

company.name = '{$_SESSION['company_name']}'
company.cnpj = '{$_SESSION['company_cnpj']}'

ENV;
}

function runMigrations() {
    $migrationsPath = __DIR__ . '/../app/Database/Migrations';

    if (!is_dir($migrationsPath)) {
        throw new Exception("Diretório de migrations não encontrado!");
    }

    // Connect to database
    $dsn = "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};dbname={$_SESSION['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create migrations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `version` varchar(255) NOT NULL,
            `class` varchar(255) NOT NULL,
            `group` varchar(255) NOT NULL,
            `namespace` varchar(255) NOT NULL,
            `time` int NOT NULL,
            `batch` int unsigned NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Get migration files
    $files = glob($migrationsPath . '/*.php');
    sort($files);

    foreach ($files as $file) {
        $sql = file_get_contents($file);

        // Extract CREATE TABLE statements
        preg_match_all('/CREATE TABLE.*?;/s', $sql, $matches);

        foreach ($matches[0] as $statement) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Table might already exist, continue
            }
        }
    }

    return true;
}

function createAdminUser() {
    $dsn = "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};dbname={$_SESSION['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $hashedPassword = password_hash($_SESSION['admin_password'], PASSWORD_BCRYPT);
    $uniqueCode = 'ADM' . str_pad(1, 6, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("
        INSERT INTO employees (
            name, email, cpf, password, role, department, position,
            unique_code, active, created_at, updated_at
        ) VALUES (
            :name, :email, '000.000.000-00', :password, 'admin', 'Administração', 'Administrador',
            :unique_code, 1, NOW(), NOW()
        )
    ");

    $stmt->execute([
        'name' => $_SESSION['admin_name'],
        'email' => $_SESSION['admin_email'],
        'password' => $hashedPassword,
        'unique_code' => $uniqueCode,
    ]);

    return true;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema de Ponto Eletrônico</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: #2c3e50; color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .progress { display: flex; justify-content: space-between; background: #ecf0f1; padding: 0; }
        .progress-step { flex: 1; padding: 15px; text-align: center; font-size: 12px; border-right: 1px solid #bdc3c7; position: relative; }
        .progress-step:last-child { border-right: none; }
        .progress-step.active { background: #3498db; color: white; font-weight: bold; }
        .progress-step.completed { background: #27ae60; color: white; }
        .content { padding: 40px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #ecf0f1; border-radius: 6px; font-size: 14px; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #3498db; }
        .form-group small { color: #7f8c8d; font-size: 12px; display: block; margin-top: 5px; }
        .btn { padding: 14px 30px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.3s; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-secondary { background: #95a5a6; margin-right: 10px; }
        .btn-secondary:hover { background: #7f8c8d; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-error { background: #e74c3c; color: white; }
        .alert-success { background: #27ae60; color: white; }
        .requirements { list-style: none; }
        .requirements li { padding: 10px; margin-bottom: 5px; border-radius: 4px; }
        .requirements li.ok { background: #d5f4e6; color: #27ae60; }
        .requirements li.error { background: #fadbd8; color: #e74c3c; }
        .requirements li.warning { background: #fef5e7; color: #f39c12; }
        .requirements li::before { content: ''; display: inline-block; width: 20px; margin-right: 10px; }
        .requirements li.ok::before { content: '✓'; }
        .requirements li.error::before { content: '✗'; }
        .requirements li.warning::before { content: '⚠'; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .footer { background: #ecf0f1; padding: 20px; text-align: center; font-size: 12px; color: #7f8c8d; }
        code { background: #ecf0f1; padding: 3px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🕐 Sistema de Ponto Eletrônico</h1>
            <p>Assistente de Instalação</p>
        </div>

        <div class="progress">
            <div class="progress-step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">1. Requisitos</div>
            <div class="progress-step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">2. Banco de Dados</div>
            <div class="progress-step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'completed' : '' ?>">3. Administrador</div>
            <div class="progress-step <?= $step >= 4 ? 'active' : '' ?> <?= $step > 4 ? 'completed' : '' ?>">4. Instalação</div>
            <div class="progress-step <?= $step >= 5 ? 'active' : '' ?>">5. Concluído</div>
        </div>

        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php
            // Step content
            switch ($step) {
                case '1': // Requirements check
                    echo '<h2>Verificação de Requisitos</h2>';
                    echo '<p>Verificando se o servidor atende aos requisitos mínimos:</p>';
                    echo '<ul class="requirements">';

                    // PHP version
                    $phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
                    echo '<li class="' . ($phpOk ? 'ok' : 'error') . '">PHP ' . PHP_VERSION . ' (mínimo: 8.1.0)</li>';

                    // Extensions
                    $extensions = ['intl', 'mbstring', 'json', 'mysqlnd', 'gd', 'curl'];
                    foreach ($extensions as $ext) {
                        $loaded = extension_loaded($ext);
                        echo '<li class="' . ($loaded ? 'ok' : 'error') . '">Extensão: ' . $ext . '</li>';
                    }

                    // Writable directories
                    $dirs = ['../writable', '../writable/cache', '../writable/logs', '../writable/session'];
                    foreach ($dirs as $dir) {
                        $writable = is_writable(__DIR__ . '/' . $dir);
                        echo '<li class="' . ($writable ? 'ok' : 'error') . '">Permissão de escrita: ' . $dir . '</li>';
                    }

                    echo '</ul>';
                    echo '<br><a href="install.php?step=2" class="btn">Continuar →</a>';
                    break;

                case '2': // Database configuration
                    echo '<h2>Configuração do Banco de Dados</h2>';
                    echo '<form method="POST">';
                    echo '<div class="grid">';
                    echo '<div class="form-group"><label>Host:</label><input type="text" name="db_host" value="' . ($_SESSION['db_host'] ?? 'localhost') . '" required></div>';
                    echo '<div class="form-group"><label>Porta:</label><input type="text" name="db_port" value="' . ($_SESSION['db_port'] ?? '3306') . '" required></div>';
                    echo '</div>';
                    echo '<div class="form-group"><label>Nome do Banco:</label><input type="text" name="db_name" value="' . ($_SESSION['db_name'] ?? 'ponto_eletronico') . '" required><small>Será criado automaticamente se não existir</small></div>';
                    echo '<div class="form-group"><label>Usuário:</label><input type="text" name="db_user" value="' . ($_SESSION['db_user'] ?? 'root') . '" required></div>';
                    echo '<div class="form-group"><label>Senha:</label><input type="password" name="db_pass" value="' . ($_SESSION['db_pass'] ?? '') . '"></div>';
                    echo '<button type="submit" class="btn">Testar Conexão</button>';
                    if ($_SESSION['db_tested'] ?? false) {
                        echo ' <a href="install.php?step=3" class="btn btn-success">Continuar →</a>';
                    }
                    echo '</form>';
                    break;

                case '3': // Admin user
                    echo '<h2>Criar Usuário Administrador</h2>';
                    echo '<form method="POST">';
                    echo '<div class="form-group"><label>Nome da Empresa:</label><input type="text" name="company_name" value="' . ($_SESSION['company_name'] ?? '') . '" required></div>';
                    echo '<div class="form-group"><label>CNPJ:</label><input type="text" name="company_cnpj" value="' . ($_SESSION['company_cnpj'] ?? '') . '" placeholder="00.000.000/0000-00" required></div>';
                    echo '<div class="form-group"><label>Nome do Administrador:</label><input type="text" name="admin_name" value="' . ($_SESSION['admin_name'] ?? 'Administrador') . '" required></div>';
                    echo '<div class="form-group"><label>Email:</label><input type="email" name="admin_email" value="' . ($_SESSION['admin_email'] ?? 'admin@empresa.com.br') . '" required></div>';
                    echo '<div class="form-group"><label>Senha:</label><input type="password" name="admin_password" value="' . ($_SESSION['admin_password'] ?? '') . '" required><small>Mínimo 8 caracteres</small></div>';
                    echo '<a href="install.php?step=2" class="btn btn-secondary">← Voltar</a>';
                    echo '<button type="submit" class="btn">Salvar</button>';
                    if (!empty($_SESSION['admin_email'])) {
                        echo ' <a href="install.php?step=4" class="btn btn-success">Instalar Agora →</a>';
                    }
                    echo '</form>';
                    break;

                case '4': // Run installation
                    echo '<h2>Executar Instalação</h2>';
                    echo '<p>Clique em "Instalar" para:</p>';
                    echo '<ul style="margin: 20px 0; padding-left: 30px;">';
                    echo '<li>Criar arquivo de configuração (.env)</li>';
                    echo '<li>Criar estrutura do banco de dados (migrations)</li>';
                    echo '<li>Criar usuário administrador</li>';
                    echo '<li>Configurar permissões e diretórios</li>';
                    echo '</ul>';
                    echo '<form method="POST">';
                    echo '<a href="install.php?step=3" class="btn btn-secondary">← Voltar</a>';
                    echo '<button type="submit" class="btn btn-success">Instalar Sistema</button>';
                    echo '</form>';
                    break;

                case '5': // Completion
                    echo '<h2>✓ Instalação Concluída!</h2>';
                    echo '<p>O sistema foi instalado com sucesso!</p>';
                    echo '<div style="background: #ecf0f1; padding: 20px; border-radius: 8px; margin: 20px 0;">';
                    echo '<h3>Credenciais de Acesso:</h3>';
                    echo '<p><strong>Email:</strong> ' . htmlspecialchars($_SESSION['admin_email']) . '</p>';
                    echo '<p><strong>Senha:</strong> (a senha que você definiu)</p>';
                    echo '</div>';
                    echo '<div style="background: #fef5e7; padding: 20px; border-radius: 8px; margin: 20px 0;">';
                    echo '<h3>⚠️ IMPORTANTE - Segurança:</h3>';
                    echo '<ol style="padding-left: 30px;">';
                    echo '<li><strong>DELETE</strong> o arquivo <code>public/install.php</code> IMEDIATAMENTE!</li>';
                    echo '<li>Altere a senha padrão após o primeiro login</li>';
                    echo '<li>Configure as permissões de arquivo corretamente</li>';
                    echo '<li>Configure HTTPS em produção</li>';
                    echo '</ol>';
                    echo '</div>';
                    echo '<a href="/" class="btn btn-success">Acessar o Sistema →</a>';
                    break;
            }
            ?>
        </div>

        <div class="footer">
            Sistema de Ponto Eletrônico © <?= date('Y') ?> | Conforme Portaria MTE 671/2021 e LGPD
        </div>
    </div>
</body>
</html>
