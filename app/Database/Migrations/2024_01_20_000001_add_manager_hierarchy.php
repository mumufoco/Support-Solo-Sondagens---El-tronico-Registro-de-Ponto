<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add Manager Hierarchy
 *
 * Adds manager_id field to employees table to support hierarchical structure
 */
class AddManagerHierarchy extends Migration
{
    public function up()
    {
        // Add manager_id column
        $fields = [
            'manager_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'role',
                'comment'    => 'ID do gestor responsável (FK para employees)',
            ],
        ];

        $this->forge->addColumn('employees', $fields);

        // Add foreign key
        $this->db->query('
            ALTER TABLE employees
            ADD CONSTRAINT fk_employees_manager
            FOREIGN KEY (manager_id) REFERENCES employees(id)
            ON DELETE SET NULL
            ON UPDATE CASCADE
        ');

        // Add index for faster queries
        $this->db->query('
            CREATE INDEX idx_employees_manager ON employees(manager_id, active)
        ');
    }

    public function down()
    {
        // Drop foreign key
        $this->db->query('ALTER TABLE employees DROP FOREIGN KEY fk_employees_manager');

        // Drop index
        $this->db->query('DROP INDEX idx_employees_manager ON employees');

        // Drop column
        $this->forge->dropColumn('employees', 'manager_id');
    }
}
