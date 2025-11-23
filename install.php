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
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

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

function printHeader($text) {
    $line = str_repeat('=', 75);
    echo "\n" . Color::CYAN . Color::BOLD . $line . Color::RESET . "\n";
    echo Color::CYAN . Color::BOLD . "  $text" . Color::RESET . "\n";
    echo Color::CYAN . Color::BOLD . $line . Color::RESET . "\n\n";
}

function printSuccess($text) {
    echo Color::GREEN . "✓ $text" . Color::RESET . "\n";
}

function printError($text) {
    echo Color::RED . "✗ $text" . Color::RESET . "\n";
}

function printWarning($text) {
    echo Color::YELLOW . "⚠ $text" . Color::RESET . "\n";
}

function printInfo($text) {
    echo Color::BLUE . "ℹ $text" . Color::RESET . "\n";
}

function ask($question, $default = '') {
    $defaultText = $default ? " [$default]" : '';
    echo Color::BOLD . "$question$defaultText: " . Color::RESET;
    $answer = trim(fgets(STDIN));
    return $answer ?: $default;
}

function isFunctionEnabled($func) {
    $disabled = explode(',', ini_get('disable_functions'));
    $disabled = array_map('trim', $disabled);
    return !in_array($func, $disabled) && function_exists($func);
}

function askPassword($question) {
    echo Color::BOLD . "$question: " . Color::RESET;

    // Tentar desabilitar echo se a função estiver disponível
    if (isFunctionEnabled('system') && PHP_OS_FAMILY !== 'Windows') {
        @system('stty -echo 2>/dev/null');
        $password = trim(fgets(STDIN));
        @system('stty echo 2>/dev/null');
    } else {
        // Fallback: senha visível (com aviso)
        echo Color::YELLOW . "(Aviso: a senha será visível ao digitar) " . Color::RESET;
        $password = trim(fgets(STDIN));
    }

    echo "\n";
    return $password;
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
// VARIÁVEIS GLOBAIS
// ==============================================================================

$installData = [
    'admin' => [],
    'database' => [],
    'app' => [],
];

// ==============================================================================
// CABEÇALHO DO INSTALADOR
// ==============================================================================

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

$requirements = [
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

printInfo("O primeiro usuário será o administrador do sistema.\n");

// Nome completo
while (true) {
    $adminName = ask("Nome completo do administrador");
    if (strlen($adminName) >= 3) {
        break;
    }
    printError("Nome deve ter pelo menos 3 caracteres.\n");
}

// Email
while (true) {
    $adminEmail = ask("Email do administrador");
    if (validateEmail($adminEmail)) {
        break;
    }
    printError("Email inválido. Use o formato: usuario@dominio.com\n");
}

// Senha
printInfo("\nRequisitos da senha:");
printInfo("  • Mínimo 12 caracteres");
printInfo("  • Pelo menos 1 letra maiúscula");
printInfo("  • Pelo menos 1 letra minúscula");
printInfo("  • Pelo menos 1 número\n");

while (true) {
    $adminPassword = askPassword("Senha do administrador");
    $validation = validatePassword($adminPassword);

    if ($validation === true) {
        $confirmPassword = askPassword("Confirme a senha");

        if ($adminPassword === $confirmPassword) {
            break;
        }
        printError("As senhas não coincidem. Tente novamente.\n");
    } else {
        printError($validation . "\n");
    }
}

$installData['admin'] = [
    'name' => $adminName,
    'email' => $adminEmail,
    'password' => password_hash($adminPassword, PASSWORD_DEFAULT),
];

printSuccess("\n✅ Dados do administrador validados!");
printInfo("Nome: $adminName");
printInfo("Email: $adminEmail\n");

// ==============================================================================
// FASE 3: CONFIGURAÇÃO DO BANCO DE DADOS
// ==============================================================================

printHeader("FASE 3/4: CONFIGURAÇÃO DO BANCO DE DADOS MYSQL");

printInfo("Configure a conexão com o banco de dados de produção.\n");

// Solicitar informações de conexão
$dbHost = ask("Hostname do MySQL", "localhost");
$dbPort = ask("Porta do MySQL", "3306");
$dbName = ask("Nome do banco de dados", "ponto_eletronico");
$dbUser = ask("Usuário do MySQL");
$dbPass = askPassword("Senha do MySQL");

$installData['database'] = [
    'hostname' => $dbHost,
    'port' => $dbPort,
    'database' => $dbName,
    'username' => $dbUser,
    'password' => $dbPass,
];

printInfo("\nTestando conexão com o servidor MySQL...");

// Testar conexão
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    printSuccess("Conexão estabelecida com sucesso!");

    // Verificar se banco existe
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    $dbExists = $stmt->fetch();

    if ($dbExists) {
        printWarning("\nO banco de dados '$dbName' já existe.");

        // Verificar se há tabelas
        $pdo->exec("USE `$dbName`");
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($tables) > 0) {
            printWarning("Encontradas " . count($tables) . " tabelas no banco:");
            foreach ($tables as $table) {
                echo "  • $table\n";
            }

            echo "\n";
            $action = ask("Deseja APAGAR todos os dados e recriar? (S/N)", "n");

            if (strtolower($action) === 's') {
                printWarning("\n⚠️  ATENÇÃO: Esta ação é IRREVERSÍVEL!");
                $confirm = ask("Digite 'CONFIRMO' para prosseguir");

                if ($confirm === 'CONFIRMO') {
                    printInfo("Removendo todas as tabelas...");

                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    foreach ($tables as $table) {
                        $pdo->exec("DROP TABLE IF EXISTS `$table`");
                        printSuccess("Tabela '$table' removida");
                    }
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

                    printSuccess("\n✅ Banco de dados limpo!");
                } else {
                    printError("Confirmação inválida. Instalação cancelada.");
                    exit(1);
                }
            } else {
                printWarning("\nOs dados existentes serão mantidos.");
                printWarning("As migrations adicionarão apenas estruturas faltantes.");
            }
        }
    } else {
        printInfo("Criando banco de dados '$dbName'...");
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        printSuccess("Banco de dados criado com sucesso!");
    }

} catch (PDOException $e) {
    printError("\n❌ Erro de conexão com MySQL:");
    printError($e->getMessage());
    printError("\nVerifique as credenciais e tente novamente.");
    exit(1);
}

echo "\n";

// ==============================================================================
// GERAR CONFIGURAÇÕES
// ==============================================================================

printInfo("Gerando configurações do sistema...\n");

// Gerar chave de criptografia
$encryptionKey = 'base64:' . base64_encode(random_bytes(32));
printSuccess("Chave de criptografia gerada");

// Solicitar URL da aplicação
$appURL = ask("URL de produção do sistema (com https://)", "https://seu-dominio.com.br");
$installData['app']['url'] = $appURL;

// Criar arquivo .env
printInfo("\nCriando arquivo de configuração (.env)...");

$templatePath = __DIR__ . '/.env.production.template';
if (!file_exists($templatePath)) {
    printError("Template .env.production.template não encontrado!");
    exit(1);
}

$template = file_get_contents($templatePath);

$replacements = [
    '%%APP_BASE_URL%%' => $appURL,
    '%%ENCRYPTION_KEY%%' => $encryptionKey,
    '%%DB_HOSTNAME%%' => $dbHost,
    '%%DB_DATABASE%%' => $dbName,
    '%%DB_USERNAME%%' => $dbUser,
    '%%DB_PASSWORD%%' => $dbPass,
    '%%DB_PORT%%' => $dbPort,
    '%%COMPANY_NAME%%' => ask("Nome da empresa"),
    '%%COMPANY_CNPJ%%' => ask("CNPJ da empresa"),
    '%%EMAIL_FROM%%' => ask("Email remetente do sistema", "noreply@" . parse_url($appURL, PHP_URL_HOST)),
    '%%EMAIL_FROM_NAME%%' => ask("Nome do remetente", "Sistema de Ponto"),
    '%%SMTP_HOST%%' => ask("Servidor SMTP", "smtp.gmail.com"),
    '%%SMTP_USER%%' => ask("Usuário SMTP"),
    '%%SMTP_PASS%%' => askPassword("Senha SMTP"),
    '%%SMTP_PORT%%' => ask("Porta SMTP", "587"),
    '%%SMTP_CRYPTO%%' => ask("Criptografia (tls/ssl)", "tls"),
    '%%ADMIN_EMAIL%%' => $adminEmail,
];

$envContent = str_replace(array_keys($replacements), array_values($replacements), $template);

if (file_put_contents(__DIR__ . '/.env', $envContent)) {
    chmod(__DIR__ . '/.env', 0600);
    printSuccess("Arquivo .env criado e protegido (permissão 600)");
} else {
    printError("Erro ao criar arquivo .env");
    exit(1);
}

// Executar migrations
printInfo("\nExecutando migrations do banco de dados...");

if (isFunctionEnabled('exec')) {
    $output = [];
    $returnVar = 0;
    @exec("cd " . escapeshellarg(__DIR__) . " && php spark migrate --all 2>&1", $output, $returnVar);

    if ($returnVar === 0) {
        printSuccess("Migrations executadas com sucesso!");
    } else {
        printError("Erro ao executar migrations:");
        foreach ($output as $line) {
            echo "  $line\n";
        }
        printWarning("\nContinuando instalação...\n");
    }
} else {
    printWarning("A função exec() está desabilitada no servidor.");
    printInfo("As migrations serão executadas automaticamente na primeira execução do sistema.");
    printInfo("Ou você pode executar manualmente: php spark migrate --all");
}

// Criar usuário administrador
printInfo("Criando usuário administrador no banco...");

try {
    $pdo->exec("USE `$dbName`");

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
        $installData['admin']['name'],
        $installData['admin']['email'],
        $installData['admin']['password']
    ]);

    printSuccess("Usuário administrador criado com sucesso!\n");

} catch (PDOException $e) {
    printError("Erro ao criar administrador: " . $e->getMessage());
    printWarning("Você pode criar manualmente após a instalação.\n");
}

// ==============================================================================
// FASE 4: CHECAGEM FINAL DE COMUNICAÇÃO
// ==============================================================================

printHeader("FASE 4/4: CHECAGEM FINAL DE COMUNICAÇÃO");

printInfo("Validando integração entre componentes...\n");

$checks = [];

// 1. Testar conexão com banco
try {
    $pdo->exec("USE `$dbName`");
    $stmt = $pdo->query("SELECT 1");
    $checks['database'] = true;
    printSuccess("Comunicação com banco de dados: OK");
} catch (PDOException $e) {
    $checks['database'] = false;
    printError("Comunicação com banco de dados: FALHA");
}

// 2. Verificar tabelas criadas
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredTables = ['employees', 'time_punches', 'companies', 'audit_logs'];
    $missingTables = array_diff($requiredTables, $tables);

    if (empty($missingTables)) {
        $checks['tables'] = true;
        printSuccess("Estrutura de tabelas: OK (" . count($tables) . " tabelas)");
    } else {
        $checks['tables'] = false;
        printError("Estrutura de tabelas: INCOMPLETA (faltam: " . implode(', ', $missingTables) . ")");
    }
} catch (PDOException $e) {
    $checks['tables'] = false;
    printError("Estrutura de tabelas: ERRO");
}

// 3. Verificar usuário admin
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();

    if ($adminCount > 0) {
        $checks['admin'] = true;
        printSuccess("Usuário administrador: OK");
    } else {
        $checks['admin'] = false;
        printWarning("Usuário administrador: NÃO ENCONTRADO");
    }
} catch (PDOException $e) {
    $checks['admin'] = false;
    printError("Usuário administrador: ERRO");
}

// 4. Verificar arquivo .env
if (file_exists(__DIR__ . '/.env')) {
    $checks['env'] = true;
    printSuccess("Arquivo de configuração (.env): OK");
} else {
    $checks['env'] = false;
    printError("Arquivo de configuração (.env): NÃO ENCONTRADO");
}

// 5. Verificar permissões de diretórios
$writableDirs = ['writable/cache', 'writable/logs', 'writable/session', 'writable/uploads'];
$allWritable = true;
foreach ($writableDirs as $dir) {
    if (!is_writable(__DIR__ . '/' . $dir)) {
        $allWritable = false;
        break;
    }
}

if ($allWritable) {
    $checks['permissions'] = true;
    printSuccess("Permissões de diretórios: OK");
} else {
    $checks['permissions'] = false;
    printWarning("Permissões de diretórios: VERIFICAÇÃO NECESSÁRIA");
}

echo "\n";

// Resultado final
$allPassed = !in_array(false, $checks);

if ($allPassed) {
    printHeader("✅ INSTALAÇÃO CONCLUÍDA COM SUCESSO!");

    echo Color::GREEN . Color::BOLD . "\nSistema pronto para produção!\n" . Color::RESET . "\n";

    echo Color::BOLD . "CREDENCIAIS DO ADMINISTRADOR:\n" . Color::RESET;
    echo "  Email: {$installData['admin']['email']}\n";
    echo "  Senha: [A senha que você definiu]\n\n";

    echo Color::BOLD . "PRÓXIMOS PASSOS:\n" . Color::RESET;
    echo "  1. Configure seu servidor web (Apache/Nginx) para apontar para /public\n";
    echo "  2. Configure certificado SSL/HTTPS\n";
    echo "  3. Acesse: {$installData['app']['url']}\n";
    echo "  4. Faça login com as credenciais acima\n";
    echo "  5. Configure backup automático do banco de dados\n\n";

    printWarning("SEGURANÇA:");
    echo "  • O arquivo .env contém informações sensíveis\n";
    echo "  • NUNCA compartilhe ou versione o arquivo .env\n";
    echo "  • Mantenha backups da chave de criptografia\n\n";

} else {
    printHeader("⚠️  INSTALAÇÃO CONCLUÍDA COM AVISOS");

    echo Color::YELLOW . "\nAlguns componentes precisam de atenção:\n" . Color::RESET . "\n";

    foreach ($checks as $component => $status) {
        if (!$status) {
            printWarning("Verificar: $component");
        }
    }

    echo "\nRevise os avisos acima antes de colocar o sistema em produção.\n\n";
}

echo Color::CYAN . str_repeat('=', 75) . Color::RESET . "\n\n";
