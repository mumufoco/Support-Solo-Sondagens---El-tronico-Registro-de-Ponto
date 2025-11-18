<?php
/**
 * Instalador Web Standalone - Sistema de Ponto Eletrônico
 *
 * Este instalador roda INDEPENDENTE do CodeIgniter
 * Usa PDO puro e cria tudo do zero
 *
 * @version 3.0.0
 * @author Support Solo Sondagens
 */

// Iniciar sessão
session_start();

// Configurações
define('INSTALL_VERSION', '3.0.0');
define('LOCK_FILE', __DIR__ . '/../writable/installed.lock');
define('ENV_FILE', __DIR__ . '/../.env');

// Verificar se já está instalado (exceto se for force-reinstall)
if (file_exists(LOCK_FILE) && !isset($_GET['force_reinstall'])) {
    header('Location: /');
    exit;
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'test_connection':
            echo json_encode(testConnection($_POST));
            exit;

        case 'run_installation':
            echo json_encode(runInstallation($_POST));
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            exit;
    }
}

/**
 * Testar conexão com MySQL
 */
function testConnection($data) {
    $result = [
        'success' => false,
        'message' => '',
        'logs' => []
    ];

    try {
        $host = trim($data['db_host'] ?? '');
        $port = trim($data['db_port'] ?? '3306');
        $database = trim($data['db_database'] ?? '');
        $username = trim($data['db_username'] ?? '');
        $password = $data['db_password'] ?? '';

        // Validar campos
        if (empty($host) || empty($database) || empty($username)) {
            throw new Exception('Preencha todos os campos obrigatórios (Host, Database, Username)');
        }

        // IMPORTANTE: Sempre retornar db_config no JSON (mesmo se falhar)
        // Frontend salva no localStorage para enviar depois
        $result['db_config'] = [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password
        ];

        $result['logs'][] = "🔍 Testando conexão: {$username}@{$host}:{$port}";

        // Tentar conectar ao servidor MySQL (sem database)
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);

        $result['logs'][] = "✅ Conexão com MySQL estabelecida!";

        // Verificar versão
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        $result['logs'][] = "📌 Versão do MySQL: {$version}";

        // Verificar se database existe
        $stmt = $pdo->query("SHOW DATABASES LIKE '{$database}'");
        $dbExists = $stmt->rowCount() > 0;

        if (!$dbExists) {
            $result['logs'][] = "⚠️  Database '{$database}' não existe";
            $result['logs'][] = "🔧 Tentando criar database...";

            $pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $result['logs'][] = "✅ Database '{$database}' criado com sucesso!";
        } else {
            $result['logs'][] = "✅ Database '{$database}' já existe";
        }

        // Conectar ao database específico
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Verificar tabelas existentes
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $tableCount = count($tables);

        if ($tableCount > 0) {
            $result['logs'][] = "";
            $result['logs'][] = "⚠️  ATENÇÃO: Database contém {$tableCount} tabela(s)";
            $result['logs'][] = "📋 Tabelas: " . implode(', ', array_slice($tables, 0, 5)) . ($tableCount > 5 ? '...' : '');
            $result['logs'][] = "⚠️  Todas as tabelas serão REMOVIDAS durante instalação!";
            $result['logs'][] = "";
            $result['has_tables'] = true;
            $result['table_count'] = $tableCount;
            $result['existing_tables'] = $tables; // Retornar no JSON
        } else {
            $result['logs'][] = "✅ Database vazio (pronto para instalação)";
            $result['has_tables'] = false;
            $result['existing_tables'] = [];
        }

        // Testar permissões
        $testTable = '_test_' . time();
        $pdo->exec("CREATE TABLE `{$testTable}` (id INT)");
        $pdo->exec("DROP TABLE `{$testTable}`");
        $result['logs'][] = "✅ Permissões CREATE/DROP validadas";

        $result['success'] = true;
        $result['message'] = '✅ Conexão testada com sucesso!';

    } catch (PDOException $e) {
        $result['message'] = '❌ Erro de conexão: ' . $e->getMessage();
        $result['logs'][] = "❌ " . $e->getMessage();

        // Dicas baseadas no erro
        if ($e->getCode() == 1045) {
            $result['logs'][] = "💡 Dica: Verifique usuário e senha do MySQL";
        } elseif ($e->getCode() == 2002) {
            $result['logs'][] = "💡 Dica: MySQL está rodando? (systemctl status mysql)";
        } elseif ($e->getCode() == 1044) {
            $result['logs'][] = "💡 Dica: Usuário precisa de permissão CREATE DATABASE";
        }

        // Mesmo com erro, retornar arrays vazios
        $result['existing_tables'] = [];
        $result['has_tables'] = false;
    } catch (Exception $e) {
        $result['message'] = '❌ Erro: ' . $e->getMessage();
        $result['logs'][] = "❌ " . $e->getMessage();

        // Mesmo com erro, retornar arrays vazios
        $result['existing_tables'] = [];
        $result['has_tables'] = false;
    }

    return $result;
}

/**
 * Executar instalação completa
 */
function runInstallation($data) {
    $result = [
        'success' => false,
        'message' => '',
        'logs' => []
    ];

    try {
        // IMPORTANTE: Receber dados do MySQL via POST (não usar sessão)
        $dbHost = trim($data['db_host'] ?? '');
        $dbPort = trim($data['db_port'] ?? '3306');
        $dbDatabase = trim($data['db_database'] ?? '');
        $dbUsername = trim($data['db_username'] ?? '');
        $dbPassword = $data['db_password'] ?? '';
        $existingTables = isset($data['existing_tables']) ? json_decode($data['existing_tables'], true) : [];

        // Validar dados do MySQL
        if (empty($dbHost) || empty($dbDatabase) || empty($dbUsername)) {
            throw new Exception('Dados do MySQL não fornecidos. Volte e teste a conexão primeiro.');
        }

        $config = [
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbDatabase,
            'username' => $dbUsername,
            'password' => $dbPassword
        ];

        $adminName = trim($data['admin_name'] ?? 'Administrador');
        $adminEmail = trim($data['admin_email'] ?? '');
        $adminPassword = $data['admin_password'] ?? '';

        // Validar dados do admin
        if (empty($adminEmail) || empty($adminPassword)) {
            throw new Exception('Preencha email e senha do administrador');
        }

        if (strlen($adminPassword) < 8) {
            throw new Exception('Senha deve ter no mínimo 8 caracteres');
        }

        $result['logs'][] = "🚀 Iniciando instalação...";
        $result['logs'][] = "";

        // Conectar ao banco
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $result['logs'][] = "✅ Conectado ao database: {$config['database']}";

        // PASSO 1: Limpar banco se necessário
        if (!empty($existingTables) && is_array($existingTables) && count($existingTables) > 0) {
            $result['logs'][] = "";
            $result['logs'][] = "🗑️  Removendo tabelas existentes...";

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            foreach ($existingTables as $table) {
                try {
                    $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                    $result['logs'][] = "  ✓ Removida: {$table}";
                } catch (Exception $e) {
                    $result['logs'][] = "  ⚠️  Erro ao remover {$table}: " . $e->getMessage();
                }
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            $result['logs'][] = "✅ Database limpo!";
            $result['logs'][] = "";
        }

        // PASSO 2: Criar tabelas
        $result['logs'][] = "📦 Criando estrutura do database...";

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        // Criar tabela employees
        $result['logs'][] = "  → Criando tabela: employees";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `employees` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `cpf` VARCHAR(14) NULL,
                `role` ENUM('admin', 'gestor', 'funcionario') DEFAULT 'funcionario',
                `admission_date` DATE NULL,
                `status` ENUM('active', 'inactive') DEFAULT 'active',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_email` (`email`),
                INDEX `idx_role` (`role`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Criar tabela timesheets
        $result['logs'][] = "  → Criando tabela: timesheets";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `timesheets` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `employee_id` INT UNSIGNED NOT NULL,
                `punch_time` DATETIME NOT NULL,
                `punch_type` ENUM('entrada', 'saida', 'intervalo_inicio', 'intervalo_fim') NOT NULL,
                `latitude` DECIMAL(10, 8) NULL,
                `longitude` DECIMAL(11, 8) NULL,
                `ip_address` VARCHAR(45) NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
                INDEX `idx_employee` (`employee_id`),
                INDEX `idx_punch_time` (`punch_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Criar tabela remember_tokens
        $result['logs'][] = "  → Criando tabela: remember_tokens";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `remember_tokens` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `employee_id` INT UNSIGNED NOT NULL,
                `selector` VARCHAR(64) NOT NULL UNIQUE,
                `token_hash` VARCHAR(255) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `ip_address` VARCHAR(45) NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
                INDEX `idx_selector` (`selector`),
                INDEX `idx_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Criar tabela audit_logs
        $result['logs'][] = "  → Criando tabela: audit_logs";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `audit_logs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `employee_id` INT UNSIGNED NULL,
                `action` VARCHAR(255) NOT NULL,
                `entity` VARCHAR(100) NULL,
                `entity_id` INT NULL,
                `old_values` TEXT NULL,
                `new_values` TEXT NULL,
                `ip_address` VARCHAR(45) NULL,
                `user_agent` VARCHAR(255) NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL,
                INDEX `idx_employee` (`employee_id`),
                INDEX `idx_action` (`action`),
                INDEX `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Criar tabela leave_requests
        $result['logs'][] = "  → Criando tabela: leave_requests";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `leave_requests` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `employee_id` INT UNSIGNED NOT NULL,
                `type` ENUM('ferias', 'atestado', 'licenca') NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `reason` TEXT NULL,
                `status` ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente',
                `approved_by` INT UNSIGNED NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`approved_by`) REFERENCES `employees`(`id`) ON DELETE SET NULL,
                INDEX `idx_employee` (`employee_id`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Criar tabela biometric_templates
        $result['logs'][] = "  → Criando tabela: biometric_templates";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `biometric_templates` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `employee_id` INT UNSIGNED NOT NULL,
                `type` ENUM('fingerprint', 'face') NOT NULL,
                `template_data` TEXT NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
                INDEX `idx_employee` (`employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $result['logs'][] = "✅ 6 tabelas criadas com sucesso!";
        $result['logs'][] = "";

        // PASSO 3: Criar usuário administrador
        $result['logs'][] = "👤 Criando usuário administrador...";

        $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $pdo->prepare("
            INSERT INTO employees (name, email, password, role, cpf, admission_date, status)
            VALUES (:name, :email, :password, 'admin', '00000000000', CURDATE(), 'active')
        ");

        $stmt->execute([
            ':name' => $adminName,
            ':email' => $adminEmail,
            ':password' => $passwordHash
        ]);

        $result['logs'][] = "✅ Administrador criado!";
        $result['logs'][] = "   Nome: {$adminName}";
        $result['logs'][] = "   Email: {$adminEmail}";
        $result['logs'][] = "";

        // PASSO 4: Criar arquivo .env
        $result['logs'][] = "📝 Criando arquivo .env...";

        $encryptionKey = 'base64:' . base64_encode(random_bytes(32));

        $envContent = generateEnvFile($config, $encryptionKey);

        if (file_put_contents(ENV_FILE, $envContent) === false) {
            throw new Exception('Não foi possível criar arquivo .env');
        }

        $result['logs'][] = "✅ Arquivo .env criado!";
        $result['logs'][] = "   Encryption key: " . substr($encryptionKey, 0, 20) . "...";
        $result['logs'][] = "";

        // PASSO 5: Criar lock file
        $result['logs'][] = "🔒 Criando lock file...";

        $lockData = [
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => INSTALL_VERSION,
            'database' => $config['database']
        ];

        @mkdir(dirname(LOCK_FILE), 0755, true);
        file_put_contents(LOCK_FILE, json_encode($lockData, JSON_PRETTY_PRINT));

        $result['logs'][] = "✅ Sistema marcado como instalado!";
        $result['logs'][] = "";

        // Limpar sessão
        unset($_SESSION['db_config']);
        unset($_SESSION['existing_tables']);

        $result['logs'][] = "🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO!";
        $result['logs'][] = "";
        $result['logs'][] = "Você já pode fazer login no sistema.";

        $result['success'] = true;
        $result['message'] = '✅ Instalação concluída!';
        $result['admin_email'] = $adminEmail;

    } catch (PDOException $e) {
        $result['message'] = '❌ Erro no banco de dados: ' . $e->getMessage();
        $result['logs'][] = "❌ ERRO: " . $e->getMessage();
        $result['logs'][] = "Código: " . $e->getCode();
    } catch (Exception $e) {
        $result['message'] = '❌ Erro: ' . $e->getMessage();
        $result['logs'][] = "❌ ERRO: " . $e->getMessage();
    }

    return $result;
}

/**
 * Gerar conteúdo do arquivo .env
 */
function generateEnvFile($config, $encryptionKey) {
    return <<<ENV
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
app.baseURL = 'http://localhost/'
app.forceGlobalSecureRequests = false
app.CSPEnabled = true

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = {$config['host']}
database.default.database = {$config['database']}
database.default.username = {$config['username']}
database.default.password = {$config['password']}
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = {$config['port']}

#--------------------------------------------------------------------
# ENCRYPTION
#--------------------------------------------------------------------
encryption.key = {$encryptionKey}

#--------------------------------------------------------------------
# SESSION
#--------------------------------------------------------------------
session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.cookieName = 'ci_session'
session.expiration = 7200
session.savePath = writable/session
session.matchIP = true
session.timeToUpdate = 300
session.regenerateDestroy = true

#--------------------------------------------------------------------
# SECURITY
#--------------------------------------------------------------------
security.csrfProtection = 'session'
security.tokenRandomize = true
security.tokenName = 'csrf_token_name'
security.headerName = 'X-CSRF-TOKEN'
security.cookieName = 'csrf_cookie_name'
security.expires = 7200
security.regenerate = true

#--------------------------------------------------------------------
# COOKIE
#--------------------------------------------------------------------
cookie.prefix = ''
cookie.expires = 0
cookie.path = '/'
cookie.domain = ''
cookie.secure = false
cookie.httponly = true
cookie.samesite = 'Lax'
ENV;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - Sistema de Ponto Eletrônico v<?= INSTALL_VERSION ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .content {
            padding: 40px;
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .console {
            background: #1e1e1e;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            padding: 20px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            margin: 20px 0;
            line-height: 1.8;
        }

        .console div {
            margin-bottom: 4px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-color: #f59e0b;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            margin-top: 16px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }

        .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 6px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .success-box {
            background: #d1fae5;
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
        }

        .success-box h2 {
            color: #065f46;
            font-size: 28px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⏰ Sistema de Ponto Eletrônico</h1>
            <p>Instalador Web v<?= INSTALL_VERSION ?> - Standalone</p>
        </div>

        <div class="content">
            <!-- STEP 1: Configuração do Banco -->
            <div id="step-database" class="step active">
                <h2 style="margin-bottom: 24px; color: #333;">🗄️ Configuração do MySQL</h2>

                <div class="alert alert-warning">
                    <strong>⚠️ Importante:</strong> Teste a conexão antes de prosseguir!
                </div>

                <div class="form-group">
                    <label for="db_host">Host do MySQL *</label>
                    <input type="text" id="db_host" value="localhost" required>
                    <div class="help-text">Geralmente "localhost" ou "127.0.0.1"</div>
                </div>

                <div class="form-group">
                    <label for="db_port">Porta</label>
                    <input type="number" id="db_port" value="3306">
                </div>

                <div class="form-group">
                    <label for="db_database">Nome do Database *</label>
                    <input type="text" id="db_database" value="ponto_eletronico" required>
                </div>

                <div class="form-group">
                    <label for="db_username">Usuário *</label>
                    <input type="text" id="db_username" value="root" required>
                </div>

                <div class="form-group">
                    <label for="db_password">Senha</label>
                    <input type="password" id="db_password" value="">
                </div>

                <div style="text-align: center; padding: 20px; background: #f0fdf4; border-radius: 8px; margin: 20px 0;">
                    <button id="btn-test" class="btn btn-success" style="padding: 16px 40px; font-size: 18px;">
                        🔍 Testar Conexão
                    </button>
                </div>

                <div id="console-test" class="console" style="display: none;"></div>

                <div class="loading" id="loading-test">
                    <div class="spinner"></div>
                    <p>Testando conexão...</p>
                </div>

                <div class="button-group">
                    <button id="btn-next-admin" class="btn btn-primary" disabled>
                        Próximo: Configurar Admin →
                    </button>
                </div>
            </div>

            <!-- STEP 2: Configuração do Admin -->
            <div id="step-admin" class="step">
                <h2 style="margin-bottom: 24px; color: #333;">👤 Usuário Administrador</h2>

                <div class="form-group">
                    <label for="admin_name">Nome Completo *</label>
                    <input type="text" id="admin_name" value="Administrador" required>
                </div>

                <div class="form-group">
                    <label for="admin_email">E-mail *</label>
                    <input type="email" id="admin_email" value="admin@exemplo.com" required>
                    <div class="help-text">Será usado para fazer login</div>
                </div>

                <div class="form-group">
                    <label for="admin_password">Senha *</label>
                    <input type="password" id="admin_password" required minlength="8">
                    <div class="help-text">Mínimo 8 caracteres</div>
                </div>

                <div class="form-group">
                    <label for="admin_password_confirm">Confirmar Senha *</label>
                    <input type="password" id="admin_password_confirm" required minlength="8">
                </div>

                <div id="console-install" class="console" style="display: none;"></div>

                <div class="loading" id="loading-install">
                    <div class="spinner"></div>
                    <p>Instalando sistema...</p>
                </div>

                <div class="button-group">
                    <button id="btn-back" class="btn" style="background: #6c757d; color: white;">
                        ← Voltar
                    </button>
                    <button id="btn-install" class="btn btn-primary">
                        🚀 Instalar Sistema
                    </button>
                </div>
            </div>

            <!-- STEP 3: Conclusão -->
            <div id="step-finish" class="step">
                <div class="success-box">
                    <div style="font-size: 64px; margin-bottom: 20px;">🎉</div>
                    <h2>Instalação Concluída!</h2>
                    <p style="color: #065f46; font-size: 16px; margin-top: 10px;">
                        O sistema foi instalado com sucesso!
                    </p>
                </div>

                <div id="success-info" style="background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin-bottom: 15px;">Credenciais de Acesso:</h3>
                    <p><strong>E-mail:</strong> <span id="final-email"></span></p>
                    <p><strong>Senha:</strong> (a que você definiu)</p>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="/" class="btn btn-success" style="padding: 16px 40px; font-size: 18px; text-decoration: none;">
                        ✓ Ir para o Sistema
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    let connectionTested = false;
    let hasExistingTables = false;

    // Testar conexão
    document.getElementById('btn-test').addEventListener('click', function() {
        const btn = this;
        const console = document.getElementById('console-test');
        const loading = document.getElementById('loading-test');
        const nextBtn = document.getElementById('btn-next-admin');

        console.innerHTML = '';
        console.style.display = 'block';
        loading.classList.add('active');
        btn.disabled = true;
        nextBtn.disabled = true;
        connectionTested = false;

        fetch('install.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'test_connection',
                db_host: document.getElementById('db_host').value,
                db_port: document.getElementById('db_port').value,
                db_database: document.getElementById('db_database').value,
                db_username: document.getElementById('db_username').value,
                db_password: document.getElementById('db_password').value
            })
        })
        .then(res => res.json())
        .then(data => {
            loading.classList.remove('active');
            btn.disabled = false;

            data.logs.forEach(log => {
                const div = document.createElement('div');
                div.textContent = log;
                console.appendChild(div);
            });

            if (data.success) {
                connectionTested = true;
                hasExistingTables = data.has_tables || false;

                const finalMsg = document.createElement('div');
                finalMsg.style.marginTop = '15px';
                finalMsg.style.fontSize = '16px';
                finalMsg.style.fontWeight = 'bold';
                finalMsg.style.color = '#10b981';
                finalMsg.textContent = data.message;
                console.appendChild(finalMsg);

                if (hasExistingTables) {
                    const warning = document.createElement('div');
                    warning.style.marginTop = '20px';
                    warning.style.padding = '20px';
                    warning.style.background = '#fee2e2';
                    warning.style.border = '2px solid #ef4444';
                    warning.style.borderRadius = '8px';
                    warning.style.color = '#991b1b';
                    warning.innerHTML = `
                        <strong style="font-size: 18px;">⚠️ ATENÇÃO: ${data.table_count} TABELA(S) SERÃO REMOVIDAS!</strong><br><br>
                        Esta ação é IRREVERSÍVEL!<br><br>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="confirm-cleanup" style="width: 20px; height: 20px;">
                            <span>Eu entendo e desejo continuar</span>
                        </label>
                    `;
                    console.appendChild(warning);

                    document.getElementById('confirm-cleanup').addEventListener('change', function() {
                        nextBtn.disabled = !this.checked;
                    });
                } else {
                    nextBtn.disabled = false;
                }

                btn.textContent = '✅ Conexão Testada';
                btn.style.background = '#10b981';
            } else {
                const finalMsg = document.createElement('div');
                finalMsg.style.marginTop = '15px';
                finalMsg.style.fontSize = '16px';
                finalMsg.style.fontWeight = 'bold';
                finalMsg.style.color = '#ef4444';
                finalMsg.textContent = data.message;
                console.appendChild(finalMsg);
            }

            console.scrollTop = console.scrollHeight;
        })
        .catch(err => {
            loading.classList.remove('active');
            btn.disabled = false;
            console.innerHTML = '<div style="color: #ef4444;">❌ Erro na requisição: ' + err.message + '</div>';
        });
    });

    // Próximo para admin
    document.getElementById('btn-next-admin').addEventListener('click', function() {
        if (!connectionTested) {
            alert('Teste a conexão primeiro!');
            return;
        }
        document.getElementById('step-database').classList.remove('active');
        document.getElementById('step-admin').classList.add('active');
    });

    // Voltar
    document.getElementById('btn-back').addEventListener('click', function() {
        document.getElementById('step-admin').classList.remove('active');
        document.getElementById('step-database').classList.add('active');
    });

    // Instalar
    document.getElementById('btn-install').addEventListener('click', function() {
        const password = document.getElementById('admin_password').value;
        const passwordConfirm = document.getElementById('admin_password_confirm').value;

        if (!password || password.length < 8) {
            alert('Senha deve ter no mínimo 8 caracteres');
            return;
        }

        if (password !== passwordConfirm) {
            alert('As senhas não coincidem');
            return;
        }

        const btn = this;
        const console = document.getElementById('console-install');
        const loading = document.getElementById('loading-install');

        console.innerHTML = '';
        console.style.display = 'block';
        loading.classList.add('active');
        btn.disabled = true;
        document.getElementById('btn-back').disabled = true;

        fetch('install.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'run_installation',
                admin_name: document.getElementById('admin_name').value,
                admin_email: document.getElementById('admin_email').value,
                admin_password: password
            })
        })
        .then(res => res.json())
        .then(data => {
            loading.classList.remove('active');

            data.logs.forEach(log => {
                const div = document.createElement('div');
                div.textContent = log;
                console.appendChild(div);
            });

            if (data.success) {
                setTimeout(() => {
                    document.getElementById('final-email').textContent = data.admin_email;
                    document.getElementById('step-admin').classList.remove('active');
                    document.getElementById('step-finish').classList.add('active');
                }, 2000);
            } else {
                btn.disabled = false;
                document.getElementById('btn-back').disabled = false;

                const finalMsg = document.createElement('div');
                finalMsg.style.marginTop = '15px';
                finalMsg.style.fontSize = '16px';
                finalMsg.style.fontWeight = 'bold';
                finalMsg.style.color = '#ef4444';
                finalMsg.textContent = data.message;
                console.appendChild(finalMsg);
            }

            console.scrollTop = console.scrollHeight;
        })
        .catch(err => {
            loading.classList.remove('active');
            btn.disabled = false;
            document.getElementById('btn-back').disabled = false;
            console.innerHTML = '<div style="color: #ef4444;">❌ Erro: ' + err.message + '</div>';
        });
    });

    // Resetar teste ao mudar campos
    ['db_host', 'db_port', 'db_database', 'db_username', 'db_password'].forEach(field => {
        document.getElementById(field).addEventListener('input', function() {
            if (connectionTested) {
                connectionTested = false;
                document.getElementById('btn-next-admin').disabled = true;
                document.getElementById('btn-test').textContent = '🔍 Testar Conexão';
                document.getElementById('btn-test').style.background = '#10b981';
            }
        });
    });
    </script>
</body>
</html>
