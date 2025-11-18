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
     */
    private function addIndexIfNotExists($table, $indexName, $columns)
    {
        try {
            $this->db->query("
                ALTER TABLE {$table}
                ADD INDEX {$indexName} ({$columns})
            ");
        } catch (\Exception $e) {
            // Ignore duplicate key errors (index already exists)
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
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

    public function down()
    {
        // Drop indexes in reverse order

        // warnings
        try { $this->db->query('DROP INDEX idx_type_status ON warnings'); } catch (\Exception $e) {}
        try { $this->db->query('DROP INDEX idx_employee_date ON warnings'); } catch (\Exception $e) {}

        // biometric_templates
        $this->db->query('DROP INDEX idx_employee_type ON biometric_templates');

        // justifications
        $this->db->query('DROP INDEX idx_status_date ON justifications');
        $this->db->query('DROP INDEX idx_employee_status_date ON justifications');

        // employees
        // $this->db->query('DROP INDEX idx_manager_active ON employees');  // Not created
        $this->db->query('DROP INDEX idx_department_active ON employees');

        // chat_messages (if exists)
        if ($this->db->tableExists('chat_messages')) {
            try { $this->db->query('DROP INDEX idx_room_date ON chat_messages'); } catch (\Exception $e) {}
            try { $this->db->query('DROP INDEX idx_sender_room_date ON chat_messages'); } catch (\Exception $e) {}
        }

        // audit_logs
        if ($this->db->tableExists('audit_logs')) {
            try { $this->db->query('DROP INDEX idx_entity_type_id ON audit_logs'); } catch (\Exception $e) {}
            try { $this->db->query('DROP INDEX idx_action_date ON audit_logs'); } catch (\Exception $e) {}
            try { $this->db->query('DROP INDEX idx_user_action_date ON audit_logs'); } catch (\Exception $e) {}
        }

        // time_punches
        $this->db->query('DROP INDEX idx_employee_method ON time_punches');
        $this->db->query('DROP INDEX idx_geofence ON time_punches');
        $this->db->query('DROP INDEX idx_type_date ON time_punches');
        $this->db->query('DROP INDEX idx_employee_date ON time_punches');

        log_message('info', 'Performance indexes dropped');
    }
}
