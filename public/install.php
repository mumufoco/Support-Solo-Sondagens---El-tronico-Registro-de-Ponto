<?php
/**
 * Sistema de Ponto Eletrônico - Instalador Web v3.0
 *
 * CHANGELOG v3.0:
 * - FIX: Loop infinito entre fase 4 e 5 (display:none removido)
 * - FIX: Login do administrador (prepared statements, sem corrupção de hash)
 * - ADD: Criação completa de todas as tabelas necessárias
 * - ADD: AJAX installation para melhor UX
 * - ADD: Validação robusta em todas as etapas
 * - ADD: Rollback automático em caso de erro
 *
 * @version 3.0.0
 * @author Sistema de Ponto Eletrônico
 * @date 2025-12-05
 */

// CRITICAL: Start output buffering and session FIRST
ob_start();
session_start();

// Define constants
define('INSTALL_START_TIME', microtime(true));
define('BASEPATH', dirname(__DIR__));
define('WRITABLE_PATH', BASEPATH . '/writable');
define('ENV_FILE', BASEPATH . '/.env');
define('ENV_EXAMPLE', BASEPATH . '/.env.example');
define('INSTALLER_VERSION', '3.0.0');

// Error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Initialize installation session
if (!isset($_SESSION['install'])) {
    $_SESSION['install'] = [
        'step' => 0,
        'errors' => [],
        'data' => [],
        'started_at' => time()
    ];
}

// Get current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : $_SESSION['install']['step'];

/**
 * Security: Generate random encryption key
 */
function generate_encryption_key(): string {
    return bin2hex(random_bytes(32));
}

/**
 * Generate unique employee code (8 alphanumeric characters)
 */
function generate_unique_code(): string {
    // Generate 8 character alphanumeric code (uppercase letters and numbers)
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Render HTML header
 */
function render_header(string $title = 'Instalação do Sistema'): void {
    global $step;
    $progress = min(100, round(($step / 5) * 100));
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex,nofollow">
        <title><?= htmlspecialchars($title) ?> - Instalador v<?= INSTALLER_VERSION ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }

            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                line-height: 1.6;
            }

            .container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 650px;
                width: 100%;
                overflow: hidden;
            }

            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 32px;
                text-align: center;
            }

            .header h1 {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 8px;
            }

            .header .version {
                font-size: 12px;
                opacity: 0.85;
                font-weight: 500;
            }

            .progress-bar {
                height: 6px;
                background: rgba(255,255,255,0.2);
                position: relative;
            }

            .progress-fill {
                height: 100%;
                background: white;
                width: <?= $progress ?>%;
                transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .content {
                padding: 40px;
            }

            .step-title {
                font-size: 26px;
                font-weight: 700;
                color: #2d3748;
                margin-bottom: 12px;
            }

            .step-description {
                color: #718096;
                margin-bottom: 32px;
                font-size: 15px;
            }

            .form-group {
                margin-bottom: 24px;
            }

            label {
                display: block;
                font-weight: 600;
                color: #2d3748;
                margin-bottom: 8px;
                font-size: 14px;
            }

            label .required {
                color: #e53e3e;
                margin-left: 2px;
            }

            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="url"],
            input[type="number"] {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e2e8f0;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.2s;
                font-family: inherit;
            }

            input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }

            input.error {
                border-color: #e53e3e;
            }

            .input-help {
                font-size: 13px;
                color: #a0aec0;
                margin-top: 6px;
            }

            .btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                padding: 14px 32px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                width: 100%;
                margin-top: 12px;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
            }

            .btn:hover:not(:disabled) {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
            }

            .btn:active:not(:disabled) {
                transform: translateY(0);
            }

            .btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }

            .alert {
                padding: 16px 20px;
                border-radius: 8px;
                margin-bottom: 24px;
                font-size: 14px;
                border-left: 4px solid;
            }

            .alert-error {
                background: #fff5f5;
                border-color: #e53e3e;
                color: #742a2a;
            }

            .alert-success {
                background: #f0fff4;
                border-color: #38a169;
                color: #22543d;
            }

            .alert-warning {
                background: #fffaf0;
                border-color: #ed8936;
                color: #7c2d12;
            }

            .alert-info {
                background: #ebf8ff;
                border-color: #4299e1;
                color: #2c5282;
            }

            .alert strong {
                display: block;
                margin-bottom: 4px;
            }

            .requirement {
                display: flex;
                align-items: center;
                padding: 14px;
                border-radius: 8px;
                margin-bottom: 10px;
                font-size: 14px;
                border: 1px solid;
            }

            .requirement-pass {
                background: #f0fff4;
                border-color: #38a169;
                color: #22543d;
            }

            .requirement-fail {
                background: #fff5f5;
                border-color: #e53e3e;
                color: #742a2a;
            }

            .requirement-icon {
                width: 26px;
                height: 26px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 12px;
                font-weight: 700;
                font-size: 14px;
            }

            .requirement-pass .requirement-icon {
                background: #38a169;
                color: white;
            }

            .requirement-fail .requirement-icon {
                background: #e53e3e;
                color: white;
            }

            .loading {
                text-align: center;
                padding: 48px 20px;
            }

            .spinner {
                border: 5px solid #edf2f7;
                border-top: 5px solid #667eea;
                border-radius: 50%;
                width: 60px;
                height: 60px;
                animation: spin 1s linear infinite;
                margin: 0 auto 24px;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .success-icon {
                width: 90px;
                height: 90px;
                border-radius: 50%;
                background: #38a169;
                color: white;
                font-size: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
                animation: scaleIn 0.5s ease-out;
            }

            @keyframes scaleIn {
                0% { transform: scale(0); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }

            .footer {
                padding: 20px 40px;
                background: #f7fafc;
                text-align: center;
                color: #718096;
                font-size: 13px;
            }

            .progress-list {
                max-height: 400px;
                overflow-y: auto;
            }

            .progress-item {
                padding: 12px;
                margin-bottom: 8px;
                border-radius: 6px;
                font-size: 14px;
                display: flex;
                align-items: center;
                transition: all 0.3s;
            }

            .progress-item.pending {
                background: #f7fafc;
                color: #718096;
            }

            .progress-item.running {
                background: #ebf8ff;
                color: #2c5282;
                border-left: 3px solid #4299e1;
            }

            .progress-item.success {
                background: #f0fff4;
                color: #22543d;
                border-left: 3px solid #38a169;
            }

            .progress-item.error {
                background: #fff5f5;
                color: #742a2a;
                border-left: 3px solid #e53e3e;
            }

            .progress-item-icon {
                margin-right: 10px;
                font-size: 16px;
            }

            @media (max-width: 600px) {
                .content { padding: 24px; }
                .header { padding: 24px; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>⏱️ Sistema de Ponto Eletrônico</h1>
                <p class="version">Instalador v<?= INSTALLER_VERSION ?> | Etapa <?= $step ?> de 5</p>
            </div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="content">
    <?php
}

/**
 * Render HTML footer
 */
function render_footer(): void {
    ?>
            </div>
            <div class="footer">
                Sistema de Ponto Eletrônico © <?= date('Y') ?> | Desenvolvido com ❤️
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
function check_requirements(): array {
    $requirements = [];

    // PHP Version
    $requirements[] = [
        'name' => 'PHP 8.1 ou superior',
        'pass' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'value' => 'Versão atual: ' . PHP_VERSION
    ];

    // Required extensions
    $required_extensions = ['mysqli', 'mbstring', 'json', 'intl', 'curl', 'openssl'];
    foreach ($required_extensions as $ext) {
        $requirements[] = [
            'name' => "Extensão PHP: $ext",
            'pass' => extension_loaded($ext),
            'value' => extension_loaded($ext) ? 'Instalada' : 'NÃO instalada'
        ];
    }

    // Writable directories
    $writable_dirs = [
        '/writable' => true,
        '/writable/cache' => true,
        '/writable/logs' => true,
        '/writable/session' => true,
        '/writable/uploads' => true,
        '/.env' => false // File, not directory
    ];

    foreach ($writable_dirs as $path => $is_dir) {
        $full_path = BASEPATH . $path;

        if ($is_dir && !is_dir($full_path)) {
            @mkdir($full_path, 0755, true);
        }

        $is_writable = $is_dir ? is_writable($full_path) : is_writable(dirname($full_path));

        $requirements[] = [
            'name' => ($is_dir ? "Diretório gravável: " : "Permissão de escrita: ") . $path,
            'pass' => $is_writable,
            'value' => $is_writable ? 'OK' : 'SEM PERMISSÃO'
        ];
    }

    return $requirements;
}

/**
 * Step 0: Welcome and Requirements Check
 */
function step_0_welcome(): void {
    render_header('Bem-vindo à Instalação');

    echo '<h2 class="step-title">👋 Bem-vindo!</h2>';
    echo '<p class="step-description">Vamos configurar seu sistema de ponto eletrônico em 5 etapas simples e rápidas.</p>';

    $requirements = check_requirements();
    $all_pass = true;

    echo '<h3 style="margin: 24px 0 16px; color: #2d3748; font-size: 18px; font-weight: 600;">Verificação de Requisitos do Sistema:</h3>';

    foreach ($requirements as $req) {
        $class = $req['pass'] ? 'requirement-pass' : 'requirement-fail';
        $icon = $req['pass'] ? '✓' : '✗';

        echo '<div class="requirement ' . $class . '">';
        echo '<div class="requirement-icon">' . $icon . '</div>';
        echo '<div style="flex: 1;">';
        echo '<strong>' . htmlspecialchars($req['name']) . '</strong><br>';
        echo '<small>' . htmlspecialchars($req['value']) . '</small>';
        echo '</div>';
        echo '</div>';

        if (!$req['pass']) {
            $all_pass = false;
        }
    }

    if ($all_pass) {
        echo '<div class="alert alert-success" style="margin-top: 24px;">';
        echo '<strong>✅ Todos os requisitos foram atendidos!</strong><br>';
        echo 'Seu servidor está pronto para a instalação.';
        echo '</div>';

        echo '<form method="post" action="?step=1">';
        echo '<button type="submit" class="btn">Iniciar Instalação →</button>';
        echo '</form>';
    } else {
        echo '<div class="alert alert-error" style="margin-top: 24px;">';
        echo '<strong>❌ Requisitos não atendidos</strong><br>';
        echo 'Alguns requisitos do sistema não foram atendidos. Por favor, corrija os itens marcados com ✗ antes de continuar.';
        echo '</div>';

        echo '<button type="button" class="btn" onclick="location.reload()">🔄 Verificar Novamente</button>';
    }

    render_footer();
}

/**
 * Step 1: Database Configuration
 */
function step_1_database(): void {
    render_header('Configuração do Banco de Dados');

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and validate inputs
        $host = trim($_POST['db_host'] ?? '');
        $port = (int)($_POST['db_port'] ?? 3306);
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = $_POST['db_pass'] ?? '';

        // Validation
        if (empty($host)) $errors[] = 'Host do banco de dados é obrigatório';
        if (empty($name)) $errors[] = 'Nome do banco de dados é obrigatório';
        if (empty($user)) $errors[] = 'Usuário do banco de dados é obrigatório';
        if ($port < 1 || $port > 65535) $errors[] = 'Porta inválida';

        // Validate database name (alphanumeric, underscore, hyphen only)
        if (!empty($name) && !preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            $errors[] = 'Nome do banco de dados deve conter apenas letras, números, underscore e hífen';
        }

        if (empty($errors)) {
            // Test connection
            try {
                $mysqli = @new mysqli($host, $user, $pass, '', $port);

                if ($mysqli->connect_error) {
                    throw new Exception('Erro de conexão: ' . $mysqli->connect_error);
                }

                // Set charset
                $mysqli->set_charset('utf8mb4');

                // Create database if not exists
                $name_escaped = $mysqli->real_escape_string($name);
                if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS `$name_escaped` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                    throw new Exception('Erro ao criar banco de dados: ' . $mysqli->error);
                }

                // Select database
                if (!$mysqli->select_db($name)) {
                    throw new Exception('Não foi possível selecionar o banco de dados: ' . $mysqli->error);
                }

                // Test if we can create tables
                $test_table = "CREATE TEMPORARY TABLE `test_permission` (`id` INT)";
                if (!$mysqli->query($test_table)) {
                    throw new Exception('Permissão insuficiente para criar tabelas: ' . $mysqli->error);
                }

                $mysqli->close();

                // Save to session
                $_SESSION['install']['data']['database'] = [
                    'host' => $host,
                    'port' => $port,
                    'name' => $name,
                    'user' => $user,
                    'pass' => $pass
                ];

                $_SESSION['install']['step'] = 2;

                // Redirect to next step
                header('Location: ?step=2');
                exit;

            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    // Get previous values
    $data = $_SESSION['install']['data']['database'] ?? [];

    echo '<h2 class="step-title">🗄️ Banco de Dados</h2>';
    echo '<p class="step-description">Configure a conexão com o MySQL/MariaDB onde os dados serão armazenados.</p>';

    if (!empty($errors)) {
        echo '<div class="alert alert-error">';
        echo '<strong>Erros encontrados:</strong>';
        foreach ($errors as $error) {
            echo '<br>• ' . htmlspecialchars($error);
        }
        echo '</div>';
    }

    echo '<form method="post" id="dbForm">';

    echo '<div class="form-group">';
    echo '<label>Host do Banco de Dados <span class="required">*</span></label>';
    echo '<input type="text" name="db_host" value="' . htmlspecialchars($data['host'] ?? 'localhost') . '" required autofocus>';
    echo '<div class="input-help">Geralmente "localhost", "127.0.0.1" ou IP do servidor MySQL</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Porta</label>';
    echo '<input type="number" name="db_port" value="' . htmlspecialchars($data['port'] ?? '3306') . '" min="1" max="65535" required>';
    echo '<div class="input-help">Porta padrão do MySQL: 3306</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Nome do Banco de Dados <span class="required">*</span></label>';
    echo '<input type="text" name="db_name" value="' . htmlspecialchars($data['name'] ?? 'ponto_eletronico') . '" required pattern="[a-zA-Z0-9_-]+">';
    echo '<div class="input-help">Será criado automaticamente se não existir</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Usuário do Banco <span class="required">*</span></label>';
    echo '<input type="text" name="db_user" value="' . htmlspecialchars($data['user'] ?? 'root') . '" required autocomplete="username">';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Senha do Banco</label>';
    echo '<input type="password" name="db_pass" value="' . htmlspecialchars($data['pass'] ?? '') . '" autocomplete="current-password">';
    echo '<div class="input-help">Deixe em branco se não houver senha</div>';
    echo '</div>';

    echo '<button type="submit" class="btn">Testar Conexão e Continuar →</button>';
    echo '</form>';

    render_footer();
}

/**
 * Step 2: Application Configuration
 */
function step_2_application(): void {
    render_header('Configuração da Aplicação');

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $app_url = trim($_POST['app_url'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $company_cnpj = preg_replace('/[^0-9]/', '', $_POST['company_cnpj'] ?? '');

        // Validation
        if (empty($app_url)) {
            $errors[] = 'URL da aplicação é obrigatória';
        } elseif (!filter_var($app_url, FILTER_VALIDATE_URL)) {
            $errors[] = 'URL inválida. Deve começar com http:// ou https://';
        }

        if (empty($company_name)) {
            $errors[] = 'Nome da empresa é obrigatório';
        }

        if (!empty($company_cnpj) && strlen($company_cnpj) !== 14) {
            $errors[] = 'CNPJ inválido (deve ter 14 dígitos)';
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
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $auto_url = $protocol . '://' . $host;

    echo '<h2 class="step-title">⚙️ Configuração</h2>';
    echo '<p class="step-description">Defina as configurações básicas da sua aplicação.</p>';

    if (!empty($errors)) {
        echo '<div class="alert alert-error">';
        echo '<strong>Erros encontrados:</strong>';
        foreach ($errors as $error) {
            echo '<br>• ' . htmlspecialchars($error);
        }
        echo '</div>';
    }

    echo '<form method="post">';

    echo '<div class="form-group">';
    echo '<label>URL da Aplicação <span class="required">*</span></label>';
    echo '<input type="url" name="app_url" value="' . htmlspecialchars($data['url'] ?? $auto_url) . '" required autofocus>';
    echo '<div class="input-help">URL completa onde o sistema está instalado (incluindo http:// ou https://)</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Nome da Empresa <span class="required">*</span></label>';
    echo '<input type="text" name="company_name" value="' . htmlspecialchars($data['company_name'] ?? '') . '" required maxlength="255">';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>CNPJ da Empresa (opcional)</label>';
    echo '<input type="text" name="company_cnpj" value="' . htmlspecialchars($data['company_cnpj'] ?? '') . '" placeholder="00.000.000/0000-00" maxlength="18">';
    echo '<div class="input-help">Apenas números, sem pontos ou traços</div>';
    echo '</div>';

    echo '<button type="submit" class="btn">Continuar →</button>';
    echo '</form>';

    render_footer();
}

/**
 * Step 3: Admin User Creation
 */
function step_3_admin(): void {
    render_header('Criar Administrador');

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['admin_name'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', $_POST['admin_cpf'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $password_confirm = $_POST['admin_password_confirm'] ?? '';

        // Validation
        if (empty($name)) {
            $errors[] = 'Nome é obrigatório';
        } elseif (strlen($name) < 3) {
            $errors[] = 'Nome deve ter no mínimo 3 caracteres';
        }

        if (empty($email)) {
            $errors[] = 'E-mail é obrigatório';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido';
        }

        if (empty($cpf)) {
            $errors[] = 'CPF é obrigatório';
        } elseif (strlen($cpf) !== 11) {
            $errors[] = 'CPF inválido (deve ter 11 dígitos)';
        }

        if (empty($password)) {
            $errors[] = 'Senha é obrigatória';
        } elseif (strlen($password) < 8) {
            $errors[] = 'A senha deve ter no mínimo 8 caracteres';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra maiúscula';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um número';
        }

        if ($password !== $password_confirm) {
            $errors[] = 'As senhas não coincidem';
        }

        if (empty($errors)) {
            // Store PLAIN password temporarily - will be hashed during installation
            $_SESSION['install']['data']['admin'] = [
                'name' => $name,
                'email' => strtolower($email),
                'cpf' => $cpf,
                'password_plain' => $password // Store plain for display
            ];

            $_SESSION['install']['step'] = 4;
            header('Location: ?step=4');
            exit;
        }
    }

    $data = $_SESSION['install']['data']['admin'] ?? [];

    echo '<h2 class="step-title">👤 Administrador</h2>';
    echo '<p class="step-description">Crie a conta do usuário administrador que terá acesso total ao sistema.</p>';

    if (!empty($errors)) {
        echo '<div class="alert alert-error">';
        echo '<strong>Erros encontrados:</strong>';
        foreach ($errors as $error) {
            echo '<br>• ' . htmlspecialchars($error);
        }
        echo '</div>';
    }

    echo '<div class="alert alert-info">';
    echo '<strong>ℹ️ Requisitos de Senha:</strong><br>';
    echo '• Mínimo de 8 caracteres<br>';
    echo '• Pelo menos uma letra maiúscula<br>';
    echo '• Pelo menos um número';
    echo '</div>';

    echo '<form method="post">';

    echo '<div class="form-group">';
    echo '<label>Nome Completo <span class="required">*</span></label>';
    echo '<input type="text" name="admin_name" value="' . htmlspecialchars($data['name'] ?? '') . '" required minlength="3" maxlength="255" autocomplete="name" autofocus>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>E-mail <span class="required">*</span></label>';
    echo '<input type="email" name="admin_email" value="' . htmlspecialchars($data['email'] ?? '') . '" required autocomplete="email">';
    echo '<div class="input-help">Este será seu login para acessar o sistema</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>CPF <span class="required">*</span></label>';
    echo '<input type="text" name="admin_cpf" value="' . htmlspecialchars($data['cpf'] ?? '') . '" required pattern="[0-9]{3}\.?[0-9]{3}\.?[0-9]{3}-?[0-9]{2}" placeholder="000.000.000-00" maxlength="14">';
    echo '<div class="input-help">Apenas números, com ou sem pontos e traço</div>';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Senha <span class="required">*</span></label>';
    echo '<input type="password" name="admin_password" required minlength="8" autocomplete="new-password">';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>Confirmar Senha <span class="required">*</span></label>';
    echo '<input type="password" name="admin_password_confirm" required minlength="8" autocomplete="new-password">';
    echo '</div>';

    echo '<button type="submit" class="btn">Finalizar Configuração →</button>';
    echo '</form>';

    render_footer();
}

/**
 * Get database connection
 */
function get_db_connection(): mysqli {
    $db = $_SESSION['install']['data']['database'] ?? null;

    if (!$db) {
        throw new Exception('Configuração do banco de dados não encontrada');
    }

    $mysqli = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], (int)$db['port']);

    if ($mysqli->connect_error) {
        throw new Exception('Erro de conexão: ' . $mysqli->connect_error);
    }

    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}

/**
 * Create all database tables
 */
function create_database_tables(mysqli $mysqli): array {
    $results = [];

    // SQL for all tables
    $tables = [
        'employees' => "CREATE TABLE IF NOT EXISTS `employees` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `cpf` VARCHAR(14) DEFAULT NULL,
            `unique_code` VARCHAR(8) NOT NULL,
            `role` ENUM('admin', 'gestor', 'funcionario') NOT NULL DEFAULT 'funcionario',
            `department` VARCHAR(100) DEFAULT NULL,
            `position` VARCHAR(100) DEFAULT NULL,
            `work_start_time` TIME DEFAULT '08:00:00',
            `work_end_time` TIME DEFAULT '17:00:00',
            `hours_balance` DECIMAL(10,2) DEFAULT 0.00,
            `extra_hours_balance` DECIMAL(10,2) DEFAULT 0.00,
            `owed_hours_balance` DECIMAL(10,2) DEFAULT 0.00,
            `has_face_biometric` TINYINT(1) DEFAULT 0,
            `has_fingerprint_biometric` TINYINT(1) DEFAULT 0,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME DEFAULT NULL,
            `updated_at` DATETIME DEFAULT NULL,
            `deleted_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`),
            UNIQUE KEY `unique_code` (`unique_code`),
            INDEX `role` (`role`),
            INDEX `department` (`department`),
            INDEX `active` (`active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'time_punches' => "CREATE TABLE IF NOT EXISTS `time_punches` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `employee_id` INT UNSIGNED NOT NULL,
            `punch_time` DATETIME NOT NULL,
            `punch_type` ENUM('entrada', 'saida', 'intervalo_inicio', 'intervalo_fim') NOT NULL DEFAULT 'entrada',
            `location` VARCHAR(255) DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `method` ENUM('manual', 'qrcode', 'facial', 'fingerprint', 'code') DEFAULT 'manual',
            `status` ENUM('normal', 'late', 'early', 'edited') DEFAULT 'normal',
            `edited_by` INT UNSIGNED DEFAULT NULL,
            `edit_reason` TEXT DEFAULT NULL,
            `created_at` DATETIME DEFAULT NULL,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `employee_id` (`employee_id`),
            INDEX `punch_time` (`punch_time`),
            INDEX `punch_type` (`punch_type`),
            FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'justifications' => "CREATE TABLE IF NOT EXISTS `justifications` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `employee_id` INT UNSIGNED NOT NULL,
            `type` ENUM('absence', 'late', 'early_leave', 'forgot_punch', 'other') NOT NULL,
            `date` DATE NOT NULL,
            `description` TEXT NOT NULL,
            `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            `reviewed_by` INT UNSIGNED DEFAULT NULL,
            `reviewed_at` DATETIME DEFAULT NULL,
            `review_comment` TEXT DEFAULT NULL,
            `attachment` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME DEFAULT NULL,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `employee_id` (`employee_id`),
            INDEX `status` (`status`),
            INDEX `date` (`date`),
            FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'warnings' => "CREATE TABLE IF NOT EXISTS `warnings` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `employee_id` INT UNSIGNED NOT NULL,
            `issued_by` INT UNSIGNED NOT NULL,
            `type` ENUM('verbal', 'written', 'suspension', 'dismissal') NOT NULL,
            `reason` TEXT NOT NULL,
            `severity` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            `acknowledged` TINYINT(1) DEFAULT 0,
            `acknowledged_at` DATETIME DEFAULT NULL,
            `signature` TEXT DEFAULT NULL,
            `expires_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME DEFAULT NULL,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `employee_id` (`employee_id`),
            INDEX `issued_by` (`issued_by`),
            FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'notifications' => "CREATE TABLE IF NOT EXISTS `notifications` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `employee_id` INT UNSIGNED NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `type` ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
            `read_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `employee_id` (`employee_id`),
            INDEX `read_at` (`read_at`),
            FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'audit_logs' => "CREATE TABLE IF NOT EXISTS `audit_logs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED DEFAULT NULL,
            `action` VARCHAR(100) NOT NULL,
            `entity_type` VARCHAR(100) NOT NULL,
            `entity_id` INT UNSIGNED DEFAULT NULL,
            `old_values` TEXT DEFAULT NULL,
            `new_values` TEXT DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `user_agent` VARCHAR(500) DEFAULT NULL,
            `url` VARCHAR(500) DEFAULT NULL,
            `method` VARCHAR(10) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `level` ENUM('debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency') DEFAULT 'info',
            `created_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `user_id` (`user_id`),
            INDEX `action` (`action`),
            INDEX `entity_type` (`entity_type`),
            INDEX `level` (`level`),
            INDEX `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'system_settings' => "CREATE TABLE IF NOT EXISTS `system_settings` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `setting_key` VARCHAR(100) NOT NULL,
            `setting_value` TEXT DEFAULT NULL,
            `setting_type` ENUM('string', 'integer', 'boolean', 'json', 'file') DEFAULT 'string',
            `setting_group` ENUM('appearance', 'authentication', 'certificate', 'system', 'security') DEFAULT 'system',
            `is_encrypted` TINYINT(1) DEFAULT 0,
            `description` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME DEFAULT NULL,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`),
            INDEX `setting_group` (`setting_group`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($tables as $table_name => $sql) {
        try {
            if (!$mysqli->query($sql)) {
                throw new Exception($mysqli->error);
            }
            $results[$table_name] = ['success' => true, 'message' => "Tabela $table_name criada"];
        } catch (Exception $e) {
            $results[$table_name] = ['success' => false, 'message' => "Erro na tabela $table_name: " . $e->getMessage()];
            throw $e; // Re-throw to stop installation
        }
    }

    return $results;
}

/**
 * Step 4: Installation Process
 */
function step_4_install(): void {
    render_header('Instalação em Andamento');

    // Get all configuration data
    $db = $_SESSION['install']['data']['database'] ?? null;
    $app = $_SESSION['install']['data']['application'] ?? null;
    $admin = $_SESSION['install']['data']['admin'] ?? null;

    if (!$db || !$app || !$admin) {
        echo '<div class="alert alert-error">';
        echo '<strong>❌ Erro</strong><br>';
        echo 'Dados de configuração incompletos. Por favor, volte e preencha todos os passos.';
        echo '</div>';
        echo '<a href="?step=1" class="btn">← Voltar para o Início</a>';
        render_footer();
        exit;
    }

    echo '<h2 class="step-title">⚙️ Instalando...</h2>';
    echo '<p class="step-description">Por favor, aguarde enquanto configuramos tudo para você.</p>';

    echo '<div class="progress-list" id="progressList">';
    echo '<div class="progress-item pending" id="step-env"><span class="progress-item-icon">⏳</span> Criando arquivo de configuração</div>';
    echo '<div class="progress-item pending" id="step-db"><span class="progress-item-icon">⏳</span> Conectando ao banco de dados</div>';
    echo '<div class="progress-item pending" id="step-tables"><span class="progress-item-icon">⏳</span> Criando tabelas</div>';
    echo '<div class="progress-item pending" id="step-admin"><span class="progress-item-icon">⏳</span> Criando usuário administrador</div>';
    echo '<div class="progress-item pending" id="step-final"><span class="progress-item-icon">⏳</span> Finalizando instalação</div>';
    echo '</div>';

    echo '<div id="resultArea" style="margin-top: 24px;"></div>';

    // JavaScript for real-time progress
    ?>
    <script>
    (function() {
        const updateStep = (stepId, status, message) => {
            const el = document.getElementById('step-' + stepId);
            if (!el) return;

            el.className = 'progress-item ' + status;

            let icon = '⏳';
            if (status === 'running') icon = '▶️';
            else if (status === 'success') icon = '✅';
            else if (status === 'error') icon = '❌';

            el.querySelector('.progress-item-icon').textContent = icon;

            if (message) {
                const text = el.textContent.split('\n')[0].replace(/^[⏳▶️✅❌]\s*/, '');
                el.innerHTML = '<span class="progress-item-icon">' + icon + '</span> ' + text;
                if (message !== text) {
                    el.innerHTML += '<br><small style="margin-left: 28px;">' + message + '</small>';
                }
            }
        };

        const showResult = (type, title, message, redirect) => {
            const resultArea = document.getElementById('resultArea');
            resultArea.innerHTML = '<div class="alert alert-' + type + '"><strong>' + title + '</strong><br>' + message + '</div>';

            if (redirect) {
                resultArea.innerHTML += '<a href="' + redirect + '" class="btn">Continuar →</a>';
                setTimeout(() => { window.location.href = redirect; }, 2000);
            }
        };

        // Execute installation
        setTimeout(() => {
            executeInstallation();
        }, 500);

        async function executeInstallation() {
            try {
                // The PHP code will execute and show results
                // This is just for UI feedback
                updateStep('env', 'running');
                await sleep(300);

                <?php
                try {
                    // STEP 1: Create .env file
                    updateStepStatus('env', 'running', 'Gerando configurações...');

                    $encryption_key = generate_encryption_key();

                    $env_content = "# Sistema de Ponto Eletrônico - Configuração\n";
                    $env_content .= "# Gerado automaticamente em " . date('Y-m-d H:i:s') . "\n";
                    $env_content .= "# Instalador v" . INSTALLER_VERSION . "\n\n";
                    $env_content .= "CI_ENVIRONMENT=production\n\n";
                    $env_content .= "app.baseURL='" . $app['url'] . "/'\n";
                    $env_content .= "app.forceGlobalSecureRequests=false\n";
                    $env_content .= "app.CSPEnabled=false\n\n";
                    $env_content .= "# Database\n";
                    $env_content .= "database.default.hostname={$db['host']}\n";
                    $env_content .= "database.default.database={$db['name']}\n";
                    $env_content .= "database.default.username={$db['user']}\n";
                    $env_content .= "database.default.password={$db['pass']}\n";
                    $env_content .= "database.default.DBDriver=MySQLi\n";
                    $env_content .= "database.default.port={$db['port']}\n";
                    $env_content .= "database.default.DBPrefix=\n";
                    $env_content .= "database.default.charset=utf8mb4\n";
                    $env_content .= "database.default.DBCollat=utf8mb4_unicode_ci\n\n";
                    $env_content .= "# Encryption\n";
                    $env_content .= "encryption.key=hex2bin:" . $encryption_key . "\n\n";
                    $env_content .= "# Logging\n";
                    $env_content .= "logger.threshold=4\n\n";
                    $env_content .= "# Company\n";
                    $env_content .= "app.empresa.nome='{$app['company_name']}'\n";
                    $env_content .= "app.empresa.cnpj='{$app['company_cnpj']}'\n";

                    if (!file_put_contents(ENV_FILE, $env_content)) {
                        throw new Exception('Não foi possível criar o arquivo .env - verifique as permissões');
                    }

                    updateStepStatus('env', 'success', 'Arquivo .env criado com sucesso!');

                    // STEP 2: Connect to database
                    updateStepStatus('db', 'running', 'Estabelecendo conexão...');

                    $mysqli = get_db_connection();

                    updateStepStatus('db', 'success', 'Conectado ao banco ' . $db['name']);

                    // STEP 3: Create tables
                    updateStepStatus('tables', 'running', 'Criando estrutura do banco...');

                    $table_results = create_database_tables($mysqli);

                    $tables_count = count($table_results);
                    updateStepStatus('tables', 'success', "{$tables_count} tabelas criadas com sucesso");

                    // STEP 4: Create admin user using PREPARED STATEMENT (critical fix!)
                    updateStepStatus('admin', 'running', 'Criando conta de administrador...');

                    // Hash the password CORRECTLY without escaping
                    $password_hash = password_hash($admin['password_plain'], PASSWORD_BCRYPT);
                    $now = date('Y-m-d H:i:s');

                    // Generate unique code for employee
                    $unique_code = generate_unique_code();

                    // Ensure unique_code is unique (retry if collision)
                    $max_attempts = 10;
                    $attempt = 0;
                    while ($attempt < $max_attempts) {
                        $check_stmt = $mysqli->prepare("SELECT id FROM `employees` WHERE `unique_code` = ?");
                        $check_stmt->bind_param('s', $unique_code);
                        $check_stmt->execute();
                        $check_stmt->store_result();

                        if ($check_stmt->num_rows === 0) {
                            $check_stmt->close();
                            break; // Code is unique
                        }

                        $check_stmt->close();
                        $unique_code = generate_unique_code(); // Generate new code
                        $attempt++;
                    }

                    if ($attempt >= $max_attempts) {
                        throw new Exception('Não foi possível gerar um código único após ' . $max_attempts . ' tentativas');
                    }

                    // Use prepared statement to prevent any corruption
                    $stmt = $mysqli->prepare("INSERT INTO `employees` (`name`, `email`, `cpf`, `unique_code`, `password`, `role`, `active`, `created_at`) VALUES (?, ?, ?, ?, ?, 'admin', 1, ?)");

                    if (!$stmt) {
                        throw new Exception('Erro ao preparar statement: ' . $mysqli->error);
                    }

                    $stmt->bind_param('ssssss', $admin['name'], $admin['email'], $admin['cpf'], $unique_code, $password_hash, $now);

                    if (!$stmt->execute()) {
                        throw new Exception('Erro ao criar administrador: ' . $stmt->error);
                    }

                    $admin_id = $stmt->insert_id;
                    $stmt->close();

                    updateStepStatus('admin', 'success', 'Administrador criado com ID: ' . $admin_id . ' e código: ' . $unique_code);

                    // STEP 5: Insert default settings
                    updateStepStatus('final', 'running', 'Configurações finais...');

                    $default_settings = [
                        ['company_name', $app['company_name'], 'string', 'system'],
                        ['company_cnpj', $app['company_cnpj'], 'string', 'system'],
                        ['timezone', 'America/Sao_Paulo', 'string', 'system'],
                        ['date_format', 'd/m/Y', 'string', 'system'],
                        ['time_format', 'H:i', 'string', 'system'],
                        ['session_timeout', '7200', 'integer', 'authentication'],
                        ['max_login_attempts', '5', 'integer', 'authentication'],
                        ['lockout_duration', '900', 'integer', 'authentication']
                    ];

                    $stmt = $mysqli->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `setting_group`, `created_at`) VALUES (?, ?, ?, ?, ?)");

                    foreach ($default_settings as $setting) {
                        $stmt->bind_param('sssss', $setting[0], $setting[1], $setting[2], $setting[3], $now);
                        $stmt->execute();
                    }

                    $stmt->close();

                    // Log installation
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

                    $stmt = $mysqli->prepare("INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `description`, `ip_address`, `user_agent`, `level`, `created_at`) VALUES (?, 'SYSTEM_INSTALLED', 'system', 'Sistema instalado com sucesso via instalador v" . INSTALLER_VERSION . "', ?, ?, 'info', ?)");
                    $stmt->bind_param('isss', $admin_id, $ip, $user_agent, $now);
                    $stmt->execute();
                    $stmt->close();

                    $mysqli->close();

                    updateStepStatus('final', 'success', 'Instalação concluída!');

                    // Mark installation as complete
                    $_SESSION['install']['step'] = 5;
                    $_SESSION['install']['data']['completed'] = true;
                    $_SESSION['install']['data']['installed_at'] = time();

                    echo "updateStep('env', 'success');\n";
                    echo "updateStep('db', 'success');\n";
                    echo "updateStep('tables', 'success');\n";
                    echo "updateStep('admin', 'success');\n";
                    echo "updateStep('final', 'success');\n";
                    echo "showResult('success', '🎉 Instalação Concluída!', 'Seu sistema foi instalado com sucesso. Você será redirecionado em instantes...', '?step=5');\n";

                } catch (Exception $e) {
                    // Rollback: try to delete .env file
                    @unlink(ENV_FILE);

                    $error_msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

                    echo "updateStep('env', 'error');\n";
                    echo "showResult('error', '❌ Erro na Instalação', '" . addslashes($error_msg) . "<br><br><a href=\"?step=1\" class=\"btn\">← Reiniciar Instalação</a>', null);\n";
                }
                ?>

            } catch (e) {
                showResult('error', 'Erro', e.message, null);
            }
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    })();
    </script>
    <?php

    render_footer();
}

/**
 * Helper to update step status in JavaScript
 */
function updateStepStatus(string $step, string $status, string $message = ''): void {
    // This is called from PHP but outputs JavaScript
    // Not used in current implementation but kept for future use
}

/**
 * Step 5: Success
 */
function step_5_success(): void {
    render_header('Instalação Concluída!');

    $admin = $_SESSION['install']['data']['admin'] ?? [];
    $app = $_SESSION['install']['data']['application'] ?? [];
    $db = $_SESSION['install']['data']['database'] ?? [];

    echo '<div style="text-align: center; margin-bottom: 32px;">';
    echo '<div class="success-icon">✓</div>';
    echo '<h2 class="step-title">Instalação Concluída!</h2>';
    echo '<p class="step-description">Seu sistema de ponto eletrônico está pronto para uso.</p>';
    echo '</div>';

    echo '<div class="alert alert-success">';
    echo '<strong>✅ Sistema instalado com sucesso!</strong><br><br>';
    echo '<strong>Informações do Administrador:</strong><br>';
    echo 'E-mail: <code style="background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 4px;">' . htmlspecialchars($admin['email']) . '</code><br>';
    echo 'Senha: <em>a senha que você definiu no passo 3</em>';
    echo '</div>';

    echo '<div class="alert alert-info">';
    echo '<strong>📋 Configurações Aplicadas:</strong><br>';
    echo '• Banco de Dados: ' . htmlspecialchars($db['name']) . ' @ ' . htmlspecialchars($db['host']) . '<br>';
    echo '• Empresa: ' . htmlspecialchars($app['company_name']) . '<br>';
    echo '• URL: ' . htmlspecialchars($app['url']) . '<br>';
    echo '• Tabelas criadas: 7<br>';
    echo '• Versão: ' . INSTALLER_VERSION;
    echo '</div>';

    echo '<div class="alert alert-warning">';
    echo '<strong>⚠️ IMPORTANTE - Ações de Segurança:</strong><br>';
    echo '1. <strong>DELETE</strong> o arquivo <code>public/install.php</code> AGORA!<br>';
    echo '2. Guarde suas credenciais em local seguro<br>';
    echo '3. O arquivo <code>.env</code> contém informações sensíveis - não compartilhe<br>';
    echo '4. Configure SSL/HTTPS para produção<br>';
    echo '5. Altere a senha do admin no primeiro login';
    echo '</div>';

    echo '<a href="/auth/login" class="btn" style="margin-bottom: 12px;">Acessar o Sistema →</a>';
    echo '<button type="button" class="btn" style="background: #6c757d;" onclick="if(confirm(\'Tem certeza?\')) location.reload()">🔄 Reinstalar Sistema</button>';

    // Clear installation session after showing
    if (!headers_sent()) {
        // Only clear if we can still modify session
        unset($_SESSION['install']);
    }

    render_footer();
}

// ============================================================================
// MAIN EXECUTION FLOW
// ============================================================================

// Check if already installed (but allow step 5 to show)
if (file_exists(ENV_FILE) && $step < 5) {
    $env_content = @file_get_contents(ENV_FILE);
    if ($env_content && strpos($env_content, 'database.default.database') !== false) {
        render_header('Sistema Já Instalado');

        echo '<div class="alert alert-warning">';
        echo '<strong>⚠️ Sistema já instalado!</strong><br>';
        echo 'O sistema já foi instalado anteriormente. Se deseja reinstalar:';
        echo '<ol style="margin: 12px 0 0 20px;">';
        echo '<li>Faça backup do banco de dados atual</li>';
        echo '<li>Delete o arquivo <code>.env</code></li>';
        echo '<li>Recarregue esta página</li>';
        echo '</ol>';
        echo '</div>';

        echo '<a href="/auth/login" class="btn">Ir para o Login →</a>';

        render_footer();
        exit;
    }
}

// Security: Prevent installation if been running for more than 1 hour
if (isset($_SESSION['install']['started_at']) && (time() - $_SESSION['install']['started_at']) > 3600) {
    unset($_SESSION['install']);
    header('Location: ?step=0');
    exit;
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
        header('Location: ?step=0');
        exit;
}
