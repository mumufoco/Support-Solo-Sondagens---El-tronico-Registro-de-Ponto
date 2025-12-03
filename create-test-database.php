#!/usr/bin/env php
<?php
/**
 * Script para criar banco de dados SQLite de teste
 * com usuários e dados básicos para testes
 */

echo "====================================================================\n";
echo "  CRIANDO BANCO DE DADOS DE TESTE (SQLite)\n";
echo "====================================================================\n\n";

$dbPath = __DIR__ . '/writable/test-database.db';

// Remove banco antigo se existir
if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "✓ Banco de dados antigo removido\n";
}

// Criar diretório se não existir
$dir = dirname($dbPath);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Conexão estabelecida: $dbPath\n\n";

    // ====================================================================
    // CRIAR TABELAS
    // ====================================================================

    echo "📋 Criando tabelas...\n";

    // Tabela employees
    $db->exec("
        CREATE TABLE IF NOT EXISTS employees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            cpf VARCHAR(14) NOT NULL UNIQUE,
            unique_code VARCHAR(10) NOT NULL UNIQUE,
            role TEXT CHECK(role IN ('admin','gestor','funcionario')) NOT NULL DEFAULT 'funcionario',
            department VARCHAR(100),
            position VARCHAR(100),
            expected_hours_daily DECIMAL(4,2) NOT NULL DEFAULT 8.00,
            work_schedule_start TIME,
            work_schedule_end TIME,
            active INTEGER NOT NULL DEFAULT 1,
            extra_hours_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            owed_hours_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            manager_id INTEGER,
            two_factor_secret VARCHAR(32),
            two_factor_enabled INTEGER NOT NULL DEFAULT 0,
            two_factor_backup_codes TEXT,
            created_at DATETIME,
            updated_at DATETIME,
            deleted_at DATETIME,
            FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL
        )
    ");
    echo "  ✓ employees\n";

    // Tabela time_punches
    $db->exec("
        CREATE TABLE IF NOT EXISTS time_punches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            punch_time DATETIME NOT NULL,
            punch_type TEXT CHECK(punch_type IN ('entrada','saida','intervalo_inicio','intervalo_fim')) NOT NULL,
            method TEXT CHECK(method IN ('biometria','facial','codigo','manual','webservice')) NOT NULL DEFAULT 'codigo',
            ip_address VARCHAR(45),
            geolocation VARCHAR(100),
            notes TEXT,
            signature TEXT,
            hash VARCHAR(64),
            device_fingerprint VARCHAR(255),
            biometric_score DECIMAL(5,2),
            facial_confidence DECIMAL(5,2),
            is_anomaly INTEGER NOT NULL DEFAULT 0,
            anomaly_reason VARCHAR(255),
            validated_at DATETIME,
            validated_by INTEGER,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            FOREIGN KEY (validated_by) REFERENCES employees(id) ON DELETE SET NULL
        )
    ");
    echo "  ✓ time_punches\n";

    // Tabela audit_logs
    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100),
            entity_id INTEGER,
            old_values TEXT,
            new_values TEXT,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            url VARCHAR(255),
            method VARCHAR(10),
            description TEXT,
            level VARCHAR(20) DEFAULT 'info',
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE SET NULL
        )
    ");
    echo "  ✓ audit_logs\n";

    echo "\n";

    // ====================================================================
    // CRIAR USUÁRIOS DE TESTE
    // ====================================================================

    echo "👥 Criando usuários de teste...\n";

    $now = date('Y-m-d H:i:s');

    $users = [
        [
            'name' => 'Administrador Teste',
            'email' => 'admin@test.com',
            'password' => password_hash('admin123', PASSWORD_ARGON2ID),
            'cpf' => '111.111.111-11',
            'unique_code' => 'ADM001',
            'role' => 'admin',
            'department' => 'TI',
            'position' => 'Administrador de Sistemas',
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now
        ],
        [
            'name' => 'Gestor Teste',
            'email' => 'manager@test.com',
            'password' => password_hash('manager123', PASSWORD_ARGON2ID),
            'cpf' => '222.222.222-22',
            'unique_code' => 'MGR001',
            'role' => 'gestor',
            'department' => 'RH',
            'position' => 'Gerente de RH',
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now
        ],
        [
            'name' => 'Funcionário Teste',
            'email' => 'employee@test.com',
            'password' => password_hash('employee123', PASSWORD_ARGON2ID),
            'cpf' => '333.333.333-33',
            'unique_code' => 'EMP001',
            'role' => 'funcionario',
            'department' => 'Operacional',
            'position' => 'Assistente',
            'active' => 1,
            'manager_id' => 2, // Gerenciado pelo gestor
            'created_at' => $now,
            'updated_at' => $now
        ]
    ];

    $stmt = $db->prepare("
        INSERT INTO employees (
            name, email, password, cpf, unique_code, role, department, position,
            expected_hours_daily, active, manager_id, created_at, updated_at
        ) VALUES (
            :name, :email, :password, :cpf, :unique_code, :role, :department, :position,
            8.00, :active, :manager_id, :created_at, :updated_at
        )
    ");

    foreach ($users as $user) {
        $stmt->execute($user);
        echo "  ✓ {$user['name']} ({$user['email']}) - Role: {$user['role']}\n";
    }

    echo "\n";

    // ====================================================================
    // CRIAR ALGUNS REGISTROS DE PONTO DE TESTE
    // ====================================================================

    echo "⏱️  Criando registros de ponto de exemplo...\n";

    $today = date('Y-m-d');
    $punches = [
        // Funcionário (ID 3) - dia completo
        [
            'employee_id' => 3,
            'punch_time' => "$today 08:00:00",
            'punch_type' => 'entrada',
            'method' => 'codigo',
            'ip_address' => '127.0.0.1',
            'created_at' => $now
        ],
        [
            'employee_id' => 3,
            'punch_time' => "$today 12:00:00",
            'punch_type' => 'intervalo_inicio',
            'method' => 'codigo',
            'ip_address' => '127.0.0.1',
            'created_at' => $now
        ],
        [
            'employee_id' => 3,
            'punch_time' => "$today 13:00:00",
            'punch_type' => 'intervalo_fim',
            'method' => 'codigo',
            'ip_address' => '127.0.0.1',
            'created_at' => $now
        ],
        [
            'employee_id' => 3,
            'punch_time' => "$today 17:00:00",
            'punch_type' => 'saida',
            'method' => 'codigo',
            'ip_address' => '127.0.0.1',
            'created_at' => $now
        ]
    ];

    $stmtPunch = $db->prepare("
        INSERT INTO time_punches (
            employee_id, punch_time, punch_type, method, ip_address, created_at
        ) VALUES (
            :employee_id, :punch_time, :punch_type, :method, :ip_address, :created_at
        )
    ");

    foreach ($punches as $punch) {
        $stmtPunch->execute($punch);
    }

    echo "  ✓ 4 registros de ponto criados para hoje\n";

    echo "\n";

    // ====================================================================
    // VERIFICAR CRIAÇÃO
    // ====================================================================

    echo "✅ BANCO DE DADOS CRIADO COM SUCESSO!\n\n";

    echo "====================================================================\n";
    echo "  USUÁRIOS DE TESTE CRIADOS\n";
    echo "====================================================================\n\n";

    echo "🔑 ADMIN:\n";
    echo "   Email:    admin@test.com\n";
    echo "   Senha:    admin123\n";
    echo "   Role:     admin\n";
    echo "   Código:   ADM001\n\n";

    echo "🔑 GESTOR:\n";
    echo "   Email:    manager@test.com\n";
    echo "   Senha:    manager123\n";
    echo "   Role:     gestor\n";
    echo "   Código:   MGR001\n\n";

    echo "🔑 FUNCIONÁRIO:\n";
    echo "   Email:    employee@test.com\n";
    echo "   Senha:    employee123\n";
    echo "   Role:     funcionario\n";
    echo "   Código:   EMP001\n\n";

    echo "====================================================================\n";
    echo "  PRÓXIMOS PASSOS\n";
    echo "====================================================================\n\n";

    echo "1. Servidor de desenvolvimento já está rodando em http://localhost:8080\n";
    echo "2. Acesse: http://localhost:8080/auth/login\n";
    echo "3. Faça login com qualquer um dos usuários acima\n";
    echo "4. Teste a navegação completa do sistema\n\n";

    $result = $db->query("SELECT COUNT(*) as total FROM employees");
    $count = $result->fetch(PDO::FETCH_ASSOC);
    echo "📊 Total de usuários criados: {$count['total']}\n\n";

} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
