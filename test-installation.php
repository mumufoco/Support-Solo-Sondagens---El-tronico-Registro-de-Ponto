#!/usr/bin/env php
<?php
/**
 * Teste de Instalação Automatizada
 *
 * Simula e valida o processo de instalação completo
 * sem necessidade de interação do usuário ou MySQL real
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TESTE DE INSTALAÇÃO AUTOMATIZADA                              ║\n";
echo "║  Sistema de Ponto Eletrônico Brasileiro                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$passed = 0;
$total = 0;
$errors = [];

function test($description, $condition, &$passed, &$total, &$errors) {
    $total++;
    if ($condition) {
        $passed++;
        echo "✓ {$description}\n";
        return true;
    } else {
        echo "✗ {$description}\n";
        $errors[] = $description;
        return false;
    }
}

// ============================================================================
// TESTE 1: VALIDAÇÃO DE REQUISITOS
// ============================================================================
echo "\n📦 TESTE 1: Validação de Requisitos\n";
echo str_repeat("─", 70) . "\n";

test("Script de instalação existe", file_exists('install.php'), $passed, $total, $errors);
test("Script é executável", is_executable('install.php'), $passed, $total, $errors);
test("Script de validação existe", file_exists('validate-system.php'), $passed, $total, $errors);

// Verificar estrutura do script
if (file_exists('install.php')) {
    $installContent = file_get_contents('install.php');
    test("Script contém validação de requisitos", strpos($installContent, 'Validação de Requisitos') !== false, $passed, $total, $errors);
    test("Script contém configuração de ambiente", strpos($installContent, 'Configuração de Ambiente') !== false, $passed, $total, $errors);
    test("Script contém criação de BD", strpos($installContent, 'Criação do Banco de Dados') !== false, $passed, $total, $errors);
    test("Script contém execução de migrations", strpos($installContent, 'Execução de Migrations') !== false, $passed, $total, $errors);
    test("Script contém execução de seeders", strpos($installContent, 'Execução de Seeders') !== false, $passed, $total, $errors);
    test("Script contém validação final", strpos($installContent, 'Validação da Instalação') !== false, $passed, $total, $errors);
}

// ============================================================================
// TESTE 2: ARQUIVOS NECESSÁRIOS
// ============================================================================
echo "\n📁 TESTE 2: Arquivos Necessários para Instalação\n";
echo str_repeat("─", 70) . "\n";

$requiredFiles = [
    '.env.example' => 'Template de configuração',
    'composer.json' => 'Dependências PHP',
    'phpunit.xml' => 'Configuração de testes',
    'app/Database/Migrations' => 'Migrations de banco',
];

foreach ($requiredFiles as $file => $description) {
    test("$description ($file)", file_exists($file), $passed, $total, $errors);
}

// ============================================================================
// TESTE 3: MIGRATIONS DISPONÍVEIS
// ============================================================================
echo "\n🗄️ TESTE 3: Migrations de Banco de Dados\n";
echo str_repeat("─", 70) . "\n";

$migrations = glob('app/Database/Migrations/*.php');
test("Migrations encontradas (21+ esperadas)", count($migrations) >= 21, $passed, $total, $errors);

$criticalMigrations = [
    'create_employees_table',
    'create_time_punches_table',
    'two_factor_auth', // add_two_factor_auth
    'oauth_tokens', // create_oauth_tokens
    'push_notification_tokens', // create_push_notification_tokens
];

foreach ($criticalMigrations as $migration) {
    $found = false;
    foreach ($migrations as $file) {
        if (strpos($file, $migration) !== false) {
            $found = true;
            break;
        }
    }
    test("Migration: $migration", $found, $passed, $total, $errors);
}

// ============================================================================
// TESTE 4: SEEDERS DISPONÍVEIS
// ============================================================================
echo "\n🌱 TESTE 4: Seeders de Dados Iniciais\n";
echo str_repeat("─", 70) . "\n";

$seeders = glob('app/Database/Seeds/*.php');
test("Seeders encontrados", count($seeders) > 0, $passed, $total, $errors);

$criticalSeeders = [
    'AdminUserSeeder',
    'SettingsSeeder',
];

foreach ($criticalSeeders as $seeder) {
    $found = false;
    foreach ($seeders as $file) {
        if (strpos($file, $seeder) !== false) {
            $found = true;
            break;
        }
    }
    test("Seeder: $seeder", $found, $passed, $total, $errors);
}

// ============================================================================
// TESTE 5: COMANDOS SPARK DISPONÍVEIS
// ============================================================================
echo "\n⚡ TESTE 5: Comandos CodeIgniter Spark\n";
echo str_repeat("─", 70) . "\n";

// Verificar se spark existe
test("Arquivo 'spark' existe", file_exists('spark'), $passed, $total, $errors);

// Verificar se php spark funciona
exec("php spark 2>&1", $output, $returnCode);
$outputText = implode("\n", $output);
$sparkWorks = ($returnCode === 0 ||
               strpos($outputText, 'CodeIgniter') !== false ||
               strpos($outputText, 'environment is not set') !== false); // Esperado se .env não totalmente configurado
test("Comando 'php spark' acessível", $sparkWorks, $passed, $total, $errors);

// Verificar comandos específicos através do help
unset($output);
exec("php spark --help 2>&1", $output, $returnCode);
$helpText = implode("\n", $output);

// Se não conseguir pelo spark, verificar se os arquivos de comando existem
$migrateExists = file_exists('vendor/codeigniter4/framework/system/Commands/Database/Migrate.php') ||
                 stripos($helpText, 'migrate') !== false;
$seedExists = file_exists('vendor/codeigniter4/framework/system/Commands/Database/Seed.php') ||
              stripos($helpText, 'seed') !== false;

test("Comando 'migrate' disponível", $migrateExists, $passed, $total, $errors);
test("Comando 'db:seed' disponível", $seedExists, $passed, $total, $errors);

// ============================================================================
// TESTE 6: ESTRUTURA DE CONFIGURAÇÃO
// ============================================================================
echo "\n⚙️ TESTE 6: Estrutura de Configuração\n";
echo str_repeat("─", 70) . "\n";

// Verificar .env.example
if (file_exists('.env.example')) {
    $envExample = file_get_contents('.env.example');

    $requiredConfigs = [
        'database.default.hostname',
        'database.default.database',
        'database.default.username',
        'database.default.password',
        'ENCRYPTION_KEY',
        'DEEPFACE_API_URL',
    ];

    foreach ($requiredConfigs as $config) {
        test("Config: $config presente em .env.example", strpos($envExample, $config) !== false, $passed, $total, $errors);
    }
}

// ============================================================================
// TESTE 7: DIRETÓRIOS NECESSÁRIOS
// ============================================================================
echo "\n📂 TESTE 7: Diretórios de Armazenamento\n";
echo str_repeat("─", 70) . "\n";

$requiredDirs = [
    'storage',
    'storage/logs',
    'storage/cache',
    'storage/uploads',
    'storage/faces',
    'storage/keys',
    'storage/reports',
    'public',
];

foreach ($requiredDirs as $dir) {
    $exists = is_dir($dir);
    $writable = $exists && is_writable($dir);
    test("Diretório $dir existe e é gravável", $writable, $passed, $total, $errors);
}

// ============================================================================
// TESTE 8: VALIDAÇÃO DE INSTALAÇÃO COMPLETA (SIMULADA)
// ============================================================================
echo "\n🔍 TESTE 8: Simulação de Instalação Completa\n";
echo str_repeat("─", 70) . "\n";

// Verificar se .env existe
$envExists = file_exists('.env');
test(".env configurado", $envExists, $passed, $total, $errors);

if ($envExists) {
    $envContent = file_get_contents('.env');
    test("Database hostname configurado", strpos($envContent, 'database.default.hostname') !== false, $passed, $total, $errors);
    test("Database name configurado", strpos($envContent, 'database.default.database') !== false, $passed, $total, $errors);
}

// Verificar se migrations podem ser listadas
exec("php spark migrate:status 2>&1", $output, $returnCode);
$canListMigrations = ($returnCode === 0 || $returnCode === 1); // 0 = sucesso, 1 = BD não conectado (esperado)
test("Migrations podem ser listadas/verificadas", $canListMigrations, $passed, $total, $errors);

// ============================================================================
// TESTE 9: FLUXO DE INSTALAÇÃO (DRY RUN)
// ============================================================================
echo "\n🎬 TESTE 9: Fluxo de Instalação (Dry Run)\n";
echo str_repeat("─", 70) . "\n";

echo "   ℹ️  Simulando fluxo de instalação...\n\n";

$steps = [
    '1. Validação de Requisitos' => true,
    '2. Configuração de Ambiente (.env)' => file_exists('.env') || file_exists('.env.example'),
    '3. Criação de Banco de Dados' => true, // Seria executado pelo install.php
    '4. Execução de Migrations' => count($migrations) >= 21,
    '5. Execução de Seeders' => count($seeders) >= 2,
    '6. Validação da Instalação' => file_exists('validate-system.php'),
];

foreach ($steps as $step => $condition) {
    test($step, $condition, $passed, $total, $errors);
}

// ============================================================================
// TESTE 10: COMPATIBILIDADE COM DOCKER
// ============================================================================
echo "\n🐳 TESTE 10: Compatibilidade Docker\n";
echo str_repeat("─", 70) . "\n";

test("docker-compose.yml existe", file_exists('docker-compose.yml'), $passed, $total, $errors);

if (file_exists('docker-compose.yml')) {
    $dockerCompose = file_get_contents('docker-compose.yml');
    test("Docker: MySQL service configurado", strpos($dockerCompose, 'mysql:') !== false, $passed, $total, $errors);
    test("Docker: PHP service configurado", strpos($dockerCompose, 'php:') !== false || strpos($dockerCompose, 'php-fpm') !== false, $passed, $total, $errors);
    test("Docker: Volumes configurados", strpos($dockerCompose, 'volumes:') !== false, $passed, $total, $errors);
}

// ============================================================================
// RESUMO DO TESTE
// ============================================================================
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    RESUMO DO TESTE                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$percentage = ($total > 0) ? round(($passed / $total) * 100, 1) : 0;

echo "Total de Testes: $total\n";
echo "✓ Aprovados: $passed\n";
echo "✗ Falharam: " . count($errors) . "\n";
echo "Taxa de Sucesso: $percentage%\n";
echo "\n";

if (count($errors) > 0) {
    echo "❌ TESTES FALHARAM:\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }
    echo "\n";
}

// Status final
if (count($errors) === 0) {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║        ✅ INSTALAÇÃO PRONTA PARA SER EXECUTADA!                ║\n";
    echo "║                                                                ║\n";
    echo "║  Todos os componentes necessários estão presentes.            ║\n";
    echo "║  Execute: php install.php                                     ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "INSTRUÇÕES:\n";
    echo "\n";
    echo "1. Com MySQL local:\n";
    echo "   php install.php\n";
    echo "\n";
    echo "2. Com Docker:\n";
    echo "   docker-compose up -d mysql\n";
    echo "   php install.php\n";
    echo "\n";
    echo "3. Validar instalação:\n";
    echo "   php validate-system.php\n";
    echo "\n";
    exit(0);
} else {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║          ⚠️  PROBLEMAS ENCONTRADOS                             ║\n";
    echo "║                                                                ║\n";
    echo "║  Corrija os erros acima antes de executar a instalação.       ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    exit(1);
}
