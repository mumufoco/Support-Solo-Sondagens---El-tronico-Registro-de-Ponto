<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSystemSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'setting_key' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'setting_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'setting_type' => [
                'type' => 'ENUM',
                'constraint' => ['string', 'integer', 'boolean', 'json', 'file'],
                'default' => 'string',
            ],
            'setting_group' => [
                'type' => 'ENUM',
                'constraint' => ['appearance', 'authentication', 'certificate', 'system', 'security'],
                'default' => 'system',
            ],
            'is_encrypted' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('setting_key');
        $this->forge->addKey('setting_group');

        $this->forge->createTable('system_settings', true);

        // Insert default settings
        $this->insertDefaultSettings();
    }

    public function down()
    {
        $this->forge->dropTable('system_settings', true);
    }

    protected function insertDefaultSettings()
    {
        $now = date('Y-m-d H:i:s');

        $defaultSettings = [
            // Appearance
            [
                'setting_key' => 'company_name',
                'setting_value' => 'Sistema de Ponto Eletrônico',
                'setting_type' => 'string',
                'setting_group' => 'appearance',
                'description' => 'Nome da empresa',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'primary_color',
                'setting_value' => '#3B82F6',
                'setting_type' => 'string',
                'setting_group' => 'appearance',
                'description' => 'Cor primária do sistema',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'theme_mode',
                'setting_value' => 'light',
                'setting_type' => 'string',
                'setting_group' => 'appearance',
                'description' => 'Modo do tema (light/dark/auto)',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Authentication
            [
                'setting_key' => 'session_timeout',
                'setting_value' => '3600',
                'setting_type' => 'integer',
                'setting_group' => 'authentication',
                'description' => 'Tempo de sessão em segundos',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'enable_2fa',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'authentication',
                'description' => 'Habilitar autenticação de dois fatores',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'max_login_attempts',
                'setting_value' => '5',
                'setting_type' => 'integer',
                'setting_group' => 'authentication',
                'description' => 'Máximo de tentativas de login',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // System
            [
                'setting_key' => 'company_cnpj',
                'setting_value' => '',
                'setting_type' => 'string',
                'setting_group' => 'system',
                'description' => 'CNPJ da empresa',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'timezone',
                'setting_value' => 'America/Sao_Paulo',
                'setting_type' => 'string',
                'setting_group' => 'system',
                'description' => 'Fuso horário do sistema',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'language',
                'setting_value' => 'pt-BR',
                'setting_type' => 'string',
                'setting_group' => 'system',
                'description' => 'Idioma do sistema',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Security
            [
                'setting_key' => 'password_min_length',
                'setting_value' => '8',
                'setting_type' => 'integer',
                'setting_group' => 'security',
                'description' => 'Tamanho mínimo da senha',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'password_require_special',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'setting_group' => 'security',
                'description' => 'Exigir caracteres especiais na senha',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'enable_audit_log',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'setting_group' => 'security',
                'description' => 'Habilitar log de auditoria',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('system_settings')->insertBatch($defaultSettings);
    }
}
