<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para employees (pode ser null para ações do sistema)',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'comment'    => 'Ação realizada (CREATE, UPDATE, DELETE, VIEW, EXPORT, LOGIN, LOGOUT, etc)',
            ],
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'comment'    => 'Nome da tabela/entidade afetada',
            ],
            'entity_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'ID do registro afetado',
            ],
            'old_values' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => 'Valores antes da alteração (para UPDATE/DELETE)',
            ],
            'new_values' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => 'Valores após a alteração (para CREATE/UPDATE)',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
                'null'       => true,
                'comment'    => 'IP de origem (IPv4 ou IPv6)',
            ],
            'user_agent' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'User agent do navegador',
            ],
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'URL da requisição',
            ],
            'method' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'null'       => true,
                'comment'    => 'Método HTTP (GET, POST, PUT, DELETE)',
            ],
            'description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Descrição adicional da ação',
            ],
            'level' => [
                'type'       => 'ENUM',
                'constraint' => ['info', 'warning', 'error', 'critical'],
                'default'    => 'info',
                'comment'    => 'Nível de severidade',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'comment' => 'Data e hora da ação',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'action', 'created_at']);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey('created_at');
        $this->forge->addKey('action');
        $this->forge->addKey('level');

        $this->forge->addForeignKey('user_id', 'employees', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('audit_logs');
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs');
    }
}
