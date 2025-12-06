<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add Soft Delete Support to Schedules Table
 *
 * Adds deleted_at column to enable soft deletes for better audit trail
 * and ability to restore accidentally deleted schedules.
 */
class AddSoftDeleteToSchedules extends Migration
{
    public function up()
    {
        // Add deleted_at column to schedules table
        $fields = [
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'updated_at'
            ]
        ];

        $this->forge->addColumn('schedules', $fields);

        // Add index for soft delete queries
        if (!$this->db->indexExists('schedules', 'idx_schedule_deleted')) {
            $this->forge->addKey(['deleted_at'], false, false, 'idx_schedule_deleted');
            $this->forge->processIndexes('schedules');
        }

        echo "✓ Soft delete column added to schedules table\n";
    }

    public function down()
    {
        // Drop index first
        if ($this->db->indexExists('schedules', 'idx_schedule_deleted')) {
            $this->forge->dropKey('schedules', 'idx_schedule_deleted');
        }

        // Drop deleted_at column
        $this->forge->dropColumn('schedules', 'deleted_at');

        echo "✓ Soft delete column removed from schedules table\n";
    }
}
