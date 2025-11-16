<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTimesheetConsolidatedTable extends Migration
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
            'date' => [
                'type'    => 'DATE',
                'comment' => 'Data do registro consolidado',
            ],
            'total_worked' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'comment'    => 'Total de horas trabalhadas no dia',
            ],
            'expected' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 8.00,
                'comment'    => 'Horas esperadas para o dia (jornada)',
            ],
            'extra' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'comment'    => 'Horas extras trabalhadas',
            ],
            'owed' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'comment'    => 'Horas devidas (faltaram trabalhar)',
            ],
            'interval_violation' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'comment'    => 'Horas de violação de intervalo (pagamento adicional)',
            ],
            'justified' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'comment'    => 'Possui justificativa aprovada?',
            ],
            'incomplete' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'comment'    => 'Marcações incompletas/inconsistentes?',
            ],
            'justification_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para justifications (se justified=true)',
            ],
            'punches_count' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => 'Quantidade de registros de ponto no dia',
            ],
            'first_punch' => [
                'type'    => 'TIME',
                'null'    => true,
                'comment' => 'Horário da primeira marcação (entrada)',
            ],
            'last_punch' => [
                'type'    => 'TIME',
                'null'    => true,
                'comment' => 'Horário da última marcação (saída)',
            ],
            'total_interval' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'comment'    => 'Total de horas de intervalo',
            ],
            'notes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Observações sobre o cálculo',
            ],
            'processed_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Quando foi processado pelo cron',
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
        $this->forge->addKey(['employee_id', 'date']);
        $this->forge->addKey('date');
        $this->forge->addKey('incomplete');
        $this->forge->addKey('justified');

        // Unique constraint: one record per employee per date
        $this->forge->addUniqueKey(['employee_id', 'date'], 'uk_employee_date');

        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('justification_id', 'justifications', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('timesheet_consolidated');
    }

    public function down()
    {
        $this->forge->dropTable('timesheet_consolidated');
    }
}
