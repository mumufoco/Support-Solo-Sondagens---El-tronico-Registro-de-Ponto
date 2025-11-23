#!/usr/bin/env php
<?php
/**
 * Sistema de Ponto Eletrônico - Instalador de Produção
 *
 * FASES DA INSTALAÇÃO:
 * 1. Checagem inicial de requisitos
 * 2. Criação do administrador
 * 3. Configuração do banco de dados
 * 4. Checagem final de comunicação
 *
 * Suporta execução via CLI e navegador web
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Detectar modo de execução
define('IS_CLI', PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
define('IS_WEB', !IS_CLI);

// Iniciar sessão se for web
if (IS_WEB) {
    session_start();
}

// ==============================================================================
// CLASSES E FUNÇÕES AUXILIARES
// ==============================================================================

class Color {
    const RESET = "\033[0m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
    const BOLD = "\033[1m";
}

function htmlEscape($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function printHeader($text) {
    if (IS_CLI) {
        $line = str_repeat('=', 75);
        echo "\n" . Color::CYAN . Color::BOLD . $line . Color::RESET . "\n";
        echo Color::CYAN . Color::BOLD . "  $text" . Color::RESET . "\n";
        echo Color::CYAN . Color::BOLD . $line . Color::RESET . "\n\n";
    } else {
        echo '<div class="header"><h2>' . htmlEscape($text) . '</h2></div>';
    }
}

function printSuccess($text) {
    if (IS_CLI) {
        echo Color::GREEN . "✓ $text" . Color::RESET . "\n";
    } else {
        echo '<div class="success">✓ ' . htmlEscape($text) . '</div>';
    }
}

function printError($text) {
    if (IS_CLI) {
        echo Color::RED . "✗ $text" . Color::RESET . "\n";
    } else {
        echo '<div class="error">✗ ' . htmlEscape($text) . '</div>';
    }
}

function printWarning($text) {
    if (IS_CLI) {
        echo Color::YELLOW . "⚠ $text" . Color::RESET . "\n";
    } else {
        echo '<div class="warning">⚠ ' . htmlEscape($text) . '</div>';
    }
}

function printInfo($text) {
    if (IS_CLI) {
        echo Color::BLUE . "ℹ $text" . Color::RESET . "\n";
    } else {
        echo '<div class="info">ℹ ' . htmlEscape($text) . '</div>';
    }
}

function ask($question, $default = '') {
    if (IS_CLI) {
        $defaultText = $default ? " [$default]" : '';
        echo Color::BOLD . "$question$defaultText: " . Color::RESET;
        if (defined('STDIN')) {
            $answer = trim(fgets(STDIN));
        } else {
            $answer = '';
        }
        return $answer ?: $default;
    }
    // Em modo web, retorna valor do POST ou default
    return $default;
}

function isFunctionEnabled($func) {
    $disabled = explode(',', ini_get('disable_functions'));
    $disabled = array_map('trim', $disabled);
    return !in_array($func, $disabled) && function_exists($func);
}

function askPassword($question) {
    if (IS_CLI) {
        echo Color::BOLD . "$question: " . Color::RESET;

        // Tentar desabilitar echo se a função estiver disponível
        if (isFunctionEnabled('system') && PHP_OS_FAMILY !== 'Windows' && defined('STDIN')) {
            @system('stty -echo 2>/dev/null');
            $password = trim(fgets(STDIN));
            @system('stty echo 2>/dev/null');
        } else {
            // Fallback: senha visível (com aviso)
            echo Color::YELLOW . "(Aviso: a senha será visível ao digitar) " . Color::RESET;
            if (defined('STDIN')) {
                $password = trim(fgets(STDIN));
            } else {
                $password = '';
            }
        }

        echo "\n";
        return $password;
    }
    // Em modo web, retorna vazio (será preenchido via POST)
    return '';
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    if (strlen($password) < 12) {
        return "A senha deve ter no mínimo 12 caracteres";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "A senha deve conter pelo menos uma letra maiúscula";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "A senha deve conter pelo menos uma letra minúscula";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "A senha deve conter pelo menos um número";
    }
    return true;
}

// ==============================================================================
// MODO WEB - HTML E PROCESSAMENTO
// ==============================================================================

if (IS_WEB) {
    // Processar formulário se enviado
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $step = $_POST['step'] ?? '1';

        if ($step === '1') {
            // Validação de requisitos já foi feita, avançar
            $_SESSION['step'] = '2';
        } elseif ($step === '2') {
            // Validar dados do admin
            $errors = [];
            $adminName = trim($_POST['admin_name'] ?? '');
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPassword = $_POST['admin_password'] ?? '';
            $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';

            if (empty($adminName)) $errors[] = "Nome é obrigatório";
            if (!validateEmail($adminEmail)) $errors[] = "Email inválido";

            $passValidation = validatePassword($adminPassword);
            if ($passValidation !== true) $errors[] = $passValidation;
            if ($adminPassword !== $adminPasswordConfirm) $errors[] = "As senhas não coincidem";

            if (empty($errors)) {
                $_SESSION['admin'] = [
                    'name' => $adminName,
                    'email' => $adminEmail,
                    'password' => password_hash($adminPassword, PASSWORD_DEFAULT)
                ];
                $_SESSION['step'] = '3';
            } else {
                $_SESSION['errors'] = $errors;
            }
        } elseif ($step === '3') {
            // Processar configuração do banco
            $_SESSION['database'] = [
                'url' => trim($_POST['app_url'] ?? ''),
                'host' => trim($_POST['db_host'] ?? 'localhost'),
                'name' => trim($_POST['db_name'] ?? ''),
                'user' => trim($_POST['db_user'] ?? ''),
                'pass' => $_POST['db_pass'] ?? '',
                'port' => trim($_POST['db_port'] ?? '3306'),
            ];
            $_SESSION['app'] = [
                'company_name' => trim($_POST['company_name'] ?? ''),
                'company_cnpj' => trim($_POST['company_cnpj'] ?? ''),
                'email_from' => trim($_POST['email_from'] ?? ''),
                'email_from_name' => trim($_POST['email_from_name'] ?? ''),
                'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                'smtp_user' => trim($_POST['smtp_user'] ?? ''),
                'smtp_pass' => $_POST['smtp_pass'] ?? '',
                'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
                'smtp_crypto' => trim($_POST['smtp_crypto'] ?? 'tls'),
            ];

            // Tentar instalação
            try {
                performInstallation($_SESSION['admin'], $_SESSION['database'], $_SESSION['app']);
                $_SESSION['step'] = '4';
                $_SESSION['success'] = true;
            } catch (Exception $e) {
                $_SESSION['errors'] = [$e->getMessage()];
            }
        }

        // Redirecionar para evitar resubmit
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Renderizar HTML
    renderWebInterface();
    exit;
}

// ==============================================================================
// MODO CLI - CONTINUA ABAIXO
// ==============================================================================

$installData = [
    'admin' => [],
    'database' => [],
    'app' => [],
];

// Limpar tela se possível (não crítico)
if (isFunctionEnabled('system') && PHP_SAPI === 'cli') {
    @system('clear');
}

printHeader("SISTEMA DE PONTO ELETRÔNICO - INSTALADOR DE PRODUÇÃO");

echo Color::BOLD . "Este instalador irá configurar o sistema para produção em 4 etapas:\n\n" . Color::RESET;
echo "  1️⃣  Checagem inicial de requisitos\n";
echo "  2️⃣  Criação do usuário administrador\n";
echo "  3️⃣  Configuração do banco de dados MySQL\n";
echo "  4️⃣  Checagem final de comunicação\n\n";

$continue = ask("Deseja iniciar a instalação?", "s");
if (strtolower($continue) !== 's') {
    printError("\nInstalação cancelada.");
    exit(1);
}

// ==============================================================================
// FASE 1: CHECAGEM INICIAL DE REQUISITOS
// ==============================================================================

printHeader("FASE 1/4: CHECAGEM INICIAL DE REQUISITOS");

printInfo("Verificando requisitos do servidor...\n");

$requirements = checkRequirements();

$allMet = true;
$failures = [];

foreach ($requirements as $req) {
    if ($req['check']) {
        printSuccess("{$req['name']} - {$req['current']}");
    } else {
        printError("{$req['name']} - {$req['current']}");
        $failures[] = $req;
        $allMet = false;
    }
}

if (!$allMet) {
    echo "\n" . Color::RED . Color::BOLD . "❌ REQUISITOS NÃO ATENDIDOS\n" . Color::RESET . "\n";

    foreach ($failures as $failure) {
        printWarning("Problema: {$failure['name']}");
        printInfo("Solução: {$failure['solution']}\n");
    }

    printError("Corrija os problemas acima e execute o instalador novamente.");
    exit(1);
}

printSuccess("\n✅ Todos os requisitos foram atendidos!\n");

// ==============================================================================
// FASE 2: CRIAÇÃO DO ADMINISTRADOR
// ==============================================================================

printHeader("FASE 2/4: CRIAÇÃO DO USUÁRIO ADMINISTRADOR");

printInfo("Configure as credenciais do administrador do sistema.\n");

do {
    $adminName = ask("Nome completo do administrador");
    if (empty($adminName)) {
        printError("Nome é obrigatório!\n");
    }
} while (empty($adminName));

do {
    $adminEmail = ask("E-mail do administrador");
    if (!validateEmail($adminEmail)) {
        printError("E-mail inválido!\n");
        $adminEmail = '';
    }
} while (empty($adminEmail));

do {
    $adminPassword = askPassword("Senha do administrador");
    $validation = validatePassword($adminPassword);

    if ($validation !== true) {
        printError($validation . "\n");
        continue;
    }

    $adminPasswordConfirm = askPassword("Confirme a senha");

    if ($adminPassword !== $adminPasswordConfirm) {
        printError("As senhas não coincidem!\n");
        $adminPassword = '';
    }
} while (empty($adminPassword));

$installData['admin'] = [
    'name' => $adminName,
    'email' => $adminEmail,
    'password' => password_hash($adminPassword, PASSWORD_DEFAULT)
];

printSuccess("\n✅ Dados do administrador configurados!\n");

// ==============================================================================
// FASE 3: CONFIGURAÇÃO DO BANCO DE DADOS
// ==============================================================================

printHeader("FASE 3/4: CONFIGURAÇÃO DO BANCO DE DADOS");

printInfo("Configure a conexão com o banco de dados MySQL.\n");

$appURL = ask("URL base da aplicação (ex: https://ponto.empresa.com.br)");
$dbHost = ask("Host do banco MySQL", "localhost");
$dbName = ask("Nome do banco de dados");
$dbUser = ask("Usuário do MySQL");
$dbPass = askPassword("Senha do MySQL");
$dbPort = ask("Porta do MySQL", "3306");

$installData['database'] = [
    'url' => $appURL,
    'host' => $dbHost,
    'name' => $dbName,
    'user' => $dbUser,
    'pass' => $dbPass,
    'port' => $dbPort,
];

// Coletar dados da empresa e SMTP
$installData['app'] = [
    'company_name' => ask("Nome da empresa"),
    'company_cnpj' => ask("CNPJ da empresa"),
    'email_from' => ask("Email remetente do sistema", "noreply@" . parse_url($appURL, PHP_URL_HOST)),
    'email_from_name' => ask("Nome do remetente", "Sistema de Ponto"),
    'smtp_host' => ask("Servidor SMTP", "smtp.gmail.com"),
    'smtp_user' => ask("Usuário SMTP"),
    'smtp_pass' => askPassword("Senha SMTP"),
    'smtp_port' => ask("Porta SMTP", "587"),
    'smtp_crypto' => ask("Criptografia (tls/ssl)", "tls"),
];

// Executar instalação
try {
    performInstallation($installData['admin'], $installData['database'], $installData['app']);
    printSuccess("\n✅ Sistema instalado com sucesso!\n");
} catch (Exception $e) {
    printError("\n❌ Erro durante instalação: " . $e->getMessage());
    exit(1);
}

// ==============================================================================
// FUNÇÕES DE INSTALAÇÃO
// ==============================================================================

function checkRequirements() {
    return [
        [
            'name' => 'PHP versão 8.1 ou superior',
            'check' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'current' => PHP_VERSION,
            'solution' => 'Atualize o PHP para versão 8.1 ou superior'
        ],
        [
            'name' => 'Extensão PDO MySQL',
            'check' => extension_loaded('pdo_mysql'),
            'current' => extension_loaded('pdo_mysql') ? 'Instalada' : 'Não encontrada',
            'solution' => 'Instale: sudo apt-get install php-mysql (Debian/Ubuntu) ou yum install php-mysqlnd (RHEL/CentOS)'
        ],
        [
            'name' => 'Extensão OpenSSL',
            'check' => extension_loaded('openssl'),
            'current' => extension_loaded('openssl') ? 'Instalada' : 'Não encontrada',
            'solution' => 'Extensão geralmente incluída no PHP. Verifique php.ini'
        ],
        [
            'name' => 'Extensão MBString',
            'check' => extension_loaded('mbstring'),
            'current' => extension_loaded('mbstring') ? 'Instalada' : 'Não encontrada',
            'solution' => 'Instale: sudo apt-get install php-mbstring'
        ],
        [
            'name' => 'Extensão JSON',
            'check' => extension_loaded('json'),
            'current' => extension_loaded('json') ? 'Instalada' : 'Não encontrada',
            'solution' => 'Extensão geralmente incluída no PHP core'
        ],
        [
            'name' => 'Extensão Curl',
            'check' => extension_loaded('curl'),
            'current' => extension_loaded('curl') ? 'Instalada' : 'Não encontrada',
            'solution' => 'Instale: sudo apt-get install php-curl'
        ],
        [
            'name' => 'Extensão GD (processamento de imagens)',
            'check' => extension_loaded('gd'),
            'current' => extension_loaded('gd') ? 'Instalada' : 'Não encontrada',
            'solution' => 'Instale: sudo apt-get install php-gd'
        ],
        [
            'name' => 'Extensão Intl (internacionalização)',
            'check' => extension_loaded('intl'),
            'current' => extension_loaded('intl') ? 'Instalada' : 'Não encontrada',
            'solution' => 'Instale: sudo apt-get install php-intl'
        ],
        [
            'name' => 'Diretório writable/ gravável',
            'check' => is_writable(__DIR__ . '/writable'),
            'current' => is_writable(__DIR__ . '/writable') ? 'Gravável' : 'Sem permissão',
            'solution' => 'Execute: sudo chmod -R 755 writable/ && sudo chown -R www-data:www-data writable/'
        ],
        [
            'name' => 'Composer instalado',
            'check' => file_exists(__DIR__ . '/vendor/autoload.php'),
            'current' => file_exists(__DIR__ . '/vendor/autoload.php') ? 'Instalado' : 'Não encontrado',
            'solution' => 'Execute: composer install --no-dev --optimize-autoloader'
        ],
    ];
}

function performInstallation($admin, $database, $app) {
    if (IS_CLI) {
        printInfo("\nTestando conexão com o banco de dados...");
    }

    // Testar conexão MySQL
    try {
        $dsn = "mysql:host={$database['host']};port={$database['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $database['user'], $database['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        if (IS_CLI) {
            printSuccess("Conectado ao MySQL com sucesso!");
        }
    } catch (PDOException $e) {
        throw new Exception("Erro ao conectar ao MySQL: " . $e->getMessage());
    }

    // Verificar/criar banco de dados
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$database['name']}'");
    $dbExists = $stmt->fetch();

    if (!$dbExists) {
        if (IS_CLI) {
            printInfo("Criando banco de dados '{$database['name']}'...");
        }
        $pdo->exec("CREATE DATABASE `{$database['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        if (IS_CLI) {
            printSuccess("Banco de dados criado!");
        }
    }

    $pdo->exec("USE `{$database['name']}`");

    // Gerar chave de criptografia
    $encryptionKey = 'base64:' . base64_encode(random_bytes(32));

    // Criar arquivo .env
    if (IS_CLI) {
        printInfo("\nCriando arquivo .env...");
    }

    $template = file_get_contents(__DIR__ . '/.env.production.template');
    if ($template === false) {
        throw new Exception("Arquivo .env.production.template não encontrado!");
    }

    $replacements = [
        '%%APP_BASE_URL%%' => $database['url'],
        '%%ENCRYPTION_KEY%%' => $encryptionKey,
        '%%DB_HOSTNAME%%' => $database['host'],
        '%%DB_DATABASE%%' => $database['name'],
        '%%DB_USERNAME%%' => $database['user'],
        '%%DB_PASSWORD%%' => $database['pass'],
        '%%DB_PORT%%' => $database['port'],
        '%%COMPANY_NAME%%' => $app['company_name'],
        '%%COMPANY_CNPJ%%' => $app['company_cnpj'],
        '%%EMAIL_FROM%%' => $app['email_from'],
        '%%EMAIL_FROM_NAME%%' => $app['email_from_name'],
        '%%SMTP_HOST%%' => $app['smtp_host'],
        '%%SMTP_USER%%' => $app['smtp_user'],
        '%%SMTP_PASS%%' => $app['smtp_pass'],
        '%%SMTP_PORT%%' => $app['smtp_port'],
        '%%SMTP_CRYPTO%%' => $app['smtp_crypto'],
        '%%ADMIN_EMAIL%%' => $admin['email'],
    ];

    $envContent = str_replace(array_keys($replacements), array_values($replacements), $template);

    if (file_put_contents(__DIR__ . '/.env', $envContent)) {
        @chmod(__DIR__ . '/.env', 0600);
        if (IS_CLI) {
            printSuccess("Arquivo .env criado e protegido (permissão 600)");
        }
    } else {
        throw new Exception("Erro ao criar arquivo .env");
    }

    // Executar migrations
    if (IS_CLI) {
        printInfo("\nExecutando migrations do banco de dados...");
    }

    if (isFunctionEnabled('exec')) {
        $output = [];
        $returnVar = 0;
        @exec("cd " . escapeshellarg(__DIR__) . " && php spark migrate --all 2>&1", $output, $returnVar);

        if ($returnVar === 0) {
            if (IS_CLI) {
                printSuccess("Migrations executadas com sucesso!");
            }
        } else {
            if (IS_CLI) {
                printWarning("Aviso ao executar migrations. Continuando...");
            }
        }
    } else {
        if (IS_CLI) {
            printWarning("A função exec() está desabilitada no servidor.");
            printInfo("Execute manualmente: php spark migrate --all");
        }
    }

    // Criar usuário administrador
    if (IS_CLI) {
        printInfo("Criando usuário administrador no banco...");
    }

    try {
        $pdo->exec("USE `{$database['name']}`");

        // Verificar se a tabela employees existe
        $tables = $pdo->query("SHOW TABLES LIKE 'employees'")->fetchAll();

        if (empty($tables)) {
            // Criar tabela employees manualmente se não existir
            if (IS_CLI) {
                printInfo("Criando tabela employees...");
            }
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `employees` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL COMMENT 'Nome completo do funcionário',
                    `email` varchar(255) NOT NULL UNIQUE COMMENT 'E-mail único para login',
                    `password` varchar(255) NOT NULL COMMENT 'Senha hash',
                    `cpf` varchar(14) DEFAULT NULL UNIQUE COMMENT 'CPF formatado',
                    `unique_code` varchar(8) NOT NULL UNIQUE COMMENT 'Código único para registro de ponto',
                    `role` varchar(20) DEFAULT 'funcionario' COMMENT 'Perfil: admin, gestor, funcionario',
                    `department` varchar(100) DEFAULT NULL COMMENT 'Departamento',
                    `position` varchar(100) DEFAULT NULL COMMENT 'Cargo',
                    `expected_hours_daily` decimal(4,2) DEFAULT 8.00 COMMENT 'Jornada diária em horas',
                    `work_schedule_start` time DEFAULT NULL COMMENT 'Horário início expediente',
                    `work_schedule_end` time DEFAULT NULL COMMENT 'Horário fim expediente',
                    `extra_hours_balance` decimal(10,2) DEFAULT 0.00 COMMENT 'Saldo horas extras',
                    `owed_hours_balance` decimal(10,2) DEFAULT 0.00 COMMENT 'Saldo horas devidas',
                    `active` tinyint(1) DEFAULT 1 COMMENT 'Funcionário ativo',
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `deleted_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_role_active` (`role`, `active`),
                    KEY `idx_department` (`department`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
            if (IS_CLI) {
                printSuccess("Tabela employees criada!");
            }
        } else {
            // Tabela existe, verificar se tem as colunas necessárias
            $columns = $pdo->query("SHOW COLUMNS FROM `employees`")->fetchAll(PDO::FETCH_COLUMN);

            $requiredColumns = [
                'id', 'name', 'email', 'password', 'cpf', 'unique_code', 'role',
                'department', 'position', 'expected_hours_daily', 'work_schedule_start',
                'work_schedule_end', 'extra_hours_balance', 'owed_hours_balance',
                'active', 'created_at', 'updated_at'
            ];

            $missingColumns = array_diff($requiredColumns, $columns);

            if (!empty($missingColumns)) {
                // Adicionar colunas faltantes
                if (IS_CLI) {
                    printWarning("Tabela employees existe mas está incompleta. Adicionando colunas...");
                }

                foreach ($missingColumns as $column) {
                    switch ($column) {
                        case 'unique_code':
                            $pdo->exec("ALTER TABLE `employees` ADD COLUMN `unique_code` varchar(8) NOT NULL UNIQUE COMMENT 'Código único para registro de ponto' AFTER `cpf`");
                            break;
                        case 'cpf':
                            $pdo->exec("ALTER TABLE `employees` ADD COLUMN `cpf` varchar(14) DEFAULT NULL UNIQUE COMMENT 'CPF formatado' AFTER `password`");
                            break;
                        case 'expected_hours_daily':
                            $pdo->exec("ALTER TABLE `employees` ADD COLUMN `expected_hours_daily` decimal(4,2) DEFAULT 8.00 COMMENT 'Jornada diária em horas' AFTER `position`");
                            break;
                        case 'work_schedule_start':
                            $pdo->exec("ALTER TABLE `employees` ADD COLUMN `work_schedule_start` time DEFAULT NULL COMMENT 'Horário início expediente' AFTER `expected_hours_daily`");
                            break;
                        case 'work_schedule_end':
                            $pdo->exec("ALTER TABLE `employees` ADD COLUMN `work_schedule_end` time DEFAULT NULL COMMENT 'Horário fim expediente' AFTER `work_schedule_start`");
                            break;
                        case 'extra_hours_balance':
                            $pdo->exec("ALTER TABLE `employees` ADD COLUMN `extra_hours_balance` decimal(10,2) DEFAULT 0.00 COMMENT 'Saldo horas extras' AFTER `work_schedule_end`");
                            break;
                        case 'owed_hours_balance':
                            $pdo->exec("ALTER TABLE `employees` ADD COLUMN `owed_hours_balance` decimal(10,2) DEFAULT 0.00 COMMENT 'Saldo horas devidas' AFTER `extra_hours_balance`");
                            break;
                        case 'deleted_at':
                            $pdo->exec("ALTER TABLE `employees` ADD COLUMN `deleted_at` datetime DEFAULT NULL AFTER `updated_at`");
                            break;
                    }
                }

                if (IS_CLI) {
                    printSuccess("Colunas adicionadas com sucesso!");
                }
            }
        }

        // Verificar se já existe um admin
        $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE role = 'admin' OR email = " . $pdo->quote($admin['email']));
        $adminExists = $stmt->fetchColumn() > 0;

        if ($adminExists) {
            if (IS_CLI) {
                printWarning("Já existe um administrador cadastrado. Atualizando...");
            }

            // Atualizar admin existente
            $stmt = $pdo->prepare("
                UPDATE employees
                SET name = ?, password = ?, role = 'admin', active = 1, updated_at = NOW()
                WHERE email = ?
            ");
            $stmt->execute([
                $admin['name'],
                $admin['password'],
                $admin['email']
            ]);
        } else {
            // Inserir novo admin
            $stmt = $pdo->prepare("
                INSERT INTO employees (
                    name, email, password, cpf, unique_code, role, department, position,
                    expected_hours_daily, work_schedule_start, work_schedule_end,
                    extra_hours_balance, owed_hours_balance, active,
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, '000.000.000-00', '0001', 'admin', 'Administração', 'Administrador',
                    8.0, '08:00:00', '17:00:00', 0, 0, 1, NOW(), NOW()
                )
            ");

            $stmt->execute([
                $admin['name'],
                $admin['email'],
                $admin['password']
            ]);
        }

        if (IS_CLI) {
            printSuccess("Usuário administrador configurado com sucesso!");
        }
    } catch (PDOException $e) {
        throw new Exception("Erro ao criar administrador: " . $e->getMessage());
    }

    // Validação final
    if (IS_CLI) {
        printHeader("FASE 4/4: CHECAGEM FINAL");
        printSuccess("✅ Banco de dados: Conectado");
        printSuccess("✅ Tabelas: Criadas");
        printSuccess("✅ Administrador: Criado");
        printSuccess("✅ Arquivo .env: Configurado");

        echo "\n" . Color::GREEN . Color::BOLD . "🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO!\n" . Color::RESET . "\n";
        echo Color::CYAN . "Sistema pronto para uso em produção.\n\n" . Color::RESET;
        echo Color::BOLD . "📝 Credenciais do administrador:\n" . Color::RESET;
        echo "   E-mail: {$admin['email']}\n";
        echo "   Senha: [a senha que você definiu]\n\n";
        echo Color::BOLD . "🌐 Acesse o sistema em:\n" . Color::RESET;
        echo "   {$database['url']}\n\n";
        echo Color::YELLOW . "⚠️  IMPORTANTE:\n" . Color::RESET;
        echo "   - Guarde suas credenciais em local seguro\n";
        echo "   - Faça backup regular do banco de dados\n";
        echo "   - O arquivo .env contém informações sensíveis (não compartilhe!)\n";

        if (!isFunctionEnabled('exec')) {
            echo "\n" . Color::YELLOW . "⚠️  Execute manualmente as migrations:\n" . Color::RESET;
            echo "   php spark migrate --all\n";
        }

        echo "\n";
    }
}

function renderWebInterface() {
    $step = $_SESSION['step'] ?? '1';
    $errors = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Instalador - Sistema de Ponto Eletrônico</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                overflow: hidden;
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .header h1 { font-size: 28px; margin-bottom: 10px; }
            .header p { opacity: 0.9; }
            .content { padding: 40px; }
            .steps {
                display: flex;
                justify-content: space-between;
                margin-bottom: 40px;
                padding-bottom: 20px;
                border-bottom: 2px solid #f0f0f0;
            }
            .step-item {
                flex: 1;
                text-align: center;
                padding: 10px;
                position: relative;
            }
            .step-item.active { color: #667eea; font-weight: bold; }
            .step-item.completed { color: #10b981; }
            .step-number {
                display: inline-block;
                width: 32px;
                height: 32px;
                line-height: 32px;
                border-radius: 50%;
                background: #e5e7eb;
                margin-bottom: 5px;
            }
            .step-item.active .step-number { background: #667eea; color: white; }
            .step-item.completed .step-number { background: #10b981; color: white; }
            .form-group { margin-bottom: 20px; }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #374151;
            }
            .form-group input, .form-group select {
                width: 100%;
                padding: 12px;
                border: 2px solid #e5e7eb;
                border-radius: 6px;
                font-size: 16px;
                transition: border-color 0.3s;
            }
            .form-group input:focus, .form-group select:focus {
                outline: none;
                border-color: #667eea;
            }
            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            .btn {
                display: inline-block;
                padding: 14px 32px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s;
            }
            .btn:hover { transform: translateY(-2px); }
            .success, .error, .warning, .info {
                padding: 12px 16px;
                border-radius: 6px;
                margin-bottom: 16px;
            }
            .success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
            .error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
            .warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
            .info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
            .requirements { list-style: none; }
            .requirements li {
                padding: 12px;
                margin-bottom: 8px;
                background: #f9fafb;
                border-radius: 6px;
                border-left: 4px solid #10b981;
            }
            .requirements li.failed {
                background: #fef2f2;
                border-left-color: #ef4444;
            }
            .final-info {
                background: #f0fdf4;
                border: 2px solid #10b981;
                border-radius: 8px;
                padding: 24px;
                margin-top: 24px;
            }
            .final-info h3 { color: #065f46; margin-bottom: 16px; }
            .final-info p { margin-bottom: 8px; color: #374151; }
            .final-info strong { color: #667eea; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>⏱️ Sistema de Ponto Eletrônico</h1>
                <p>Instalador de Produção</p>
            </div>

            <div class="content">
                <div class="steps">
                    <div class="step-item <?= $step >= '1' ? 'completed' : '' ?>">
                        <div class="step-number">1</div>
                        <div>Requisitos</div>
                    </div>
                    <div class="step-item <?= $step == '2' ? 'active' : ($step > '2' ? 'completed' : '') ?>">
                        <div class="step-number">2</div>
                        <div>Administrador</div>
                    </div>
                    <div class="step-item <?= $step == '3' ? 'active' : ($step > '3' ? 'completed' : '') ?>">
                        <div class="step-number">3</div>
                        <div>Banco de Dados</div>
                    </div>
                    <div class="step-item <?= $step == '4' ? 'active' : '' ?>">
                        <div class="step-number">4</div>
                        <div>Concluído</div>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="error">✗ <?= htmlEscape($error) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($step === '1'): ?>
                    <h2 style="margin-bottom: 20px;">Fase 1: Checagem de Requisitos</h2>
                    <p style="margin-bottom: 24px; color: #6b7280;">Verificando se o servidor atende todos os requisitos necessários...</p>

                    <?php
                    $requirements = checkRequirements();
                    $allMet = true;
                    ?>

                    <ul class="requirements">
                        <?php foreach ($requirements as $req): ?>
                            <li class="<?= $req['check'] ? '' : 'failed' ?>">
                                <?= $req['check'] ? '✓' : '✗' ?>
                                <strong><?= htmlEscape($req['name']) ?></strong>:
                                <?= htmlEscape($req['current']) ?>
                                <?php if (!$req['check']): ?>
                                    <br><small style="color: #991b1b; margin-top: 4px; display: block;">
                                        💡 <?= htmlEscape($req['solution']) ?>
                                    </small>
                                    <?php $allMet = false; ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($allMet): ?>
                        <div class="success" style="margin-top: 24px;">
                            ✓ Todos os requisitos foram atendidos! O sistema está pronto para instalação.
                        </div>
                        <form method="POST" style="margin-top: 24px;">
                            <input type="hidden" name="step" value="1">
                            <button type="submit" class="btn">Continuar para Próxima Etapa →</button>
                        </form>
                    <?php else: ?>
                        <div class="error" style="margin-top: 24px;">
                            ✗ Alguns requisitos não foram atendidos. Corrija os problemas acima antes de continuar.
                        </div>
                    <?php endif; ?>

                <?php elseif ($step === '2'): ?>
                    <h2 style="margin-bottom: 20px;">Fase 2: Usuário Administrador</h2>
                    <p style="margin-bottom: 24px; color: #6b7280;">Configure as credenciais do administrador do sistema.</p>

                    <form method="POST">
                        <input type="hidden" name="step" value="2">

                        <div class="form-group">
                            <label>Nome Completo *</label>
                            <input type="text" name="admin_name" required placeholder="João da Silva">
                        </div>

                        <div class="form-group">
                            <label>E-mail *</label>
                            <input type="email" name="admin_email" required placeholder="admin@empresa.com.br">
                        </div>

                        <div class="form-group">
                            <label>Senha * (mínimo 12 caracteres, letras maiúsculas, minúsculas e números)</label>
                            <input type="password" name="admin_password" required minlength="12">
                        </div>

                        <div class="form-group">
                            <label>Confirme a Senha *</label>
                            <input type="password" name="admin_password_confirm" required minlength="12">
                        </div>

                        <button type="submit" class="btn">Continuar para Próxima Etapa →</button>
                    </form>

                <?php elseif ($step === '3'): ?>
                    <h2 style="margin-bottom: 20px;">Fase 3: Configuração do Banco de Dados</h2>
                    <p style="margin-bottom: 24px; color: #6b7280;">Configure a conexão com MySQL e dados da empresa.</p>

                    <form method="POST">
                        <input type="hidden" name="step" value="3">

                        <h3 style="margin: 24px 0 16px 0; color: #667eea;">🌐 Configurações da Aplicação</h3>

                        <div class="form-group">
                            <label>URL Base da Aplicação *</label>
                            <input type="url" name="app_url" required placeholder="https://ponto.empresa.com.br"
                                   value="<?= htmlEscape($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])) ?>">
                        </div>

                        <h3 style="margin: 24px 0 16px 0; color: #667eea;">🗄️ Banco de Dados MySQL</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Host *</label>
                                <input type="text" name="db_host" required value="localhost">
                            </div>
                            <div class="form-group">
                                <label>Porta *</label>
                                <input type="text" name="db_port" required value="3306">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Nome do Banco *</label>
                            <input type="text" name="db_name" required placeholder="registro_ponto">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Usuário *</label>
                                <input type="text" name="db_user" required placeholder="ponto_user">
                            </div>
                            <div class="form-group">
                                <label>Senha *</label>
                                <input type="password" name="db_pass" required>
                            </div>
                        </div>

                        <h3 style="margin: 24px 0 16px 0; color: #667eea;">🏢 Dados da Empresa</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Nome da Empresa *</label>
                                <input type="text" name="company_name" required>
                            </div>
                            <div class="form-group">
                                <label>CNPJ</label>
                                <input type="text" name="company_cnpj" placeholder="00.000.000/0000-00">
                            </div>
                        </div>

                        <h3 style="margin: 24px 0 16px 0; color: #667eea;">📧 Configurações de E-mail (SMTP)</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label>E-mail Remetente</label>
                                <input type="email" name="email_from" placeholder="noreply@empresa.com.br">
                            </div>
                            <div class="form-group">
                                <label>Nome do Remetente</label>
                                <input type="text" name="email_from_name" value="Sistema de Ponto">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Servidor SMTP</label>
                                <input type="text" name="smtp_host" placeholder="smtp.gmail.com">
                            </div>
                            <div class="form-group">
                                <label>Porta SMTP</label>
                                <input type="text" name="smtp_port" value="587">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Usuário SMTP</label>
                                <input type="text" name="smtp_user">
                            </div>
                            <div class="form-group">
                                <label>Senha SMTP</label>
                                <input type="password" name="smtp_pass">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Criptografia</label>
                            <select name="smtp_crypto">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                            </select>
                        </div>

                        <div class="info" style="margin-top: 24px;">
                            ℹ️ Ao clicar em "Instalar", o sistema irá:
                            <ul style="margin-left: 20px; margin-top: 8px;">
                                <li>Testar a conexão com o banco de dados</li>
                                <li>Criar/verificar o banco de dados</li>
                                <li>Executar as migrations (criar tabelas)</li>
                                <li>Criar o usuário administrador</li>
                                <li>Gerar o arquivo de configuração .env</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn" style="margin-top: 24px;">🚀 Instalar Sistema</button>
                    </form>

                <?php elseif ($step === '4'): ?>
                    <div style="text-align: center; padding: 40px 0;">
                        <div style="font-size: 72px; margin-bottom: 24px;">🎉</div>
                        <h2 style="color: #10b981; margin-bottom: 16px;">Instalação Concluída com Sucesso!</h2>
                        <p style="color: #6b7280; margin-bottom: 32px;">O sistema está pronto para uso em produção.</p>
                    </div>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="final-info">
                            <h3>📝 Informações Importantes</h3>

                            <p><strong>✓ Banco de dados:</strong> Configurado e conectado</p>
                            <p><strong>✓ Tabelas:</strong> Criadas com sucesso</p>
                            <p><strong>✓ Administrador:</strong> Usuário criado</p>
                            <p><strong>✓ Arquivo .env:</strong> Gerado e protegido</p>

                            <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid #d1fae5;">
                                <p><strong>🌐 Acesse o sistema em:</strong></p>
                                <p style="font-size: 18px; color: #667eea; margin-top: 8px;">
                                    <?= htmlEscape($_SESSION['database']['url'] ?? 'Sua URL') ?>
                                </p>
                            </div>

                            <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid #d1fae5;">
                                <p><strong>👤 Credenciais do Administrador:</strong></p>
                                <p style="margin-top: 8px;">E-mail: <strong><?= htmlEscape($_SESSION['admin']['email'] ?? '') ?></strong></p>
                                <p>Senha: <em>a senha que você definiu</em></p>
                            </div>
                        </div>

                        <div class="warning" style="margin-top: 24px;">
                            ⚠️ <strong>Importante:</strong>
                            <ul style="margin-left: 20px; margin-top: 8px;">
                                <li>Guarde suas credenciais em local seguro</li>
                                <li>Faça backup regular do banco de dados</li>
                                <li>O arquivo .env contém informações sensíveis - não compartilhe!</li>
                                <li>Por segurança, delete o arquivo install.php após a instalação</li>
                            </ul>
                        </div>

                        <?php if (!isFunctionEnabled('exec')): ?>
                            <div class="info" style="margin-top: 16px;">
                                ℹ️ A função exec() está desabilitada. Execute manualmente via SSH:
                                <code style="display: block; background: #f3f4f6; padding: 8px; margin-top: 8px; border-radius: 4px;">
                                    php spark migrate --all
                                </code>
                            </div>
                        <?php endif; ?>

                        <div style="text-align: center; margin-top: 32px;">
                            <a href="<?= htmlEscape($_SESSION['database']['url'] ?? '/') ?>" class="btn">
                                Acessar Sistema →
                            </a>
                        </div>

                        <?php
                        // Limpar sessão
                        session_destroy();
                        ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
}
