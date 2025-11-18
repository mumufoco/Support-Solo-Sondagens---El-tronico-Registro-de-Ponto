<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTimePunchesTable extends Migration
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
            'employee_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para employees',
            ],
            'punch_time' => [
                'type'    => 'DATETIME',
                'comment' => 'Data e hora da marcação',
            ],
            'punch_type' => [
                'type'       => 'ENUM',
                'constraint' => ['entrada', 'saida', 'intervalo-inicio', 'intervalo-fim'],
                'comment'    => 'Tipo de marcação',
            ],
            'method' => [
                'type'       => 'ENUM',
                'constraint' => ['codigo', 'qrcode', 'facial', 'biometria'],
                'comment'    => 'Método de registro utilizado',
            ],
            'nsr' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'unique'     => true,
                'comment'    => 'Número Sequencial de Registro (único global)',
            ],
            'hash' => [
                'type'       => 'VARCHAR',
                'constraint' => '64',
                'comment'    => 'Hash SHA-256 para integridade',
            ],
            'location_lat' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,8',
                'null'       => true,
                'comment'    => 'Latitude da marcação',
            ],
            'location_lng' => [
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
                'null'       => true,
                'comment'    => 'Longitude da marcação',
            ],
            'location_accuracy' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Precisão do GPS em metros',
            ],
            'within_geofence' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'comment' => 'Se estava dentro da cerca virtual',
            ],
            'geofence_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'comment'    => 'Nome da cerca virtual em que estava',
            ],
            'face_similarity' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,4',
                'null'       => true,
                'comment'    => 'Similaridade facial (0.0000 a 1.0000)',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
                'null'       => true,
                'comment'    => 'IP de origem (IPv4 ou IPv6)',
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'User agent do navegador',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Observações adicionais',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['employee_id', 'punch_time']);
        // nsr already has unique index from field definition
        $this->forge->addKey('punch_time');
        $this->forge->addKey(['employee_id', 'punch_type']);

        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('time_punches');
    }

    public function down()
    {
        $this->forge->dropTable('time_punches');
    }
}
