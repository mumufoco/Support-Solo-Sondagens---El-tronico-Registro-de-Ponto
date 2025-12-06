<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add Performance Indexes to Schedules and Shifts Tables
 *
 * This migration adds composite indexes to improve query performance
 * for frequently used queries in the shift and schedule system.
 */
class AddSchedulePerformanceIndexes extends Migration
{
    public function up()
    {
        // Schedules table indexes

        // Index for calendar queries (date + employee lookup)
        // Used by: getScheduleByDateRange(), calendar views
        if (!$this->db->indexExists('schedules', 'idx_schedule_date_employee')) {
            $this->forge->addKey(['date', 'employee_id'], false, false, 'idx_schedule_date_employee');
            $this->forge->processIndexes('schedules');
        }

        // Index for shift coverage queries (date + shift lookup)
        // Used by: getShiftCoverage(), shift statistics
        if (!$this->db->indexExists('schedules', 'idx_schedule_date_shift')) {
            $this->forge->addKey(['date', 'shift_id'], false, false, 'idx_schedule_date_shift');
            $this->forge->processIndexes('schedules');
        }

        // Index for status-based queries
        // Used by: filtering by status, excluding cancelled schedules
        if (!$this->db->indexExists('schedules', 'idx_schedule_status_date')) {
            $this->forge->addKey(['status', 'date'], false, false, 'idx_schedule_status_date');
            $this->forge->processIndexes('schedules');
        }

        // Index for recurring schedule queries
        // Used by: getRecurringSchedules(), recurrence management
        if (!$this->db->indexExists('schedules', 'idx_schedule_recurring')) {
            $this->forge->addKey(['is_recurring', 'date'], false, false, 'idx_schedule_recurring');
            $this->forge->processIndexes('schedules');
        }

        // Work shifts table indexes

        // Index for active shifts by type
        // Used by: getShiftsByType(), filtering shifts
        if (!$this->db->indexExists('work_shifts', 'idx_shift_active_type')) {
            $this->forge->addKey(['active', 'type'], false, false, 'idx_shift_active_type');
            $this->forge->processIndexes('work_shifts');
        }

        // Index for soft delete queries
        // Used by: all queries that need to filter deleted shifts
        if (!$this->db->indexExists('work_shifts', 'idx_shift_deleted')) {
            $this->forge->addKey(['deleted_at'], false, false, 'idx_shift_deleted');
            $this->forge->processIndexes('work_shifts');
        }

        echo "✓ Performance indexes added successfully\n";
    }

    public function down()
    {
        // Drop schedules indexes
        if ($this->db->indexExists('schedules', 'idx_schedule_date_employee')) {
            $this->forge->dropKey('schedules', 'idx_schedule_date_employee');
        }

        if ($this->db->indexExists('schedules', 'idx_schedule_date_shift')) {
            $this->forge->dropKey('schedules', 'idx_schedule_date_shift');
        }

        if ($this->db->indexExists('schedules', 'idx_schedule_status_date')) {
            $this->forge->dropKey('schedules', 'idx_schedule_status_date');
        }

        if ($this->db->indexExists('schedules', 'idx_schedule_recurring')) {
            $this->forge->dropKey('schedules', 'idx_schedule_recurring');
        }

        // Drop work_shifts indexes
        if ($this->db->indexExists('work_shifts', 'idx_shift_active_type')) {
            $this->forge->dropKey('work_shifts', 'idx_shift_active_type');
        }

        if ($this->db->indexExists('work_shifts', 'idx_shift_deleted')) {
            $this->forge->dropKey('work_shifts', 'idx_shift_deleted');
        }

        echo "✓ Performance indexes removed\n";
    }
}
