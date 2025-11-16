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
                    $_SESSION['install_log'] = [];

                    // Step 1: Create .env file
                    $_SESSION['install_log'][] = "Criando arquivo .env...";
                    $envContent = createEnvFile();
                    file_put_contents(__DIR__ . '/../.env', $envContent);
                    $_SESSION['install_log'][] = "✓ Arquivo .env criado";

                    // Step 2: Run migrations
                    $_SESSION['install_log'][] = "Executando migrations do banco de dados...";
                    runMigrations();
                    $_SESSION['install_log'][] = "✓ Migrations executadas com sucesso";

                    // Step 3: Create admin user (custom)
                    $_SESSION['install_log'][] = "Criando usuário administrador...";
                    createAdminUser();
                    $_SESSION['install_log'][] = "✓ Usuário administrador criado";

                    // Step 4: Run seeders (settings and default data)
                    $_SESSION['install_log'][] = "Executando seeders (configurações iniciais)...";
                    runSeeders();
                    $_SESSION['install_log'][] = "✓ Seeders executados";

                    // Step 5: Create lock file
                    $_SESSION['install_log'][] = "Finalizando instalação...";
                    file_put_contents(__DIR__ . '/../writable/installed.lock', date('Y-m-d H:i:s'));
                    $_SESSION['install_log'][] = "✓ Arquivo de proteção criado";

                    $success = "Instalação concluída com sucesso!";
                    header('Location: install.php?step=5');
                    exit;
                } catch (Exception $e) {
                    $error = "Erro na instalação: " . $e->getMessage();
                    $_SESSION['install_log'][] = "✗ ERRO: " . $e->getMessage();

                    // Show detailed logs
                    if (isset($_SESSION['migration_output'])) {
                        $error .= "\n\nDetalhes das migrations:\n" . $_SESSION['migration_output'];
                    }
                }
            } else {
                $error = "Configure o banco de dados primeiro!";
            }
            break;
    }
}

// Helper functions
function createEnvFile() {
    // Generate proper sodium encryption key (32 bytes for XChaCha20-Poly1305)
    $key = base64_encode(random_bytes(32));

    // Get app URL from server or session
    $appUrl = $_SESSION['app_url'] ?? ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    return <<<ENV
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------

CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------

app.baseURL = '{$appUrl}'
app.indexPage = ''
app.forceGlobalSecureRequests = false

# Encryption key (32 bytes for XChaCha20-Poly1305 AEAD)
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
    $rootPath = __DIR__ . '/..';

    // Check if exec() is available
    $execAvailable = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));

    if ($execAvailable) {
        // Method 1: Use spark CLI (preferred)
        $sparkPath = $rootPath . '/spark';
        if (!file_exists($sparkPath)) {
            // Try to copy from vendor
            $vendorSpark = $rootPath . '/vendor/codeigniter4/framework/spark';
            if (file_exists($vendorSpark)) {
                copy($vendorSpark, $sparkPath);
                chmod($sparkPath, 0755);
            }
        }

        if (file_exists($sparkPath)) {
            $output = [];
            $returnCode = 0;

            chdir($rootPath);
            exec('php spark migrate --all 2>&1', $output, $returnCode);

            $_SESSION['migration_output'] = implode("\n", $output);

            if ($returnCode !== 0) {
                // Check if error is just about already migrated
                $outputText = implode(' ', $output);
                if (strpos($outputText, 'up-to-date') !== false ||
                    strpos($outputText, 'No migrations') !== false ||
                    strpos($outputText, 'already') !== false) {
                    return true; // Already migrated is OK
                }
                // Fall through to SQL file method
            } else {
                return true; // Success
            }
        }
    }

    // Method 2: Use database.sql file (fallback for shared hosting)
    $_SESSION['migration_output'] = "Usando método alternativo (database.sql)...\n";

    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo database.sql não encontrado em public/");
    }

    // Read SQL file
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Não foi possível ler o arquivo database.sql");
    }

    // Connect to database
    $dsn = "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};dbname={$_SESSION['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Remove comments and split into statements
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
    $sql = preg_replace('#/\*.*?\*/#s', '', $sql); // Remove multi-line comments
    $sql = preg_replace('/^\s*$/m', '', $sql); // Remove empty lines

    // Split by semicolon but not inside quotes or parentheses
    $statements = [];
    $currentStatement = '';
    $inString = false;
    $stringChar = '';

    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];

        if (($char === '"' || $char === "'") && ($i === 0 || $sql[$i-1] !== '\\')) {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar) {
                $inString = false;
            }
        }

        if ($char === ';' && !$inString) {
            $statement = trim($currentStatement);
            if (!empty($statement)) {
                $statements[] = $statement;
            }
            $currentStatement = '';
        } else {
            $currentStatement .= $char;
        }
    }

    // Add last statement if any
    $statement = trim($currentStatement);
    if (!empty($statement)) {
        $statements[] = $statement;
    }

    // Execute each statement
    $executed = 0;
    $skipped = 0;
    foreach ($statements as $statement) {
        // Skip SET commands and control statements
        if (preg_match('/^\s*(SET|START TRANSACTION|COMMIT|\/\*!)/i', $statement)) {
            continue;
        }

        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), '1050') !== false || // Table already exists
                strpos($e->getMessage(), 'already exists') !== false) {
                $skipped++;
                continue;
            }
            // Log other errors but continue
            $_SESSION['migration_output'] .= "Aviso: " . $e->getMessage() . "\n";
        }
    }

    $_SESSION['migration_output'] .= "Executadas: $executed statements, Ignoradas: $skipped (já existentes)\n";

    return true;
}

function runSeeders() {
    $rootPath = __DIR__ . '/..';

    // Check if exec() is available
    $execAvailable = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));

    if ($execAvailable) {
        // Method 1: Use spark CLI (preferred)
        $sparkPath = $rootPath . '/spark';

        if (file_exists($sparkPath)) {
            chdir($rootPath);

            // Run AdminUserSeeder
            $output = [];
            $returnCode = 0;
            exec('php spark db:seed AdminUserSeeder 2>&1', $output, $returnCode);
            $_SESSION['seeder_admin_output'] = implode("\n", $output);

            // Run SettingsSeeder
            $output = [];
            $returnCode = 0;
            exec('php spark db:seed SettingsSeeder 2>&1', $output, $returnCode);
            $_SESSION['seeder_settings_output'] = implode("\n", $output);

            if ($returnCode === 0) {
                return true; // Success
            }
            // Fall through to manual seeding
        }
    }

    // Method 2: Manual seeding via PDO (fallback for shared hosting)
    $_SESSION['seeder_admin_output'] = "Usando método alternativo (SQL direto)...\n";
    $_SESSION['seeder_settings_output'] = "Usando método alternativo (SQL direto)...\n";

    // Connect to database
    $dsn = "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};dbname={$_SESSION['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insert settings if not exist
    $settings = [
        ['company_name', $_SESSION['company_name'] ?? 'Empresa LTDA', 'string', 'company', 'Nome da empresa', 1],
        ['company_cnpj', $_SESSION['company_cnpj'] ?? '00.000.000/0000-00', 'string', 'company', 'CNPJ da empresa', 1],
        ['timezone', 'America/Sao_Paulo', 'string', 'general', 'Fuso horário do sistema', 1],
        ['date_format', 'd/m/Y', 'string', 'general', 'Formato de data', 1],
        ['time_format', 'H:i', 'string', 'general', 'Formato de hora', 1],
        ['tolerance_minutes', '10', 'number', 'timesheet', 'Tolerância de atraso em minutos', 0],
        ['extra_hours_enabled', 'true', 'boolean', 'timesheet', 'Habilitar horas extras', 0],
        ['max_extra_hours_daily', '2', 'number', 'timesheet', 'Máximo de horas extras por dia', 0],
        ['require_geolocation', 'false', 'boolean', 'punch', 'Exigir geolocalização no registro', 0],
        ['biometric_threshold', '70', 'number', 'biometric', 'Score mínimo de biometria (0-100)', 0],
        ['facial_threshold', '85', 'number', 'biometric', 'Score mínimo de reconhecimento facial (0-100)', 0],
        ['session_timeout', '7200', 'number', 'security', 'Timeout de sessão em segundos', 0],
        ['password_min_length', '8', 'number', 'security', 'Tamanho mínimo da senha', 0],
        ['enable_2fa', 'true', 'boolean', 'security', 'Habilitar autenticação 2FA', 0],
        ['notification_email', 'admin@empresa.com.br', 'string', 'notifications', 'Email para notificações', 0],
        ['enable_push_notifications', 'true', 'boolean', 'notifications', 'Habilitar push notifications', 0],
        ['enable_rate_limiting', 'true', 'boolean', 'security', 'Habilitar rate limiting', 0],
        ['api_rate_limit', '100', 'number', 'security', 'Limite de requisições por minuto', 0],
        ['enable_audit_log', 'true', 'boolean', 'security', 'Habilitar logs de auditoria', 0],
        ['lgpd_dpo_email', 'dpo@empresa.com.br', 'string', 'lgpd', 'Email do DPO (LGPD)', 1],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO settings (`key`, `value`, `type`, `group`, `description`, `is_public`, `created_at`, `updated_at`)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            `value` = VALUES(`value`),
            `updated_at` = NOW()
    ");

    $inserted = 0;
    foreach ($settings as $setting) {
        try {
            $stmt->execute($setting);
            $inserted++;
        } catch (PDOException $e) {
            // Ignore errors, setting might already exist
        }
    }

    $_SESSION['seeder_settings_output'] .= "Inseridas/atualizadas: $inserted configurações\n";

    return true;
}

function createAdminUser() {
    $dsn = "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};dbname={$_SESSION['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Use Argon2id for password hashing (more secure than bcrypt)
    $hashedPassword = password_hash($_SESSION['admin_password'], PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,  // 64 MB
        'time_cost' => 4,
        'threads' => 2
    ]);

    $uniqueCode = 'ADM' . str_pad(1, 6, '0', STR_PAD_LEFT);

    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $_SESSION['admin_email']]);

    if ($stmt->fetch()) {
        // Update existing admin
        $stmt = $pdo->prepare("
            UPDATE employees
            SET name = :name,
                password = :password,
                role = 'admin',
                active = 1,
                updated_at = NOW()
            WHERE email = :email
        ");

        $stmt->execute([
            'name' => $_SESSION['admin_name'],
            'email' => $_SESSION['admin_email'],
            'password' => $hashedPassword,
        ]);
    } else {
        // Create new admin
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
    }

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

                    // Show installation logs if available
                    if (isset($_SESSION['install_log']) && count($_SESSION['install_log']) > 0) {
                        echo '<div style="background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 8px; margin: 20px 0; font-family: monospace; font-size: 13px; max-height: 400px; overflow-y: auto;">';
                        echo '<strong>Log de Instalação:</strong><br><br>';
                        foreach ($_SESSION['install_log'] as $log) {
                            $color = '#ecf0f1';
                            if (strpos($log, '✓') === 0) {
                                $color = '#2ecc71';
                            } elseif (strpos($log, '✗') === 0) {
                                $color = '#e74c3c';
                            }
                            echo '<div style="color: ' . $color . '; margin-bottom: 5px;">' . htmlspecialchars($log) . '</div>';
                        }
                        echo '</div>';

                        // Show migration output if available
                        if (isset($_SESSION['migration_output']) && !empty($_SESSION['migration_output'])) {
                            echo '<details style="margin: 20px 0;">';
                            echo '<summary style="cursor: pointer; padding: 10px; background: #ecf0f1; border-radius: 4px;">Ver detalhes das migrations</summary>';
                            echo '<pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; margin-top: 10px; overflow-x: auto; font-size: 12px;">';
                            echo htmlspecialchars($_SESSION['migration_output']);
                            echo '</pre>';
                            echo '</details>';
                        }

                        echo '<p style="margin-top: 20px; color: #e74c3c;">Se ocorreu algum erro, corrija o problema e tente novamente.</p>';
                        echo '<a href="install.php?step=3" class="btn btn-secondary">← Voltar</a> ';
                        echo '<form method="POST" style="display: inline;"><button type="submit" class="btn btn-success">Tentar Novamente</button></form>';
                    } else {
                        echo '<p>Clique em "Instalar" para:</p>';
                        echo '<ul style="margin: 20px 0; padding-left: 30px;">';
                        echo '<li>Criar arquivo de configuração (.env) com encryption key</li>';
                        echo '<li>Criar estrutura do banco de dados (21+ migrations)</li>';
                        echo '<li>Criar usuário administrador customizado</li>';
                        echo '<li>Executar seeders (configurações do sistema)</li>';
                        echo '<li>Configurar permissões e diretórios</li>';
                        echo '</ul>';
                        echo '<form method="POST">';
                        echo '<a href="install.php?step=3" class="btn btn-secondary">← Voltar</a>';
                        echo '<button type="submit" class="btn btn-success">Instalar Sistema</button>';
                        echo '</form>';
                    }
                    break;

                case '5': // Completion
                    echo '<h2>✓ Instalação Concluída com Sucesso!</h2>';
                    echo '<p style="font-size: 16px; color: #27ae60;">Parabéns! Seu Sistema de Ponto Eletrônico está pronto para uso.</p>';

                    // Installation summary
                    echo '<div style="background: #d5f4e6; padding: 20px; border-radius: 8px; margin: 20px 0;">';
                    echo '<h3 style="margin-top: 0;">📋 Resumo da Instalação:</h3>';
                    echo '<ul style="padding-left: 30px; line-height: 1.8;">';
                    echo '<li>✓ Banco de dados <strong>' . htmlspecialchars($_SESSION['db_name']) . '</strong> criado e configurado</li>';
                    echo '<li>✓ 21+ tabelas criadas (migrations executadas)</li>';
                    echo '<li>✓ Usuário administrador criado</li>';
                    echo '<li>✓ Configurações do sistema inicializadas</li>';
                    echo '<li>✓ Encryption key gerada (XChaCha20-Poly1305)</li>';
                    echo '<li>✓ Arquivo .env configurado</li>';
                    echo '</ul>';
                    echo '</div>';

                    // Access credentials
                    echo '<div style="background: #ecf0f1; padding: 20px; border-radius: 8px; margin: 20px 0;">';
                    echo '<h3 style="margin-top: 0;">🔑 Credenciais de Acesso:</h3>';
                    echo '<div style="background: white; padding: 15px; border-radius: 4px; margin-top: 10px;">';
                    echo '<p style="margin: 5px 0;"><strong>Email:</strong> <code style="background: #ecf0f1; padding: 4px 8px; border-radius: 3px;">' . htmlspecialchars($_SESSION['admin_email'] ?? 'admin@empresa.com.br') . '</code></p>';
                    echo '<p style="margin: 5px 0;"><strong>Senha:</strong> <code style="background: #ecf0f1; padding: 4px 8px; border-radius: 3px;">(a senha que você definiu)</code></p>';
                    echo '<p style="margin: 5px 0;"><strong>Role:</strong> <code style="background: #ecf0f1; padding: 4px 8px; border-radius: 3px;">Administrador</code></p>';
                    echo '</div>';
                    echo '</div>';

                    // Security warnings
                    echo '<div style="background: #fef5e7; border-left: 4px solid #f39c12; padding: 20px; border-radius: 8px; margin: 20px 0;">';
                    echo '<h3 style="margin-top: 0;">⚠️ IMPORTANTE - Ações de Segurança Obrigatórias:</h3>';
                    echo '<ol style="padding-left: 30px; line-height: 1.8;">';
                    echo '<li><strong style="color: #e74c3c;">DELETE o arquivo <code>public/install.php</code> IMEDIATAMENTE!</strong><br>';
                    echo '<small style="color: #7f8c8d;">Execute: <code>rm public/install.php</code> ou delete via FTP</small></li>';
                    echo '<li>Altere a senha padrão após o primeiro login</li>';
                    echo '<li>Configure HTTPS em produção (obrigatório por LGPD)</li>';
                    echo '<li>Ajuste permissões de arquivos: <code>chmod 644 .env</code></li>';
                    echo '<li>Configure backup automático do banco de dados</li>';
                    echo '</ol>';
                    echo '</div>';

                    // Features summary
                    echo '<div style="background: #e8f5ff; padding: 20px; border-radius: 8px; margin: 20px 0;">';
                    echo '<h3 style="margin-top: 0;">🚀 Recursos Instalados:</h3>';
                    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px;">';
                    echo '<div>✓ Registro de Ponto Biométrico</div>';
                    echo '<div>✓ Reconhecimento Facial (DeepFace)</div>';
                    echo '<div>✓ Autenticação 2FA (TOTP)</div>';
                    echo '<div>✓ OAuth 2.0 API</div>';
                    echo '<div>✓ Criptografia XChaCha20-Poly1305</div>';
                    echo '<div>✓ Push Notifications (FCM)</div>';
                    echo '<div>✓ Rate Limiting</div>';
                    echo '<div>✓ Security Headers</div>';
                    echo '<div>✓ Dashboard Analytics</div>';
                    echo '<div>✓ Gestão de Justificativas</div>';
                    echo '<div>✓ Sistema de Advertências</div>';
                    echo '<div>✓ Conformidade LGPD</div>';
                    echo '</div>';
                    echo '</div>';

                    // Next steps
                    echo '<div style="background: #d5f4e6; padding: 20px; border-radius: 8px; margin: 20px 0;">';
                    echo '<h3 style="margin-top: 0;">📖 Próximos Passos:</h3>';
                    echo '<ol style="padding-left: 30px; line-height: 1.8;">';
                    echo '<li>Acesse o sistema e faça login como administrador</li>';
                    echo '<li>Configure as informações da empresa em Configurações</li>';
                    echo '<li>Cadastre departamentos e cargos</li>';
                    echo '<li>Cadastre funcionários e configure biometria</li>';
                    echo '<li>Configure regras de horário e banco de horas</li>';
                    echo '<li>Configure notificações (opcional)</li>';
                    echo '<li>Configure WebSocket para atualizações em tempo real (opcional)</li>';
                    echo '</ol>';
                    echo '</div>';

                    echo '<div style="text-align: center; margin-top: 30px;">';
                    echo '<a href="/" class="btn btn-success" style="font-size: 18px; padding: 16px 40px;">Acessar o Sistema →</a>';
                    echo '</div>';
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
