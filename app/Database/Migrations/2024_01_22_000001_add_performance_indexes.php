<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Database Performance Optimization
 *
 * Adds composite indexes to frequently queried tables for better performance
 */
class AddPerformanceIndexes extends Migration
{
    /**
     * Helper method to add index only if it doesn't exist
     * Supports both MySQL and SQLite syntax
     */
    private function addIndexIfNotExists($table, $indexName, $columns)
    {
        try {
            if ($this->db->DBDriver === 'SQLite3') {
                // SQLite uses CREATE INDEX syntax
                $this->db->query("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} ({$columns})");
            } else {
                // MySQL/MariaDB uses ALTER TABLE ADD INDEX syntax
                $this->db->query("ALTER TABLE {$table} ADD INDEX {$indexName} ({$columns})");
            }
        } catch (\Exception $e) {
            // Ignore duplicate key errors (index already exists)
            if (strpos($e->getMessage(), 'Duplicate key name') === false &&
                strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }

    public function up()
    {
        // ==================== time_punches indexes ====================

        // Composite index for employee + date queries (most common)
        $this->addIndexIfNotExists('time_punches', 'idx_employee_date', 'employee_id, punch_time DESC');

        // Index for punch type filtering with date
        $this->addIndexIfNotExists('time_punches', 'idx_type_date', 'punch_type, punch_time DESC');

        // Index for geofence queries
        $this->addIndexIfNotExists('time_punches', 'idx_geofence', 'within_geofence, punch_time DESC');

        // Index for method + employee (useful for reports by method)
        $this->addIndexIfNotExists('time_punches', 'idx_employee_method', 'employee_id, method, punch_time DESC');

        // ==================== audit_logs indexes ====================

        // Composite index for user + action + date
        $this->addIndexIfNotExists('audit_logs', 'idx_user_action_date', 'user_id, action, created_at DESC');

        // Index for action filtering
        $this->addIndexIfNotExists('audit_logs', 'idx_action_date', 'action, created_at DESC');

        // Index for severity filtering (for alerts) - SKIPPED: severity column doesn't exist

        // Index for entity filtering (actual column names are entity_type, entity_id)
        $this->addIndexIfNotExists('audit_logs', 'idx_entity_type_id', 'entity_type, entity_id, created_at DESC');

        // ==================== chat_messages indexes ====================

        // Check if chat_messages table exists
        if ($this->db->tableExists('chat_messages')) {
            // Index for room-based messages (actual structure uses rooms, not direct recipient_id)
            $this->addIndexIfNotExists('chat_messages', 'idx_room_date', 'room_id, created_at DESC');

            // Index for sender + room
            $this->addIndexIfNotExists('chat_messages', 'idx_sender_room_date', 'sender_id, room_id, created_at DESC');
        }

        // ==================== employees indexes ====================

        // Index for active employees by department
        $this->addIndexIfNotExists('employees', 'idx_department_active', 'department, active, name');

        // Index for manager hierarchy queries - SKIPPED: manager_id column doesn't exist
        // $this->db->query('
        //     ALTER TABLE employees
        //     ADD INDEX idx_manager_active (manager_id, active)
        // ');

        // ==================== justifications indexes ====================

        // Index for employee + status + date
        $this->addIndexIfNotExists('justifications', 'idx_employee_status_date', 'employee_id, status, justification_date DESC');

        // Index for pending approvals
        $this->addIndexIfNotExists('justifications', 'idx_status_date', 'status, created_at DESC');

        // ==================== biometric_templates indexes ====================

        // Index for employee + type (for quick lookup) - column is biometric_type, not template_type
        $this->addIndexIfNotExists('biometric_templates', 'idx_employee_type', 'employee_id, biometric_type, active');

        // ==================== warnings indexes ====================

        // Index for employee + date (column is occurrence_date, not warning_date)
        $this->addIndexIfNotExists('warnings', 'idx_employee_date', 'employee_id, occurrence_date DESC');

        // Index for type + status (no severity column exists)
        $this->addIndexIfNotExists('warnings', 'idx_type_status', 'warning_type, status, occurrence_date DESC');

        log_message('info', 'Performance indexes created successfully');
    }

    /**
     * Helper method to drop index with database-specific syntax
     */
    private function dropIndexIfExists($indexName, $table = null)
    {
        try {
            if ($this->db->DBDriver === 'SQLite3') {
                // SQLite uses DROP INDEX syntax (table name not needed)
                $this->db->query("DROP INDEX IF EXISTS {$indexName}");
            } else {
                // MySQL/MariaDB uses DROP INDEX ON syntax
                $this->db->query("DROP INDEX {$indexName} ON {$table}");
            }
        } catch (\Exception $e) {
            // Silently ignore errors (index might not exist)
        }
    }

    public function down()
    {
        // Drop indexes in reverse order

        // warnings
        $this->dropIndexIfExists('idx_type_status', 'warnings');
        $this->dropIndexIfExists('idx_employee_date', 'warnings');

        // biometric_templates
        $this->dropIndexIfExists('idx_employee_type', 'biometric_templates');

        // justifications
        $this->dropIndexIfExists('idx_status_date', 'justifications');
        $this->dropIndexIfExists('idx_employee_status_date', 'justifications');

        // employees
        $this->dropIndexIfExists('idx_department_active', 'employees');

        // chat_messages (if exists)
        if ($this->db->tableExists('chat_messages')) {
            $this->dropIndexIfExists('idx_room_date', 'chat_messages');
            $this->dropIndexIfExists('idx_sender_room_date', 'chat_messages');
        }

        // audit_logs
        if ($this->db->tableExists('audit_logs')) {
            $this->dropIndexIfExists('idx_entity_type_id', 'audit_logs');
            $this->dropIndexIfExists('idx_action_date', 'audit_logs');
            $this->dropIndexIfExists('idx_user_action_date', 'audit_logs');
        }

        // time_punches
        $this->dropIndexIfExists('idx_employee_method', 'time_punches');
        $this->dropIndexIfExists('idx_geofence', 'time_punches');
        $this->dropIndexIfExists('idx_type_date', 'time_punches');
        $this->dropIndexIfExists('idx_employee_date', 'time_punches');

        log_message('info', 'Performance indexes dropped');
    }
}
