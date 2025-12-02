<?php
/**
 * Sistema de Ponto Eletrônico - Instalador Web
 * Inspirado no instalador do WordPress - interface moderna e simples
 *
 * @version 2.0
 * @author Sistema de Ponto Eletrônico
 */

// CRITICAL: Start output buffering FIRST to prevent any header issues
ob_start();

// Define constants
define('INSTALL_START_TIME', microtime(true));
define('BASEPATH', dirname(__DIR__));
define('WRITABLE_PATH', BASEPATH . '/writable');
define('ENV_FILE', BASEPATH . '/.env');
define('ENV_EXAMPLE', BASEPATH . '/.env.example');

// Error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', '1');

// Session management
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = WRITABLE_PATH . '/session';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0755, true);
    }
    if (is_writable($sessionPath)) {
        @ini_set('session.save_path', $sessionPath);
    }
    @session_start();
}

// Initialize installation session
if (!isset($_SESSION['install'])) {
    $_SESSION['install'] = [
        'step' => 0,
        'errors' => [],
        'data' => []
    ];
}

// Get current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : $_SESSION['install']['step'];

/**
 * Render HTML header
 */
function render_header($title = 'Instalação do Sistema') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex,nofollow">
        <title><?php echo htmlspecialchars($title); ?></title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .container {
                background: white;
                border-radius: 8px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 600px;
                width: 100%;
                overflow: hidden;
            }

            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }

            .header h1 {
                font-size: 28px;
                font-weight: 600;
                margin-bottom: 10px;
            }

            .header p {
                opacity: 0.9;
                font-size: 14px;
            }

            .progress-bar {
                height: 4px;
                background: rgba(255,255,255,0.3);
                position: relative;
            }

            .progress-fill {
                height: 100%;
                background: white;
                transition: width 0.3s ease;
            }

            .content {
                padding: 40px;
            }

            .step-title {
                font-size: 24px;
                font-weight: 600;
                color: #333;
                margin-bottom: 10px;
            }

            .step-description {
                color: #666;
                margin-bottom: 30px;
                line-height: 1.6;
            }

            .form-group {
                margin-bottom: 20px;
            }

            label {
                display: block;
                font-weight: 600;
                color: #333;
                margin-bottom: 8px;
                font-size: 14px;
            }

            label .required {
                color: #e74c3c;
            }

            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="url"],
            input[type="number"] {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #e0e0e0;
                border-radius: 6px;
                font-size: 14px;
                transition: border-color 0.2s;
                font-family: inherit;
            }

            input:focus {
                outline: none;
                border-color: #667eea;
            }

            .input-help {
                font-size: 12px;
                color: #888;
                margin-top: 5px;
            }

            .btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                padding: 14px 30px;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s, box-shadow 0.2s;
                width: 100%;
                margin-top: 10px;
            }

            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            }

            .btn:active {
                transform: translateY(0);
            }

            .btn-secondary {
                background: #6c757d;
            }

            .alert {
                padding: 15px 20px;
                border-radius: 6px;
                margin-bottom: 20px;
                font-size: 14px;
                line-height: 1.6;
            }

            .alert-error {
                background: #fee;
                border-left: 4px solid #e74c3c;
                color: #c0392b;
            }

            .alert-success {
                background: #efe;
                border-left: 4px solid #27ae60;
                color: #229954;
            }

            .alert-warning {
                background: #ffeaa7;
                border-left: 4px solid #f39c12;
                color: #d68910;
            }

            .alert-info {
                background: #e3f2fd;
                border-left: 4px solid #2196F3;
                color: #1976D2;
            }

            .requirement {
                display: flex;
                align-items: center;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 8px;
                font-size: 14px;
            }

            .requirement-pass {
                background: #efe;
                color: #229954;
            }

            .requirement-fail {
                background: #fee;
                color: #c0392b;
            }

            .requirement-icon {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 12px;
                font-weight: bold;
            }

            .requirement-pass .requirement-icon {
                background: #27ae60;
                color: white;
            }

            .requirement-fail .requirement-icon {
                background: #e74c3c;
                color: white;
            }

            .loading {
                text-align: center;
                padding: 40px;
            }

            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #667eea;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .success-icon {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                background: #27ae60;
                color: white;
                font-size: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
            }

            .footer {
                padding: 20px 40px;
                background: #f8f9fa;
                text-align: center;
                color: #666;
                font-size: 12px;
            }

            .two-col {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }

            @media (max-width: 600px) {
                .two-col {
                    grid-template-columns: 1fr;
                }

                .content {
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>⏱️ Sistema de Ponto Eletrônico</h1>
                <p>Instalação e Configuração Inicial</p>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(100, ($step / 5) * 100); ?>%"></div>
            </div>
            <div class="content">
    <?php
}

/**
 * Render HTML footer
 */
function render_footer() {
    ?>
            </div>
            <div class="footer">
                Sistema de Ponto Eletrônico © <?php echo date('Y'); ?> | Desenvolvido com ❤️
            </div>
        </div>
    </body>
    </html>
    <?php
    ob_end_flush();
}

/**
 * Check system requirements
 */
function check_requirements() {
    $requirements = [];

    // PHP Version
    $requirements[] = [
        'name' => 'PHP 8.1 ou superior',
        'pass' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'value' => PHP_VERSION
    ];

    // Extensions
    $required_extensions = ['mysqli', 'mbstring', 'json', 'intl', 'curl'];
    foreach ($required_extensions as $ext) {
        $requirements[] = [
            'name' => "Extensão PHP: $ext",
            'pass' => extension_loaded($ext),
            'value' => extension_loaded($ext) ? 'Instalada' : 'Não instalada'
        ];
    }

    // Writable directories
    $writable_dirs = [
        '/writable',
        '/writable/cache',
        '/writable/logs',
        '/writable/session',
        '/writable/uploads'
    ];

    foreach ($writable_dirs as $dir) {
        $path = BASEPATH . $dir;
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        $requirements[] = [
            'name' => "Diretório gravável: $dir",
            'pass' => is_writable($path),
            'value' => is_writable($path) ? 'Gravável' : 'Sem permissão'
        ];
    }

    return $requirements;
}

/**
 * Step 0: Welcome and Requirements Check
 */
function step_0_welcome() {
    render_header('Bem-vindo');

    echo '<h2 class="step-title">Bem-vindo ao Instalador!</h2>';
    echo '<p class="step-description">Vamos configurar seu sistema de ponto eletrônico em alguns passos simples.</p>';

    $requirements = check_requirements();
    $all_pass = true;

    echo '<h3 style="margin-bottom: 15px; color: #333;">Verificação de Requisitos:</h3>';

    foreach ($requirements as $req) {
        $class = $req['pass'] ? 'requirement-pass' : 'requirement-fail';
        $icon = $req['pass'] ? '✓' : '✗';

        echo '<div class="requirement ' . $class . '">';
        echo '<div class="requirement-icon">' . $icon . '</div>';
        echo '<div>';
        echo '<strong>' . htmlspecialchars($req['name']) . '</strong><br>';
        echo '<small>' . htmlspecialchars($req['value']) . '</small>';
        echo '</div>';
        echo '</div>';

        if (!$req['pass']) {
            $all_pass = false;
        }
    }

    if ($all_pass) {
        echo '<form method="post" action="?step=1">';
        echo '<button type="submit" class="btn">Iniciar Instalação →</button>';
        echo '</form>';
    } else {
        echo '<div class="alert alert-error" style="margin-top: 20px;">';
        echo '<strong>⚠️ Atenção!</strong><br>';
        echo 'Alguns requisitos não foram atendidos. Por favor, corrija-os antes de continuar.';
        echo '</div>';
    }

    render_footer();
}

/**
 * Step 1: Database Configuration
 */
function step_1_database() {
    render_header('Configuração do Banco de Dados');

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate inputs
        $host = trim($_POST['db_host'] ?? '');
        $port = trim($_POST['db_port'] ?? '3306');
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = $_POST['db_pass'] ?? '';

        if (empty($host)) $errors[] = 'Host do banco de dados é obrigatório';
        if (empty($name)) $errors[] = 'Nome do banco de dados é obrigatório';
        if (empty($user)) $errors[] = 'Usuário do banco de dados é obrigatório';

        if (empty($errors)) {
            // Test connection
            try {
                $mysqli = new mysqli($host, $user, $pass, '', (int)$port);

                if ($mysqli->connect_error) {
                    throw new Exception($mysqli->connect_error);
                }

                // Create database if not exists
                $name_escaped = $mysqli->real_escape_string($name);
                $mysqli->query("CREATE DATABASE IF NOT EXISTS `$name_escaped` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                // Select database
                if (!$mysqli->select_db($name)) {
                    throw new Exception("Não foi possível selecionar o banco de dados");
                }

                $mysqli->close();

                // Save to session
                $_SESSION['install']['data']['database'] = [
                    'host' => $host,
                    'port' => (int)$port,
                    'name' => $name,
                    'user' => $user,
                    'pass' => $pass
                ];

                $_SESSION['install']['step'] = 2;
                header('Location: ?step=2');
                exit;

            } catch (Exception $e) {
                $errors[] = 'Erro ao conectar: ' . $e->getMessage();
            }
        }
    }

    // Get previous values
    $data = $_SESSION['install']['data']['database'] ?? [];

    echo '<h2 class="step-title">Configuração do Banco de Dados</h2>';
    echo '<p class="step-description">Informe os dados de conexão com o MySQL/MariaDB.</p>';

    if (!empty($errors)) {
        echo '<div class="alert alert-error">';
        foreach ($errors as $error) {
            echo '• ' . htmlspecialchars($error) . '<br>';
        }
        echo '</div>';
    }

    echo '<form method="post">';

    echo '<div class="form-group">';
    echo '<label>Host do Banco <span class="required">*</span></label>';
    echo '<input type="text" name="db_host" value="' . htmlspecialchars($data['host'] ?? 'localhost') . '" required>';
    echo '<div class="input-help">Geralmente "localhost" ou "127.0.0.1"</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Porta</label>';
    echo '<input type="number" name="db_port" value="' . htmlspecialchars($data['port'] ?? '3306') . '" min="1" max="65535">';
    echo '<div class="input-help">Porta padrão do MySQL: 3306</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Nome do Banco <span class="required">*</span></label>';
    echo '<input type="text" name="db_name" value="' . htmlspecialchars($data['name'] ?? 'ponto_eletronico') . '" required>';
    echo '<div class="input-help">Será criado automaticamente se não existir</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Usuário do Banco <span class="required">*</span></label>';
    echo '<input type="text" name="db_user" value="' . htmlspecialchars($data['user'] ?? '') . '" required>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Senha do Banco</label>';
    echo '<input type="password" name="db_pass" value="">';
    echo '<div class="input-help">Deixe em branco se não houver senha</div>';
    echo '</div>';

    echo '<button type="submit" class="btn">Testar Conexão e Continuar →</button>';
    echo '</form>';

    render_footer();
}

/**
 * Step 2: Application Configuration
 */
function step_2_application() {
    render_header('Configuração da Aplicação');

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $app_url = trim($_POST['app_url'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $company_cnpj = trim($_POST['company_cnpj'] ?? '');

        if (empty($app_url)) $errors[] = 'URL da aplicação é obrigatória';
        if (empty($company_name)) $errors[] = 'Nome da empresa é obrigatório';

        // Validate URL
        if (!empty($app_url) && !filter_var($app_url, FILTER_VALIDATE_URL)) {
            $errors[] = 'URL inválida';
        }

        if (empty($errors)) {
            $_SESSION['install']['data']['application'] = [
                'url' => rtrim($app_url, '/'),
                'company_name' => $company_name,
                'company_cnpj' => $company_cnpj
            ];

            $_SESSION['install']['step'] = 3;
            header('Location: ?step=3');
            exit;
        }
    }

    $data = $_SESSION['install']['data']['application'] ?? [];

    // Auto-detect URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $auto_url = $protocol . '://' . $host;

    echo '<h2 class="step-title">Configuração da Aplicação</h2>';
    echo '<p class="step-description">Configure as informações básicas do sistema.</p>';

    if (!empty($errors)) {
        echo '<div class="alert alert-error">';
        foreach ($errors as $error) {
            echo '• ' . htmlspecialchars($error) . '<br>';
        }
        echo '</div>';
    }

    echo '<form method="post">';

    echo '<div class="form-group">';
    echo '<label>URL da Aplicação <span class="required">*</span></label>';
    echo '<input type="url" name="app_url" value="' . htmlspecialchars($data['url'] ?? $auto_url) . '" required>';
    echo '<div class="input-help">URL completa onde o sistema está instalado</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Nome da Empresa <span class="required">*</span></label>';
    echo '<input type="text" name="company_name" value="' . htmlspecialchars($data['company_name'] ?? '') . '" required>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>CNPJ da Empresa</label>';
    echo '<input type="text" name="company_cnpj" value="' . htmlspecialchars($data['company_cnpj'] ?? '') . '" placeholder="00.000.000/0000-00">';
    echo '<div class="input-help">Opcional</div>';
    echo '</div>';

    echo '<button type="submit" class="btn">Continuar →</button>';
    echo '</form>';

    render_footer();
}

/**
 * Step 3: Admin User Creation
 */
function step_3_admin() {
    render_header('Criar Usuário Administrador');

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['admin_name'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $password_confirm = $_POST['admin_password_confirm'] ?? '';

        if (empty($name)) $errors[] = 'Nome é obrigatório';
        if (empty($email)) $errors[] = 'E-mail é obrigatório';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido';
        if (empty($password)) $errors[] = 'Senha é obrigatória';
        if (strlen($password) < 8) $errors[] = 'A senha deve ter no mínimo 8 caracteres';
        if ($password !== $password_confirm) $errors[] = 'As senhas não coincidem';

        if (empty($errors)) {
            $_SESSION['install']['data']['admin'] = [
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ];

            $_SESSION['install']['step'] = 4;
            header('Location: ?step=4');
            exit;
        }
    }

    $data = $_SESSION['install']['data']['admin'] ?? [];

    echo '<h2 class="step-title">Criar Usuário Administrador</h2>';
    echo '<p class="step-description">Este será o usuário com acesso total ao sistema.</p>';

    if (!empty($errors)) {
        echo '<div class="alert alert-error">';
        foreach ($errors as $error) {
            echo '• ' . htmlspecialchars($error) . '<br>';
        }
        echo '</div>';
    }

    echo '<form method="post">';

    echo '<div class="form-group">';
    echo '<label>Nome Completo <span class="required">*</span></label>';
    echo '<input type="text" name="admin_name" value="' . htmlspecialchars($data['name'] ?? '') . '" required autocomplete="name">';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>E-mail <span class="required">*</span></label>';
    echo '<input type="email" name="admin_email" value="' . htmlspecialchars($data['email'] ?? '') . '" required autocomplete="email">';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Senha <span class="required">*</span></label>';
    echo '<input type="password" name="admin_password" required minlength="8" autocomplete="new-password">';
    echo '<div class="input-help">Mínimo de 8 caracteres</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Confirmar Senha <span class="required">*</span></label>';
    echo '<input type="password" name="admin_password_confirm" required minlength="8" autocomplete="new-password">';
    echo '</div>';

    echo '<button type="submit" class="btn">Criar Administrador →</button>';
    echo '</form>';

    render_footer();
}

/**
 * Step 4: Installation
 */
function step_4_install() {
    render_header('Instalando Sistema');

    // Get all data
    $db = $_SESSION['install']['data']['database'] ?? null;
    $app = $_SESSION['install']['data']['application'] ?? null;
    $admin = $_SESSION['install']['data']['admin'] ?? null;

    if (!$db || !$app || !$admin) {
        echo '<div class="alert alert-error">Dados de configuração incompletos. Por favor, reinicie a instalação.</div>';
        render_footer();
        exit;
    }

    echo '<div class="loading">';
    echo '<div class="spinner"></div>';
    echo '<h3 style="color: #333;">Instalando o sistema...</h3>';
    echo '<p style="color: #666; margin-top: 10px;">Isso pode levar alguns instantes.</p>';
    echo '</div>';
    echo '<div id="progress" style="display:none;">';

    try {
        // 1. Create/Update .env file
        echo '<div class="alert alert-info">Criando arquivo de configuração (.env)...</div>';

        $env_content = "# Sistema de Ponto Eletrônico - Configuração\n";
        $env_content .= "# Gerado automaticamente em " . date('Y-m-d H:i:s') . "\n\n";
        $env_content .= "CI_ENVIRONMENT=production\n\n";
        $env_content .= "app.baseURL='" . $app['url'] . "/'\n";
        $env_content .= "app.forceGlobalSecureRequests=true\n\n";
        $env_content .= "database.default.hostname={$db['host']}\n";
        $env_content .= "database.default.database={$db['name']}\n";
        $env_content .= "database.default.username={$db['user']}\n";
        $env_content .= "database.default.password={$db['pass']}\n";
        $env_content .= "database.default.DBDriver=MySQLi\n";
        $env_content .= "database.default.port={$db['port']}\n\n";
        $env_content .= "logger.threshold=7\n\n";
        $env_content .= "app.empresa.nome='{$app['company_name']}'\n";
        $env_content .= "app.empresa.cnpj='{$app['company_cnpj']}'\n";

        if (!file_put_contents(ENV_FILE, $env_content)) {
            throw new Exception('Não foi possível criar o arquivo .env');
        }

        echo '<div class="alert alert-success">✓ Arquivo .env criado com sucesso!</div>';

        // 2. Connect to database and create tables
        echo '<div class="alert alert-info">Conectando ao banco de dados...</div>';

        $mysqli = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], (int)$db['port']);

        if ($mysqli->connect_error) {
            throw new Exception('Erro na conexão: ' . $mysqli->connect_error);
        }

        $mysqli->set_charset('utf8mb4');

        echo '<div class="alert alert-success">✓ Conectado ao banco de dados!</div>';

        // 3. Run migrations (simple version - create employees table)
        echo '<div class="alert alert-info">Criando tabelas do banco de dados...</div>';

        $sql_employees = "CREATE TABLE IF NOT EXISTS `employees` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `password` varchar(255) NOT NULL,
            `role` varchar(50) NOT NULL DEFAULT 'funcionario',
            `active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            `deleted_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!$mysqli->query($sql_employees)) {
            throw new Exception('Erro ao criar tabela employees: ' . $mysqli->error);
        }

        // Create audit_logs table
        $sql_audit = "CREATE TABLE IF NOT EXISTS `audit_logs` (
            `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` int UNSIGNED DEFAULT NULL,
            `action` varchar(100) NOT NULL,
            `entity_type` varchar(100) NOT NULL,
            `entity_id` int UNSIGNED DEFAULT NULL,
            `old_values` text,
            `new_values` text,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` varchar(255) DEFAULT NULL,
            `url` varchar(500) DEFAULT NULL,
            `method` varchar(10) DEFAULT NULL,
            `description` text,
            `level` varchar(20) DEFAULT 'info',
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `action` (`action`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!$mysqli->query($sql_audit)) {
            throw new Exception('Erro ao criar tabela audit_logs: ' . $mysqli->error);
        }

        echo '<div class="alert alert-success">✓ Tabelas criadas com sucesso!</div>';

        // 4. Insert admin user
        echo '<div class="alert alert-info">Criando usuário administrador...</div>';

        $name = $mysqli->real_escape_string($admin['name']);
        $email = $mysqli->real_escape_string($admin['email']);
        $password = $mysqli->real_escape_string($admin['password']);
        $now = date('Y-m-d H:i:s');

        $sql_admin = "INSERT INTO `employees` (`name`, `email`, `password`, `role`, `active`, `created_at`)
                      VALUES ('$name', '$email', '$password', 'admin', 1, '$now')
                      ON DUPLICATE KEY UPDATE
                      `password`='$password', `role`='admin', `active`=1, `updated_at`='$now'";

        if (!$mysqli->query($sql_admin)) {
            throw new Exception('Erro ao criar administrador: ' . $mysqli->error);
        }

        $admin_id = $mysqli->insert_id ?: $mysqli->query("SELECT id FROM employees WHERE email='$email'")->fetch_object()->id;

        echo '<div class="alert alert-success">✓ Usuário administrador criado!</div>';

        // 5. Log installation
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sql_log = "INSERT INTO audit_logs (user_id, action, entity_type, description, ip_address, created_at)
                    VALUES ($admin_id, 'SYSTEM_INSTALLED', 'system', 'Sistema instalado com sucesso', '$ip', '$now')";
        $mysqli->query($sql_log);

        $mysqli->close();

        // Success!
        $_SESSION['install']['step'] = 5;
        $_SESSION['install']['data']['completed'] = true;

        echo '<div class="alert alert-success" style="margin-top: 20px;">';
        echo '<strong>🎉 Instalação Concluída!</strong><br>';
        echo 'O sistema foi instalado com sucesso!';
        echo '</div>';

        echo '<script>';
        echo 'setTimeout(function(){ window.location.href="?step=5"; }, 2000);';
        echo '</script>';

    } catch (Exception $e) {
        echo '<div class="alert alert-error">';
        echo '<strong>❌ Erro na Instalação</strong><br>';
        echo htmlspecialchars($e->getMessage());
        echo '</div>';
        echo '<a href="?step=1" class="btn" style="margin-top: 20px;">← Voltar e Tentar Novamente</a>';
    }

    echo '</div>';

    render_footer();
}

/**
 * Step 5: Success
 */
function step_5_success() {
    render_header('Instalação Concluída!');

    $admin = $_SESSION['install']['data']['admin'] ?? [];
    $app = $_SESSION['install']['data']['application'] ?? [];

    echo '<div style="text-align: center;">';
    echo '<div class="success-icon">✓</div>';
    echo '<h2 class="step-title">Instalação Concluída!</h2>';
    echo '<p class="step-description">Seu sistema está pronto para uso.</p>';
    echo '</div>';

    echo '<div class="alert alert-success">';
    echo '<strong>✅ Sistema instalado com sucesso!</strong><br><br>';
    echo '<strong>Credenciais do Administrador:</strong><br>';
    echo 'E-mail: <strong>' . htmlspecialchars($admin['email']) . '</strong><br>';
    echo 'Senha: <em>a senha que você definiu</em>';
    echo '</div>';

    echo '<div class="alert alert-warning">';
    echo '<strong>⚠️ Importante - Segurança:</strong><br>';
    echo '• DELETE este arquivo install.php AGORA!<br>';
    echo '• Guarde suas credenciais em local seguro<br>';
    echo '• O arquivo .env contém informações sensíveis';
    echo '</div>';

    echo '<a href="/auth/login" class="btn">Acessar o Sistema →</a>';

    // Clear installation session
    unset($_SESSION['install']);

    render_footer();
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

// Check if already installed
if (file_exists(ENV_FILE) && $step < 5) {
    $env_content = file_get_contents(ENV_FILE);
    if (strpos($env_content, 'database.default.database') !== false) {
        render_header('Sistema Já Instalado');
        echo '<div class="alert alert-warning">';
        echo '<strong>⚠️ Sistema já instalado!</strong><br>';
        echo 'O sistema já foi instalado. Se deseja reinstalar, delete o arquivo .env primeiro.';
        echo '</div>';
        echo '<a href="/auth/login" class="btn">Ir para o Login</a>';
        render_footer();
        exit;
    }
}

// Route to appropriate step
switch ($step) {
    case 0:
        step_0_welcome();
        break;
    case 1:
        step_1_database();
        break;
    case 2:
        step_2_application();
        break;
    case 3:
        step_3_admin();
        break;
    case 4:
        step_4_install();
        break;
    case 5:
        step_5_success();
        break;
    default:
        step_0_welcome();
}
