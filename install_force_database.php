<?php
/**
 * Script de Instalação Forçada para Testes em Produção
 * Cria banco de dados SQLite compatível para testes quando MySQL não está disponível
 */

echo "=================================================\n";
echo "  INSTALAÇÃO FORÇADA - Ambiente de Testes\n";
echo "=================================================\n\n";

// Verificar se temos SQLite
if (!extension_loaded('pdo')) {
    die("❌ PDO não disponível\n");
}

echo "✅ PDO disponível\n";
echo "Drivers disponíveis: " . implode(', ', PDO::getAvailableDrivers()) . "\n\n";

// Criar diretório para banco SQLite
$dbDir = __DIR__ . '/writable/database';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$dbFile = $dbDir . '/ponto_eletronico.db';

echo "🗄️  Criando banco de dados SQLite em: $dbFile\n";

try {
    // Conectar ao SQLite
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Banco de dados SQLite criado\n\n";

    // Criar tabela employees
    echo "📋 Criando tabela employees...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS employees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'funcionario',
            active INTEGER NOT NULL DEFAULT 1,
            cpf VARCHAR(14),
            phone VARCHAR(20),
            department VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabela employees criada\n";

    // Criar tabela timesheets
    echo "📋 Criando tabela timesheets...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS timesheets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            date DATE NOT NULL,
            check_in TIME,
            check_out TIME,
            lunch_start TIME,
            lunch_end TIME,
            hours_worked DECIMAL(4,2),
            status VARCHAR(50) DEFAULT 'pending',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Tabela timesheets criada\n";

    // Criar tabela remember_tokens
    echo "📋 Criando tabela remember_tokens...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS remember_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            selector VARCHAR(64) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            expires_at DATETIME NOT NULL,
            last_used_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_selector_hash ON remember_tokens(selector, token_hash)");
    echo "✅ Tabela remember_tokens criada\n";

    // Criar tabela audit_logs
    echo "📋 Criando tabela audit_logs...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action VARCHAR(100) NOT NULL,
            table_name VARCHAR(100),
            record_id INTEGER,
            old_values TEXT,
            new_values TEXT,
            metadata TEXT,
            description TEXT,
            severity VARCHAR(50) DEFAULT 'info',
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabela audit_logs criada\n";

    // Criar tabela leave_requests
    echo "📋 Criando tabela leave_requests...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS leave_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            type VARCHAR(50) NOT NULL,
            reason TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            approved_by INTEGER,
            approved_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Tabela leave_requests criada\n";

    // Criar tabela biometric_templates
    echo "📋 Criando tabela biometric_templates...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS biometric_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            template_data TEXT NOT NULL,
            template_type VARCHAR(50) DEFAULT 'facial',
            quality_score DECIMAL(3,2),
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Tabela biometric_templates criada\n";

    // Inserir dados de teste
    echo "\n👥 Inserindo dados de teste...\n";

    // Senha: Admin@123456
    $passwordHash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewPAAa4pQRLfT4SG';

    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO employees (name, email, password, role, cpf, phone, department)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $employees = [
        ['Administrador Teste', 'admin@teste.com', $passwordHash, 'admin', '123.456.789-00', '(11) 98765-4321', 'TI'],
        ['Gestor RH', 'gestor@teste.com', $passwordHash, 'gestor', '987.654.321-00', '(11) 98765-4322', 'Recursos Humanos'],
        ['Funcionário João', 'joao@teste.com', $passwordHash, 'funcionario', '111.222.333-44', '(11) 98765-4323', 'Operações'],
        ['Funcionária Maria', 'maria@teste.com', $passwordHash, 'funcionario', '555.666.777-88', '(11) 98765-4324', 'Vendas'],
        ['Funcionário Pedro', 'pedro@teste.com', $passwordHash, 'funcionario', '999.888.777-66', '(11) 98765-4325', 'Operações'],
    ];

    foreach ($employees as $emp) {
        $stmt->execute($emp);
    }

    echo "✅ 5 funcionários inseridos\n";

    // Inserir timesheets de exemplo
    echo "📊 Inserindo timesheets de teste...\n";
    $stmt = $pdo->prepare("
        INSERT INTO timesheets (employee_id, date, check_in, check_out, lunch_start, lunch_end, hours_worked, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $timesheets = [
        [3, $today, '08:00:00', null, null, null, 0, 'working'],
        [3, $yesterday, '08:00:00', '17:00:00', '12:00:00', '13:00:00', 8.00, 'approved'],
        [4, $today, '09:00:00', null, null, null, 0, 'working'],
        [4, $yesterday, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 8.00, 'approved'],
        [5, $yesterday, '08:30:00', '17:30:00', '12:00:00', '13:00:00', 8.00, 'pending'],
    ];

    foreach ($timesheets as $ts) {
        $stmt->execute($ts);
    }

    echo "✅ 5 timesheets inseridos\n";

    // Inserir solicitações de férias
    echo "🏖️  Inserindo solicitações de férias...\n";
    $stmt = $pdo->prepare("
        INSERT INTO leave_requests (employee_id, start_date, end_date, type, reason, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $nextWeek = date('Y-m-d', strtotime('+7 days'));
    $twoWeeks = date('Y-m-d', strtotime('+14 days'));

    $leaves = [
        [3, $nextWeek, $twoWeeks, 'vacation', 'Férias anuais', 'pending'],
        [4, $nextWeek, $nextWeek, 'personal', 'Assuntos pessoais', 'approved'],
    ];

    foreach ($leaves as $leave) {
        $stmt->execute($leave);
    }

    echo "✅ 2 solicitações de férias inseridas\n";

    // Inserir logs de auditoria
    echo "📝 Inserindo logs de auditoria...\n";
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, description, severity, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $logs = [
        [1, 'LOGIN', null, 'Login bem-sucedido', 'info', '127.0.0.1'],
        [1, 'CREATE', 'employees', 'Novo funcionário criado', 'info', '127.0.0.1'],
        [2, 'UPDATE', 'timesheets', 'Timesheet aprovado', 'info', '127.0.0.1'],
    ];

    foreach ($logs as $log) {
        $stmt->execute($log);
    }

    echo "✅ 3 logs de auditoria inseridos\n";

    // Atualizar .env para usar SQLite
    echo "\n⚙️  Atualizando configuração (.env)...\n";

    $envFile = __DIR__ . '/.env';
    $envContent = file_get_contents($envFile);

    // Backup
    file_put_contents($envFile . '.backup.' . time(), $envContent);

    // Atualizar para SQLite
    $envContent = preg_replace('/database\.default\.DBDriver = .*/', 'database.default.DBDriver = SQLite3', $envContent);
    $envContent = preg_replace('/database\.default\.database = .*/', 'database.default.database = ' . $dbFile, $envContent);

    file_put_contents($envFile, $envContent);

    echo "✅ Arquivo .env atualizado para usar SQLite\n";

    // Mostrar resumo
    echo "\n";
    echo "=================================================\n";
    echo "  ✅ INSTALAÇÃO COMPLETA!\n";
    echo "=================================================\n\n";

    $count = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    echo "👥 Funcionários: $count\n";

    $count = $pdo->query("SELECT COUNT(*) FROM timesheets")->fetchColumn();
    echo "📊 Timesheets: $count\n";

    $count = $pdo->query("SELECT COUNT(*) FROM leave_requests")->fetchColumn();
    echo "🏖️  Solicitações de férias: $count\n";

    $count = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    echo "📝 Logs de auditoria: $count\n";

    echo "\n🔐 Credenciais de acesso:\n";
    echo "   Admin:       admin@teste.com / Admin@123456\n";
    echo "   Gestor:      gestor@teste.com / Admin@123456\n";
    echo "   Funcionário: joao@teste.com / Admin@123456\n";
    echo "   Funcionária: maria@teste.com / Admin@123456\n";
    echo "   Funcionário: pedro@teste.com / Admin@123456\n";

    echo "\n🚀 Próximo passo:\n";
    echo "   php spark serve\n";
    echo "   Acesse: http://localhost:8080\n\n";

} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
