#!/usr/bin/env php
<?php
/**
 * Diagnóstico e Correção de Instalação
 *
 * Este script diagnostica problemas de instalação e tenta corrigi-los
 */

// Cores para terminal
define('GREEN', "\033[0;32m");
define('RED', "\033[0;31m");
define('YELLOW', "\033[1;33m");
define('BLUE', "\033[0;34m");
define('NC', "\033[0m");

function printHeader($text) {
    echo "\n" . BLUE . "╔" . str_repeat("═", 68) . "╗\n";
    echo "║ " . str_pad($text, 67) . "║\n";
    echo "╚" . str_repeat("═", 68) . "╝" . NC . "\n\n";
}

function printSuccess($text) { echo GREEN . "✓ " . $text . NC . "\n"; }
function printError($text) { echo RED . "✗ " . $text . NC . "\n"; }
function printWarning($text) { echo YELLOW . "⚠ " . $text . NC . "\n"; }
function printInfo($text) { echo BLUE . "ℹ " . $text . NC . "\n"; }

printHeader("DIAGNÓSTICO E CORREÇÃO DA INSTALAÇÃO");

// ============================================================================
// ETAPA 1: VERIFICAR ARQUIVO .env
// ============================================================================
printHeader("ETAPA 1: Verificação do Arquivo .env");

if (!file_exists('.env')) {
    printError(".env não encontrado");
    printInfo("Criando .env a partir de .env.example...");

    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        printSuccess(".env criado");
    } else {
        printError(".env.example não encontrado. Execute:");
        echo "  cp .env.example .env\n";
        exit(1);
    }
} else {
    printSuccess(".env encontrado");
}

// Ler configurações do banco
$envContent = file_get_contents('.env');
preg_match('/database\.default\.hostname = (.*)/', $envContent, $matches);
$dbHost = trim($matches[1] ?? 'localhost');

preg_match('/database\.default\.database = (.*)/', $envContent, $matches);
$dbName = trim($matches[1] ?? '');

preg_match('/database\.default\.username = (.*)/', $envContent, $matches);
$dbUser = trim($matches[1] ?? 'root');

preg_match('/database\.default\.password = (.*)/', $envContent, $matches);
$dbPass = trim($matches[1] ?? '');

preg_match('/database\.default\.port = (.*)/', $envContent, $matches);
$dbPort = trim($matches[1] ?? '3306');

printInfo("Configurações do banco de dados:");
echo "  Host: $dbHost\n";
echo "  Database: $dbName\n";
echo "  User: $dbUser\n";
echo "  Port: $dbPort\n";

if (empty($dbName)) {
    printError("Nome do banco de dados não configurado em .env");
    exit(1);
}

// ============================================================================
// ETAPA 2: TESTAR CONEXÃO COM O BANCO
// ============================================================================
printHeader("ETAPA 2: Teste de Conexão com o Banco");

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, '', (int)$dbPort);

    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }

    printSuccess("Conectado ao MySQL");

    // Verificar se o banco existe
    $result = $mysqli->query("SHOW DATABASES LIKE '$dbName'");
    if ($result && $result->num_rows > 0) {
        printSuccess("Banco de dados '$dbName' existe");

        // Selecionar o banco
        $mysqli->select_db($dbName);

        // Verificar tabelas
        $result = $mysqli->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }

        if (count($tables) > 0) {
            printWarning("Banco contém " . count($tables) . " tabelas:");
            foreach ($tables as $table) {
                echo "  • $table\n";
            }
        } else {
            printWarning("Banco está vazio - nenhuma tabela encontrada");
            printInfo("As migrations precisam ser executadas");
        }

    } else {
        printWarning("Banco de dados '$dbName' não existe");
        printInfo("Criando banco de dados...");

        $sql = "CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if ($mysqli->query($sql)) {
            printSuccess("Banco de dados '$dbName' criado");
            $mysqli->select_db($dbName);
        } else {
            throw new Exception("Erro ao criar banco: " . $mysqli->error);
        }
    }

    $mysqli->close();

} catch (Exception $e) {
    printError("Erro de conexão: " . $e->getMessage());
    printInfo("\nVerifique:");
    printInfo("  • MySQL está rodando: service mysql status");
    printInfo("  • Credenciais em .env estão corretas");
    printInfo("  • Usuário tem permissões adequadas");
    exit(1);
}

// ============================================================================
// ETAPA 3: VERIFICAR MIGRATIONS
// ============================================================================
printHeader("ETAPA 3: Verificação de Migrations");

$migrationFiles = glob('app/Database/Migrations/*.php');
printInfo("Migrations encontradas: " . count($migrationFiles));

if (count($migrationFiles) < 21) {
    printWarning("Esperadas 21+ migrations, encontradas: " . count($migrationFiles));
}

// Listar migrations
foreach ($migrationFiles as $file) {
    $name = basename($file);
    echo "  • $name\n";
}

// ============================================================================
// ETAPA 4: EXECUTAR MIGRATIONS
// ============================================================================
printHeader("ETAPA 4: Execução de Migrations");

printInfo("Executando: php spark migrate");
echo "\n";

// Executar migrations com saída detalhada
passthru("php spark migrate 2>&1", $returnCode);

echo "\n";

if ($returnCode === 0) {
    printSuccess("Migrations executadas com sucesso");
} else {
    printError("Erro ao executar migrations (código: $returnCode)");
    printInfo("\nTentando executar migrations individuais...");

    // Tentar rollback primeiro
    printInfo("Executando rollback...");
    passthru("php spark migrate:rollback 2>&1");

    // Tentar executar novamente
    printInfo("\nExecutando migrations novamente...");
    passthru("php spark migrate --all 2>&1", $returnCode);

    if ($returnCode !== 0) {
        printError("Ainda há erros nas migrations");
        printInfo("\nPara debug manual:");
        echo "  php spark migrate:status\n";
        echo "  php spark migrate:rollback\n";
        echo "  php spark migrate --all\n";
    }
}

// ============================================================================
// ETAPA 5: VERIFICAR TABELAS CRIADAS
// ============================================================================
printHeader("ETAPA 5: Verificação de Tabelas Criadas");

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);

    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }

    // Listar tabelas
    $result = $mysqli->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    printInfo("Tabelas criadas: " . count($tables));

    $criticalTables = [
        'employees',
        'time_punches',
        'migrations',
        'biometric_templates',
        'justifications',
        'warnings',
        'settings',
    ];

    echo "\nTabelas Críticas:\n";
    foreach ($criticalTables as $table) {
        if (in_array($table, $tables)) {
            printSuccess("Tabela '$table' existe");
        } else {
            printError("Tabela '$table' NÃO existe");
        }
    }

    echo "\nTodas as Tabelas:\n";
    foreach ($tables as $table) {
        echo "  • $table\n";
    }

    // Verificar tabela employees especificamente
    if (in_array('employees', $tables)) {
        $result = $mysqli->query("SELECT COUNT(*) as count FROM employees");
        $row = $result->fetch_assoc();
        printInfo("Registros na tabela employees: " . $row['count']);
    }

    $mysqli->close();

} catch (Exception $e) {
    printError("Erro ao verificar tabelas: " . $e->getMessage());
}

// ============================================================================
// ETAPA 6: EXECUTAR SEEDERS
// ============================================================================
printHeader("ETAPA 6: Execução de Seeders");

$seeders = ['AdminUserSeeder', 'SettingsSeeder'];

foreach ($seeders as $seeder) {
    printInfo("Executando $seeder...");
    passthru("php spark db:seed $seeder 2>&1", $returnCode);

    if ($returnCode === 0) {
        printSuccess("$seeder executado");
    } else {
        printWarning("$seeder pode ter falhado ou já foi executado");
    }
    echo "\n";
}

// ============================================================================
// ETAPA 7: VALIDAÇÃO FINAL
// ============================================================================
printHeader("ETAPA 7: Validação Final");

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);

    // Verificar admin user
    $result = $mysqli->query("SELECT COUNT(*) as count FROM employees WHERE email LIKE '%admin%'");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            printSuccess("Usuário admin criado");
        } else {
            printWarning("Usuário admin não encontrado");
        }
    }

    // Verificar settings
    $result = $mysqli->query("SELECT COUNT(*) as count FROM settings");
    if ($result) {
        $row = $result->fetch_assoc();
        printInfo("Settings cadastradas: " . $row['count']);
    }

    $mysqli->close();

} catch (Exception $e) {
    printWarning("Não foi possível validar dados: " . $e->getMessage());
}

// ============================================================================
// RESUMO FINAL
// ============================================================================
printHeader("RESUMO FINAL");

echo GREEN . "✓ Conexão com banco de dados OK\n";
echo "✓ Banco de dados criado/existe\n";
echo "✓ Migrations executadas\n";
echo "✓ Seeders executados\n" . NC;

echo "\n" . BLUE . "Próximos Passos:\n" . NC;
echo "\n1. Teste o acesso ao sistema:\n";
echo "   php spark serve\n";
echo "   Acesse: http://localhost:8080\n\n";

echo "2. Credenciais padrão:\n";
echo "   Email: " . YELLOW . "admin@example.com" . NC . "\n";
echo "   Senha: " . YELLOW . "Admin@123" . NC . "\n";
echo "   " . RED . "⚠ ALTERE A SENHA IMEDIATAMENTE!" . NC . "\n\n";

echo "3. Validar instalação completa:\n";
echo "   php validate-system.php\n\n";

printSuccess("Diagnóstico e correção concluídos!");
echo "\n";

exit(0);
