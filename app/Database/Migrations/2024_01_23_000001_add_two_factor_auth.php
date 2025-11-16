<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add Two-Factor Authentication
 *
 * Adds columns to employees table for 2FA functionality
 */
class AddTwoFactorAuth extends Migration
{
    public function up()
    {
        $fields = [
            'two_factor_enabled' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'comment' => '2FA habilitado (TOTP)',
            ],
            'two_factor_secret' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Secret do TOTP (Base32, encrypted)',
            ],
            'two_factor_backup_codes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Códigos de backup (JSON encrypted)',
            ],
            'two_factor_verified_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'comment' => 'Data de verificação do 2FA',
            ],
        ];

        $this->forge->addColumn('employees', $fields);

        // Add index for 2FA enabled employees
        $this->forge->addKey('two_factor_enabled');
        $this->db->query('CREATE INDEX idx_employees_2fa ON employees(two_factor_enabled, active)');
    }

    public function down()
    {
        // Drop index
        $this->db->query('DROP INDEX idx_employees_2fa ON employees');

        // Drop columns
        $this->forge->dropColumn('employees', [
            'two_factor_enabled',
            'two_factor_secret',
            'two_factor_backup_codes',
            'two_factor_verified_at',
        ]);
    }
}
