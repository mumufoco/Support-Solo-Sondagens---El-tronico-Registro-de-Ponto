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
    public function up()
    {
        // ==================== time_punches indexes ====================

        // Composite index for employee + date queries (most common)
        $this->db->query('
            ALTER TABLE time_punches
            ADD INDEX idx_employee_date (employee_id, punch_time DESC)
        ');

        // Index for punch type filtering with date
        $this->db->query('
            ALTER TABLE time_punches
            ADD INDEX idx_type_date (punch_type, punch_time DESC)
        ');

        // Index for geofence queries
        $this->db->query('
            ALTER TABLE time_punches
            ADD INDEX idx_geofence (within_geofence, punch_time DESC)
        ');

        // Index for method + employee (useful for reports by method)
        $this->db->query('
            ALTER TABLE time_punches
            ADD INDEX idx_employee_method (employee_id, method, punch_time DESC)
        ');

        // ==================== audit_logs indexes ====================

        // Composite index for user + action + date
        $this->db->query('
            ALTER TABLE audit_logs
            ADD INDEX idx_user_action_date (user_id, action, created_at DESC)
        ');

        // Index for action filtering
        $this->db->query('
            ALTER TABLE audit_logs
            ADD INDEX idx_action_date (action, created_at DESC)
        ');

        // Index for severity filtering (for alerts)
        $this->db->query('
            ALTER TABLE audit_logs
            ADD INDEX idx_severity_date (severity, created_at DESC)
        ');

        // Index for table + record filtering
        $this->db->query('
            ALTER TABLE audit_logs
            ADD INDEX idx_table_record (table_name, record_id, created_at DESC)
        ');

        // ==================== chat_messages indexes ====================

        // Check if chat_messages table exists
        if ($this->db->tableExists('chat_messages')) {
            // Composite index for sender + recipient + date
            $this->db->query('
                ALTER TABLE chat_messages
                ADD INDEX idx_sender_recipient_date (sender_id, recipient_id, sent_at DESC)
            ');

            // Index for recipient + read status (for unread counts)
            $this->db->query('
                ALTER TABLE chat_messages
                ADD INDEX idx_recipient_read (recipient_id, is_read, sent_at DESC)
            ');

            // Index for room-based messages
            $this->db->query('
                ALTER TABLE chat_messages
                ADD INDEX idx_room_date (room_id, sent_at DESC)
            ');
        }

        // ==================== employees indexes ====================

        // Index for active employees by department
        $this->db->query('
            ALTER TABLE employees
            ADD INDEX idx_department_active (department, active, name)
        ');

        // Index for manager hierarchy queries
        $this->db->query('
            ALTER TABLE employees
            ADD INDEX idx_manager_active (manager_id, active)
        ');

        // ==================== justifications indexes ====================

        // Index for employee + status + date
        $this->db->query('
            ALTER TABLE justifications
            ADD INDEX idx_employee_status_date (employee_id, status, justification_date DESC)
        ');

        // Index for pending approvals
        $this->db->query('
            ALTER TABLE justifications
            ADD INDEX idx_status_date (status, created_at DESC)
        ');

        // ==================== biometric_templates indexes ====================

        // Index for employee + type (for quick lookup)
        $this->db->query('
            ALTER TABLE biometric_templates
            ADD INDEX idx_employee_type (employee_id, template_type, active)
        ');

        // ==================== warnings indexes ====================

        // Index for employee + date
        $this->db->query('
            ALTER TABLE warnings
            ADD INDEX idx_employee_date (employee_id, warning_date DESC)
        ');

        // Index for type + severity
        $this->db->query('
            ALTER TABLE warnings
            ADD INDEX idx_type_severity (warning_type, severity, warning_date DESC)
        ');

        log_message('info', 'Performance indexes created successfully');
    }

    public function down()
    {
        // Drop indexes in reverse order

        // warnings
        $this->db->query('DROP INDEX idx_type_severity ON warnings');
        $this->db->query('DROP INDEX idx_employee_date ON warnings');

        // biometric_templates
        $this->db->query('DROP INDEX idx_employee_type ON biometric_templates');

        // justifications
        $this->db->query('DROP INDEX idx_status_date ON justifications');
        $this->db->query('DROP INDEX idx_employee_status_date ON justifications');

        // employees
        $this->db->query('DROP INDEX idx_manager_active ON employees');
        $this->db->query('DROP INDEX idx_department_active ON employees');

        // chat_messages (if exists)
        if ($this->db->tableExists('chat_messages')) {
            $this->db->query('DROP INDEX idx_room_date ON chat_messages');
            $this->db->query('DROP INDEX idx_recipient_read ON chat_messages');
            $this->db->query('DROP INDEX idx_sender_recipient_date ON chat_messages');
        }

        // audit_logs
        $this->db->query('DROP INDEX idx_table_record ON audit_logs');
        $this->db->query('DROP INDEX idx_severity_date ON audit_logs');
        $this->db->query('DROP INDEX idx_action_date ON audit_logs');
        $this->db->query('DROP INDEX idx_user_action_date ON audit_logs');

        // time_punches
        $this->db->query('DROP INDEX idx_employee_method ON time_punches');
        $this->db->query('DROP INDEX idx_geofence ON time_punches');
        $this->db->query('DROP INDEX idx_type_date ON time_punches');
        $this->db->query('DROP INDEX idx_employee_date ON time_punches');

        log_message('info', 'Performance indexes dropped');
    }
}
