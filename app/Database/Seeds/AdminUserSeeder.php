<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Check if admin already exists
        $db = \Config\Database::connect();
        $builder = $db->table('employees');

        $existingAdmin = $builder->where('email', 'admin@ponto.com.br')->get()->getRow();

        if ($existingAdmin) {
            echo "Admin user already exists. Skipping...\n";
            return;
        }

        // Generate unique code (8 characters)
        $uniqueCode = strtoupper(bin2hex(random_bytes(4)));

        $data = [
            'name'                  => 'Administrador do Sistema',
            'email'                 => 'admin@ponto.com.br',
            'password'              => password_hash('Admin@123', PASSWORD_ARGON2ID),
            'cpf'                   => '111.111.111-11',
            'unique_code'           => $uniqueCode,
            'role'                  => 'admin',
            'department'            => 'Administração',
            'position'              => 'Administrador de Sistema',
            'expected_hours_daily'  => 8.00,
            'work_schedule_start'   => '08:00:00',
            'work_schedule_end'     => '18:00:00',
            'active'                => true,
            'extra_hours_balance'   => 0.00,
            'owed_hours_balance'    => 0.00,
            'created_at'            => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s'),
        ];

        $builder->insert($data);

        echo "✅ Admin user created successfully!\n";
        echo "   Email: admin@ponto.com.br\n";
        echo "   Password: Admin@123\n";
        echo "   Unique Code: {$uniqueCode}\n";
        echo "   ⚠️  IMPORTANT: Change the password after first login!\n";

        // Create initial consent for admin (data processing)
        $adminId = $db->insertID();

        $consentData = [
            'employee_id'   => $adminId,
            'consent_type'  => 'data_processing',
            'purpose'       => 'Administração do sistema de ponto eletrônico e processamento de dados de funcionários',
            'legal_basis'   => 'LGPD Art. 7º, V - execução de contrato',
            'granted'       => true,
            'granted_at'    => date('Y-m-d H:i:s'),
            'ip_address'    => '127.0.0.1',
            'user_agent'    => 'System Seeder',
            'consent_text'  => 'Ao utilizar o sistema de ponto eletrônico como administrador, você concorda com o processamento de dados necessários para a gestão do sistema.',
            'version'       => '1.0',
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $db->table('user_consents')->insert($consentData);

        // Log in audit
        $auditData = [
            'user_id'      => $adminId,
            'action'       => 'CREATE',
            'entity_type'  => 'employees',
            'entity_id'    => $adminId,
            'new_values'   => json_encode([
                'name'  => 'Administrador do Sistema',
                'email' => 'admin@ponto.com.br',
                'role'  => 'admin',
            ]),
            'ip_address'   => '127.0.0.1',
            'user_agent'   => 'System Seeder',
            'description'  => 'Admin user created via database seeder',
            'level'        => 'info',
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        $db->table('audit_logs')->insert($auditData);

        echo "✅ Admin consent and audit log created\n";
    }
}
