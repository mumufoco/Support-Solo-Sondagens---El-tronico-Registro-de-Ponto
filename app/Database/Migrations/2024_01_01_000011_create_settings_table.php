<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true,
                'comment'    => 'Chave única da configuração',
            ],
            'value' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Valor da configuração (pode ser JSON)',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['string', 'integer', 'boolean', 'json', 'encrypted'],
                'default'    => 'string',
                'comment'    => 'Tipo do valor',
            ],
            'group' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'default'    => 'general',
                'comment'    => 'Grupo da configuração (general, jornada, geolocation, etc)',
            ],
            'description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Descrição da configuração',
            ],
            'editable' => [
                'type'       => 'BOOLEAN',
                'default'    => true,
                'comment'    => 'Configuração editável pelo admin',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        // key already has unique index from field definition
        $this->forge->addKey(['group', 'key']);

        $this->forge->createTable('settings');
    }

    public function down()
    {
        $this->forge->dropTable('settings');
    }
}
