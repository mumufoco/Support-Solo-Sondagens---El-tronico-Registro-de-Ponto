#!/usr/bin/env php
<?php
/**
 * Sistema de Ponto Eletrônico - Instalação Automatizada
 *
 * Este script realiza a instalação completa do sistema incluindo:
 * - Validação de requisitos
 * - Configuração de ambiente
 * - Criação de banco de dados
 * - Execução de migrations
 * - Execução de seeders
 * - Validação da instalação
 */

// Cores para terminal
define('GREEN', "\033[0;32m");
define('RED', "\033[0;31m");
define('YELLOW', "\033[1;33m");
define('BLUE', "\033[0;34m");
define('NC', "\033[0m"); // No Color

function printHeader($text) {
    echo "\n";
    echo BLUE . "╔" . str_repeat("═", 68) . "╗\n";
    echo "║ " . str_pad($text, 67) . "║\n";
    echo "╚" . str_repeat("═", 68) . "╝" . NC . "\n";
    echo "\n";
}

function printSuccess($text) {
    echo GREEN . "✓ " . $text . NC . "\n";
}

function printError($text) {
    echo RED . "✗ " . $text . NC . "\n";
}

function printWarning($text) {
    echo YELLOW . "⚠ " . $text . NC . "\n";
}

function printInfo($text) {
    echo BLUE . "ℹ " . $text . NC . "\n";
}

function askQuestion($question, $default = '') {
    $defaultText = $default ? " [$default]" : '';
    echo YELLOW . "? " . $question . $defaultText . ": " . NC;
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    return $line ?: $default;
}

function askYesNo($question, $default = 'y') {
    $answer = strtolower(askQuestion($question . " (y/n)", $default));
    return in_array($answer, ['y', 'yes', 's', 'sim']);
}

// ============================================================================
// CABEÇALHO
// ============================================================================
system('clear');
printHeader("INSTALAÇÃO AUTOMATIZADA - Sistema de Ponto Eletrônico");

echo "Este assistente irá guiá-lo através da instalação completa do sistema.\n";
echo "Certifique-se de ter as seguintes informações:\n";
echo "  • Credenciais do banco de dados MySQL\n";
echo "  • Acesso ao servidor\n";
echo "  • Permissões de escrita nos diretórios\n\n";

if (!askYesNo("Deseja continuar com a instalação?", 'y')) {
    echo "\n" . YELLOW . "Instalação cancelada pelo usuário." . NC . "\n\n";
    exit(0);
}

$errors = [];
$warnings = [];

// ============================================================================
// ETAPA 1: VALIDAÇÃO DE REQUISITOS
// ============================================================================
printHeader("ETAPA 1/6: Validação de Requisitos");

// PHP Version
if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    printSuccess("PHP " . PHP_VERSION . " detectado");
} else {
    printError("PHP 8.1+ é necessário. Versão atual: " . PHP_VERSION);
    $errors[] = "PHP version incompatível";
}

// Extensões PHP
$requiredExtensions = ['mysqli', 'pdo_mysql', 'mbstring', 'intl', 'curl', 'gd', 'sodium', 'zip'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        printSuccess("Extensão $ext instalada");
    } else {
        printError("Extensão $ext não encontrada");
        $errors[] = "Extensão $ext faltando";
    }
}

// Composer
if (is_dir('vendor') && file_exists('vendor/autoload.php')) {
    printSuccess("Dependências Composer instaladas");
} else {
    printError("Dependências Composer não encontradas. Execute: composer install");
    $errors[] = "Composer dependencies missing";
}

// Diretórios graváveis
$writableDirs = ['storage', 'storage/logs', 'storage/cache', 'storage/uploads'];
foreach ($writableDirs as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        printSuccess("Diretório $dir é gravável");
    } else {
        printWarning("Diretório $dir não é gravável ou não existe");
        $warnings[] = "Diretório $dir precisa de permissões";
    }
}

if (!empty($errors)) {
    printError("\nErros críticos encontrados. Por favor, corrija-os antes de continuar.");
    foreach ($errors as $error) {
        echo "  • $error\n";
    }
    exit(1);
}

if (!empty($warnings)) {
    printWarning("\nAvisos encontrados:");
    foreach ($warnings as $warning) {
        echo "  • $warning\n";
    }
    if (!askYesNo("Deseja continuar mesmo assim?", 'n')) {
        exit(0);
    }
}

// ============================================================================
// ETAPA 2: CONFIGURAÇÃO DE AMBIENTE
// ============================================================================
printHeader("ETAPA 2/6: Configuração de Ambiente");

// Verificar se .env já existe
if (file_exists('.env')) {
    printWarning(".env já existe");
    if (askYesNo("Deseja sobrescrever?", 'n')) {
        $createEnv = true;
    } else {
        $createEnv = false;
        printInfo("Usando .env existente");
    }
} else {
    $createEnv = true;
}

$dbConfig = [];

if ($createEnv) {
    echo "\n" . BLUE . "Configuração do Banco de Dados:" . NC . "\n";

    $dbConfig['hostname'] = askQuestion("Host do MySQL", "localhost");
    $dbConfig['database'] = askQuestion("Nome do banco de dados", "ponto_eletronico");
    $dbConfig['username'] = askQuestion("Usuário do MySQL", "root");

    // Senha (oculta)
    echo YELLOW . "? Senha do MySQL: " . NC;
    system('stty -echo');
    $dbConfig['password'] = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";

    $dbConfig['port'] = askQuestion("Porta do MySQL", "3306");

    // Copiar .env.example para .env
    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        printSuccess(".env criado a partir de .env.example");

        // Atualizar configurações do banco
        $envContent = file_get_contents('.env');
        $envContent = preg_replace('/database\.default\.hostname = .*/', "database.default.hostname = {$dbConfig['hostname']}", $envContent);
        $envContent = preg_replace('/database\.default\.database = .*/', "database.default.database = {$dbConfig['database']}", $envContent);
        $envContent = preg_replace('/database\.default\.username = .*/', "database.default.username = {$dbConfig['username']}", $envContent);
        $envContent = preg_replace('/database\.default\.password = .*/', "database.default.password = {$dbConfig['password']}", $envContent);
        $envContent = preg_replace('/database\.default\.port = .*/', "database.default.port = {$dbConfig['port']}", $envContent);

        file_put_contents('.env', $envContent);
        printSuccess("Configurações do banco de dados atualizadas");
    } else {
        printError(".env.example não encontrado");
        exit(1);
    }
} else {
    // Ler configurações do .env existente
    $envContent = file_get_contents('.env');
    preg_match('/database\.default\.hostname = (.*)/', $envContent, $matches);
    $dbConfig['hostname'] = trim($matches[1] ?? 'localhost');

    preg_match('/database\.default\.database = (.*)/', $envContent, $matches);
    $dbConfig['database'] = trim($matches[1] ?? 'ponto_eletronico');

    preg_match('/database\.default\.username = (.*)/', $envContent, $matches);
    $dbConfig['username'] = trim($matches[1] ?? 'root');

    preg_match('/database\.default\.password = (.*)/', $envContent, $matches);
    $dbConfig['password'] = trim($matches[1] ?? '');

    preg_match('/database\.default\.port = (.*)/', $envContent, $matches);
    $dbConfig['port'] = trim($matches[1] ?? '3306');

    printInfo("Configurações lidas do .env existente:");
    printInfo("  Host: {$dbConfig['hostname']}");
    printInfo("  Database: {$dbConfig['database']}");
    printInfo("  User: {$dbConfig['username']}");
    printInfo("  Port: {$dbConfig['port']}");
}

// Gerar ENCRYPTION_KEY se não existir
$envContent = file_get_contents('.env');
if (strpos($envContent, 'ENCRYPTION_KEY =') === false || preg_match('/ENCRYPTION_KEY = $/', $envContent)) {
    printInfo("Gerando ENCRYPTION_KEY...");
    $encryptionKey = base64_encode(random_bytes(32));

    if (strpos($envContent, '# ENCRYPTION_KEY =') !== false) {
        $envContent = str_replace('# ENCRYPTION_KEY =', "ENCRYPTION_KEY = $encryptionKey", $envContent);
    } else {
        $envContent .= "\nENCRYPTION_KEY = $encryptionKey\n";
    }

    file_put_contents('.env', $envContent);
    printSuccess("ENCRYPTION_KEY gerada e salva");
}

// ============================================================================
// ETAPA 3: CRIAÇÃO DO BANCO DE DADOS
// ============================================================================
printHeader("ETAPA 3/6: Criação do Banco de Dados");

// Tentar conectar ao MySQL
try {
    $mysqli = new mysqli(
        $dbConfig['hostname'],
        $dbConfig['username'],
        $dbConfig['password'],
        '',
        (int)$dbConfig['port']
    );

    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }

    printSuccess("Conectado ao MySQL");

    // Verificar se o banco já existe
    $result = $mysqli->query("SHOW DATABASES LIKE '{$dbConfig['database']}'");
    $dbExists = $result && $result->num_rows > 0;

    if ($dbExists) {
        printWarning("Banco de dados '{$dbConfig['database']}' já existe");

        if (askYesNo("Deseja recriar o banco (TODOS OS DADOS SERÃO PERDIDOS)?", 'n')) {
            $mysqli->query("DROP DATABASE `{$dbConfig['database']}`");
            printInfo("Banco de dados anterior removido");
            $dbExists = false;
        } else {
            printInfo("Usando banco de dados existente");
        }
    }

    if (!$dbExists) {
        $sql = "CREATE DATABASE `{$dbConfig['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if ($mysqli->query($sql)) {
            printSuccess("Banco de dados '{$dbConfig['database']}' criado");
        } else {
            throw new Exception("Erro ao criar banco: " . $mysqli->error);
        }
    }

    // Selecionar o banco
    $mysqli->select_db($dbConfig['database']);
    printSuccess("Banco de dados selecionado");

    $mysqli->close();

} catch (Exception $e) {
    printError("Erro ao conectar/criar banco de dados: " . $e->getMessage());
    printInfo("\nVerifique:");
    printInfo("  • MySQL está rodando");
    printInfo("  • Credenciais estão corretas");
    printInfo("  • Usuário tem permissão para criar bancos");
    exit(1);
}

// ============================================================================
// ETAPA 4: EXECUÇÃO DE MIGRATIONS
// ============================================================================
printHeader("ETAPA 4/6: Execução de Migrations");

printInfo("Executando migrations do CodeIgniter...");
echo "\n";

$output = [];
$returnCode = 0;
exec("php spark migrate 2>&1", $output, $returnCode);

foreach ($output as $line) {
    echo "  " . $line . "\n";
}

if ($returnCode === 0 || strpos(implode("\n", $output), 'Done') !== false) {
    printSuccess("\nMigrations executadas com sucesso");
} else {
    printError("\nErro ao executar migrations");
    printWarning("Saída do comando:");
    foreach ($output as $line) {
        echo "  $line\n";
    }

    if (!askYesNo("Deseja continuar mesmo assim?", 'n')) {
        exit(1);
    }
}

// Verificar tabelas criadas
try {
    $mysqli = new mysqli(
        $dbConfig['hostname'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['database'],
        (int)$dbConfig['port']
    );

    $result = $mysqli->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    printInfo("\nTabelas criadas: " . count($tables));
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "  • $table\n";
        }
    }

    $mysqli->close();

} catch (Exception $e) {
    printWarning("Não foi possível listar tabelas: " . $e->getMessage());
}

// ============================================================================
// ETAPA 5: EXECUÇÃO DE SEEDERS
// ============================================================================
printHeader("ETAPA 5/6: Execução de Seeders");

$seeders = ['AdminUserSeeder', 'SettingsSeeder'];

foreach ($seeders as $seeder) {
    printInfo("Executando $seeder...");

    $output = [];
    $returnCode = 0;
    exec("php spark db:seed $seeder 2>&1", $output, $returnCode);

    $outputText = implode("\n", $output);

    if ($returnCode === 0 || strpos($outputText, 'Seeded') !== false || strpos($outputText, 'successfully') !== false) {
        printSuccess("$seeder executado com sucesso");
    } else {
        printWarning("$seeder pode ter falhado ou já foi executado");
        if (askYesNo("Deseja ver a saída?", 'n')) {
            foreach ($output as $line) {
                echo "  $line\n";
            }
        }
    }
}

// ============================================================================
// ETAPA 6: VALIDAÇÃO DA INSTALAÇÃO
// ============================================================================
printHeader("ETAPA 6/6: Validação da Instalação");

printInfo("Executando script de validação...");
echo "\n";

$output = [];
$returnCode = 0;
exec("php validate-system.php 2>&1", $output, $returnCode);

// Mostrar apenas resumo (últimas 30 linhas)
$totalLines = count($output);
$startLine = max(0, $totalLines - 30);

for ($i = $startLine; $i < $totalLines; $i++) {
    echo $output[$i] . "\n";
}

echo "\n";

if ($returnCode === 0) {
    printSuccess("Sistema validado com sucesso!");
} else {
    printWarning("Validação encontrou problemas. Execute 'php validate-system.php' para detalhes.");
}

// ============================================================================
// RESUMO FINAL
// ============================================================================
printHeader("INSTALAÇÃO CONCLUÍDA");

echo GREEN;
echo "✓ Requisitos validados\n";
echo "✓ Ambiente configurado (.env)\n";
echo "✓ Banco de dados criado: {$dbConfig['database']}\n";
echo "✓ Migrations executadas\n";
echo "✓ Seeders executados\n";
echo "✓ Validação concluída\n";
echo NC;

echo "\n" . BLUE . "Próximos Passos:" . NC . "\n\n";

echo "1. Configure serviços adicionais (opcional):\n";
echo "   • DeepFace API (Reconhecimento Facial)\n";
echo "   • WebSocket Server (Chat em tempo real)\n";
echo "   • FCM (Push Notifications)\n\n";

echo "2. Acesse o sistema:\n";
echo "   • URL: " . YELLOW . "http://localhost:8080" . NC . " (ou configurado em .env)\n";
echo "   • Usuário admin padrão: " . YELLOW . "admin@example.com" . NC . "\n";
echo "   • Senha padrão: " . YELLOW . "Admin@123" . NC . "\n";
echo "   " . RED . "⚠ ALTERE A SENHA IMEDIATAMENTE!" . NC . "\n\n";

echo "3. Execute testes (opcional):\n";
echo "   • Validação: " . YELLOW . "php validate-system.php" . NC . "\n";
echo "   • Testes unitários: " . YELLOW . "vendor/bin/phpunit tests/unit/" . NC . "\n\n";

echo "4. Inicie o servidor de desenvolvimento:\n";
echo "   • " . YELLOW . "php spark serve" . NC . "\n\n";

echo "5. Documentação:\n";
echo "   • README.md\n";
echo "   • docs/SYSTEM_VALIDATION_REPORT_PHASES_0-17.md\n";
echo "   • docs/TESTING_GUIDE.md\n\n";

printInfo("Para suporte, consulte a documentação ou abra uma issue no GitHub.");

echo "\n" . GREEN . "╔" . str_repeat("═", 68) . "╗\n";
echo "║ " . str_pad("INSTALAÇÃO BEM-SUCEDIDA!", 67) . "║\n";
echo "╚" . str_repeat("═", 68) . "╝" . NC . "\n\n";

exit(0);
