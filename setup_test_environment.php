<?php
/**
 * Setup Completo de Ambiente de Testes
 * Cria dados em JSON para testar TODAS as funcionalidades sem banco de dados
 */

echo "=================================================\n";
echo "  SETUP DE AMBIENTE DE TESTES COMPLETO\n";
echo "=================================================\n\n";

$dbDir = __DIR__ . '/writable/database';

// Senha hasheada: Admin@123456
$passwordHash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewPAAa4pQRLfT4SG';

// 1. EMPLOYEES
echo "[1/8] Criando funcionários...\n";
$employees = [
    [
        'id' => 1,
        'name' => 'Administrador Teste',
        'email' => 'admin@teste.com',
        'password' => $passwordHash,
        'role' => 'admin',
        'cpf' => '123.456.789-00',
        'phone' => '(11) 98765-4321',
        'department' => 'TI',
        'active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ],
    [
        'id' => 2,
        'name' => 'Gestor RH',
        'email' => 'gestor@teste.com',
        'password' => $passwordHash,
        'role' => 'gestor',
        'cpf' => '987.654.321-00',
        'phone' => '(11) 98765-4322',
        'department' => 'Recursos Humanos',
        'active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ],
    [
        'id' => 3,
        'name' => 'João Silva',
        'email' => 'joao@teste.com',
        'password' => $passwordHash,
        'role' => 'funcionario',
        'cpf' => '111.222.333-44',
        'phone' => '(11) 98765-4323',
        'department' => 'Operações',
        'active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ],
    [
        'id' => 4,
        'name' => 'Maria Santos',
        'email' => 'maria@teste.com',
        'password' => $passwordHash,
        'role' => 'funcionario',
        'cpf' => '555.666.777-88',
        'phone' => '(11) 98765-4324',
        'department' => 'Vendas',
        'active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ],
    [
        'id' => 5,
        'name' => 'Pedro Oliveira',
        'email' => 'pedro@teste.com',
        'password' => $passwordHash,
        'role' => 'funcionario',
        'cpf' => '999.888.777-66',
        'phone' => '(11) 98765-4325',
        'department' => 'Operações',
        'active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ],
];
file_put_contents("$dbDir/employees.json", json_encode($employees, JSON_PRETTY_PRINT));
echo "✅ 5 funcionários criados\n";

// 2. TIMESHEETS
echo "[2/8] Criando registros de ponto...\n";
$timesheets = [];
for ($i = 0; $i < 30; $i++) {
    $date = date('Y-m-d', strtotime("-$i days"));
    foreach ([3, 4, 5] as $empId) {
        $timesheets[] = [
            'id' => count($timesheets) + 1,
            'employee_id' => $empId,
            'date' => $date,
            'check_in' => '08:' . sprintf('%02d', rand(0, 30)) . ':00',
            'check_out' => ($i == 0 ? null : '17:' . sprintf('%02d', rand(0, 30)) . ':00'),
            'lunch_start' => '12:00:00',
            'lunch_end' => '13:00:00',
            'hours_worked' => ($i == 0 ? 0 : 8.0 + (rand(-20, 20) / 100)),
            'status' => ($i == 0 ? 'working' : ($i < 5 ? 'pending' : 'approved')),
            'notes' => null,
            'created_at' => $date . ' 08:00:00',
        ];
    }
}
file_put_contents("$dbDir/timesheets.json", json_encode($timesheets, JSON_PRETTY_PRINT));
echo "✅ " . count($timesheets) . " registros de ponto criados\n";

// 3. LEAVE REQUESTS
echo "[3/8] Criando solicitações de férias...\n";
$leaveRequests = [
    [
        'id' => 1,
        'employee_id' => 3,
        'start_date' => date('Y-m-d', strtotime('+7 days')),
        'end_date' => date('Y-m-d', strtotime('+14 days')),
        'type' => 'vacation',
        'reason' => 'Férias anuais programadas',
        'status' => 'pending',
        'approved_by' => null,
        'approved_at' => null,
        'created_at' => date('Y-m-d H:i:s'),
    ],
    [
        'id' => 2,
        'employee_id' => 4,
        'start_date' => date('Y-m-d', strtotime('+3 days')),
        'end_date' => date('Y-m-d', strtotime('+3 days')),
        'type' => 'personal',
        'reason' => 'Assuntos pessoais',
        'status' => 'approved',
        'approved_by' => 2,
        'approved_at' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
    ],
    [
        'id' => 3,
        'employee_id' => 5,
        'start_date' => date('Y-m-d', strtotime('+30 days')),
        'end_date' => date('Y-m-d', strtotime('+45 days')),
        'type' => 'vacation',
        'reason' => 'Férias de final de ano',
        'status' => 'pending',
        'approved_by' => null,
        'approved_at' => null,
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
    ],
];
file_put_contents("$dbDir/leave_requests.json", json_encode($leaveRequests, JSON_PRETTY_PRINT));
echo "✅ " . count($leaveRequests) . " solicitações de férias criadas\n";

// 4. REMEMBER TOKENS
echo "[4/8] Criando tokens de remember me...\n";
$rememberTokens = [];
file_put_contents("$dbDir/remember_tokens.json", json_encode($rememberTokens, JSON_PRETTY_PRINT));
echo "✅ Tabela de tokens criada (vazia)\n";

// 5. AUDIT LOGS
echo "[5/8] Criando logs de auditoria...\n";
$auditLogs = [
    [
        'id' => 1,
        'user_id' => 1,
        'action' => 'LOGIN',
        'table_name' => null,
        'description' => 'Login bem-sucedido do administrador',
        'severity' => 'info',
        'ip_address' => '127.0.0.1',
        'created_at' => date('Y-m-d H:i:s'),
    ],
    [
        'id' => 2,
        'user_id' => 1,
        'action' => 'CREATE',
        'table_name' => 'employees',
        'description' => 'Novo funcionário cadastrado',
        'severity' => 'info',
        'ip_address' => '127.0.0.1',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
    ],
    [
        'id' => 3,
        'user_id' => 2,
        'action' => 'APPROVE',
        'table_name' => 'leave_requests',
        'description' => 'Solicitação de férias aprovada',
        'severity' => 'info',
        'ip_address' => '127.0.0.1',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
    ],
];
file_put_contents("$dbDir/audit_logs.json", json_encode($auditLogs, JSON_PRETTY_PRINT));
echo "✅ " . count($auditLogs) . " logs de auditoria criados\n";

// 6. BIOMETRIC TEMPLATES
echo "[6/8] Criando templates biométricos...\n";
$encryptionKey = base64_decode('tFQ23+7D1waMJ8v8fiLj80/fToCJYbL5rSt9A/MHttc=');
$biometricTemplates = [];
foreach ([3, 4, 5] as $empId) {
    // Simular template biométrico
    $template = [
        'encoding' => array_fill(0, 128, rand(0, 255)),
        'quality' => 0.95,
        'captured_at' => time(),
    ];

    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt(
        json_encode($template),
        'aes-256-cbc',
        $encryptionKey,
        0,
        $iv
    );

    $biometricTemplates[] = [
        'id' => count($biometricTemplates) + 1,
        'employee_id' => $empId,
        'template_data' => base64_encode($iv) . '::' . $encrypted,
        'template_type' => 'facial',
        'quality_score' => 0.95,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ];
}
file_put_contents("$dbDir/biometric_templates.json", json_encode($biometricTemplates, JSON_PRETTY_PRINT));
echo "✅ " . count($biometricTemplates) . " templates biométricos criados (criptografados)\n";

// 7. REPORTS (estrutura)
echo "[7/8] Criando estrutura de relatórios...\n";
$reports = [
    [
        'id' => 1,
        'title' => 'Relatório Mensal de Ponto',
        'type' => 'monthly_timesheet',
        'generated_by' => 1,
        'month' => date('Y-m'),
        'created_at' => date('Y-m-d H:i:s'),
    ],
];
file_put_contents("$dbDir/reports.json", json_encode($reports, JSON_PRETTY_PRINT));
echo "✅ Estrutura de relatórios criada\n";

// 8. DATABASE METADATA
echo "[8/8] Criando metadados do banco...\n";
$metadata = [
    'version' => '1.0',
    'created_at' => date('Y-m-d H:i:s'),
    'last_updated' => date('Y-m-d H:i:s'),
    'driver' => 'JSON',
    'tables' => [
        'employees',
        'timesheets',
        'leave_requests',
        'remember_tokens',
        'audit_logs',
        'biometric_templates',
        'reports',
    ],
];
file_put_contents("$dbDir/metadata.json", json_encode($metadata, JSON_PRETTY_PRINT));
echo "✅ Metadados criados\n";

// Estatísticas
echo "\n=================================================\n";
echo "  ✅ AMBIENTE DE TESTES CONFIGURADO!\n";
echo "=================================================\n\n";

echo "📊 Estatísticas:\n";
echo "   👥 Funcionários: " . count($employees) . "\n";
echo "   📋 Registros de ponto: " . count($timesheets) . "\n";
echo "   🏖️  Solicitações de férias: " . count($leaveRequests) . "\n";
echo "   🔒 Templates biométricos: " . count($biometricTemplates) . "\n";
echo "   📝 Logs de auditoria: " . count($auditLogs) . "\n";
echo "   📊 Relatórios: " . count($reports) . "\n";

echo "\n🔐 Credenciais de Login:\n";
echo "   Admin:       admin@teste.com / Admin@123456\n";
echo "   Gestor:      gestor@teste.com / Admin@123456\n";
echo "   Funcionário: joao@teste.com / Admin@123456\n";
echo "   Funcionária: maria@teste.com / Admin@123456\n";
echo "   Funcionário: pedro@teste.com / Admin@123456\n";

echo "\n📁 Arquivos criados em: writable/database/\n";
foreach ($metadata['tables'] as $table) {
    $file = "$dbDir/$table.json";
    if (file_exists($file)) {
        $size = filesize($file);
        echo "   ✅ $table.json (" . number_format($size) . " bytes)\n";
    }
}

echo "\n🚀 Próximos passos:\n";
echo "   1. Executar: php test_full_system.php\n";
echo "   2. Ou iniciar servidor: php spark serve\n";
echo "   3. Acessar: http://localhost:8080\n\n";
