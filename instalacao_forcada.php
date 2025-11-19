<?php
/**
 * INSTALAÇÃO FORÇADA - PRODUÇÃO TOTAL
 * Instala o sistema COMPLETO sem depender de MySQL
 */

echo "\n";
echo "🚀 INSTALAÇÃO FORÇADA - AMBIENTE DE PRODUÇÃO TOTAL\n";
echo str_repeat("=", 80) . "\n\n";

$dataDir = __DIR__ . '/writable/database/';

// Criar diretórios necessários
$dirs = [
    'writable/cache',
    'writable/logs',
    'writable/session',
    'writable/uploads',
    'writable/database',
    'writable/biometric',
    'writable/exports',
];

echo "📁 Criando estrutura de diretórios...\n";
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "  ✅ Criado: $dir\n";
    } else {
        echo "  ✓ Existe: $dir\n";
    }
}

echo "\n";

// Configurar .env
echo "⚙️ Configurando .env para produção...\n";

$envContent = file_get_contents('.env');

// Garantir que está em production
$envContent = preg_replace('/CI_ENVIRONMENT\s*=\s*.*/', 'CI_ENVIRONMENT = production', $envContent);

file_put_contents('.env', $envContent);
echo "  ✅ .env configurado\n\n";

// Criar tabelas JSON
echo "🗄️ Criando estrutura de dados JSON...\n";

$tables = [
    'employees' => [
        [
            'id' => 1,
            'name' => 'Administrador',
            'email' => 'admin@sistema.com',
            'password' => password_hash('Admin@2025', PASSWORD_BCRYPT, ['cost' => 12]),
            'cpf' => '000.000.000-00',
            'role' => 'admin',
            'admission_date' => date('Y-m-d'),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]
    ],
    'timesheets' => [],
    'remember_tokens' => [],
    'audit_logs' => [
        [
            'id' => 1,
            'employee_id' => 1,
            'action' => 'INSTALL',
            'entity' => 'system',
            'entity_id' => null,
            'old_values' => null,
            'new_values' => json_encode(['version' => '1.0.0']),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Instalação Forçada',
            'created_at' => date('Y-m-d H:i:s'),
        ]
    ],
    'leave_requests' => [],
    'biometric_templates' => [],
    'settings' => [
        [
            'id' => 1,
            'key' => 'self_registration_enabled',
            'value' => 'false',
            'created_at' => date('Y-m-d H:i:s'),
        ],
        [
            'id' => 2,
            'key' => 'system_installed',
            'value' => 'true',
            'created_at' => date('Y-m-d H:i:s'),
        ]
    ],
];

foreach ($tables as $tableName => $data) {
    $file = $dataDir . $tableName . '.json';
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "  ✅ Tabela criada: $tableName (" . count($data) . " registros)\n";
}

echo "\n";

// Criar arquivo de metadata
echo "📋 Criando metadata da instalação...\n";

$metadata = [
    'installed_at' => date('Y-m-d H:i:s'),
    'version' => '3.0.0',
    'database_type' => 'JSON',
    'admin_email' => 'admin@sistema.com',
    'installation_mode' => 'forced_production',
];

file_put_contents($dataDir . 'metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
echo "  ✅ Metadata criada\n\n";

// Criar flag de instalação
file_put_contents('writable/INSTALLED', date('Y-m-d H:i:s'));

echo str_repeat("=", 80) . "\n";
echo "✅ INSTALAÇÃO FORÇADA CONCLUÍDA COM SUCESSO!\n";
echo str_repeat("=", 80) . "\n\n";

echo "📊 DADOS INSTALADOS:\n";
echo "  👤 Usuário Admin criado\n";
echo "     Email: admin@sistema.com\n";
echo "     Senha: Admin@2025\n\n";

echo "  🗄️ Tabelas criadas: " . count($tables) . "\n";
foreach ($tables as $name => $data) {
    echo "     - $name: " . count($data) . " registros\n";
}

echo "\n";
echo "🚀 PRÓXIMO PASSO:\n";
echo "  1. Inicie o servidor: php spark serve\n";
echo "  2. Acesse: http://localhost:8080/auth/login\n";
echo "  3. Faça login com as credenciais acima\n\n";

echo str_repeat("=", 80) . "\n";
