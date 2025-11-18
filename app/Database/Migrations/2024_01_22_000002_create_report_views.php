<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create Views for Frequent Reports
 *
 * Creates optimized database views for commonly generated reports
 */
class CreateReportViews extends Migration
{
    public function up()
    {
        // ==================== View: Monthly Timesheet Summary ====================
        // Aggregates punch data by employee and month for fast timesheet reports

        $this->db->query("
            CREATE OR REPLACE VIEW v_monthly_timesheet AS
            SELECT
                e.id AS employee_id,
                e.name AS employee_name,
                e.department,
                e.position,
                DATE_FORMAT(tp.punch_time, '%Y-%m') AS month,
                COUNT(DISTINCT DATE(tp.punch_time)) AS days_worked,
                SUM(
                    CASE
                        WHEN tp.punch_type = 'entrada' THEN 1
                        ELSE 0
                    END
                ) AS entrance_count,
                MIN(
                    CASE
                        WHEN tp.punch_type = 'entrada' THEN tp.punch_time
                        ELSE NULL
                    END
                ) AS first_entrance,
                MAX(
                    CASE
                        WHEN tp.punch_type = 'saida' THEN tp.punch_time
                        ELSE NULL
                    END
                ) AS last_exit,
                AVG(tp.location_accuracy) AS avg_location_accuracy,
                SUM(
                    CASE
                        WHEN tp.within_geofence = 0 THEN 1
                        ELSE 0
                    END
                ) AS punches_outside_geofence
            FROM employees e
            LEFT JOIN time_punches tp ON e.id = tp.employee_id
            WHERE e.active = 1
            GROUP BY e.id, e.name, e.department, e.position, DATE_FORMAT(tp.punch_time, '%Y-%m')
        ");

        // ==================== View: Daily Attendance Status ====================
        // Shows which employees punched in today

        $this->db->query("
            CREATE OR REPLACE VIEW v_daily_attendance AS
            SELECT
                e.id AS employee_id,
                e.name AS employee_name,
                e.department,
                e.work_schedule_start AS expected_start,
                DATE(CURRENT_DATE) AS attendance_date,
                MIN(
                    CASE
                        WHEN tp.punch_type = 'entrada' AND DATE(tp.punch_time) = CURRENT_DATE
                        THEN tp.punch_time
                        ELSE NULL
                    END
                ) AS actual_entrance,
                MAX(
                    CASE
                        WHEN tp.punch_type = 'saida' AND DATE(tp.punch_time) = CURRENT_DATE
                        THEN tp.punch_time
                        ELSE NULL
                    END
                ) AS actual_exit,
                CASE
                    WHEN MIN(
                        CASE
                            WHEN tp.punch_type = 'entrada' AND DATE(tp.punch_time) = CURRENT_DATE
                            THEN tp.punch_time
                            ELSE NULL
                        END
                    ) IS NULL THEN 'absent'
                    WHEN TIME(MIN(
                        CASE
                            WHEN tp.punch_type = 'entrada' AND DATE(tp.punch_time) = CURRENT_DATE
                            THEN tp.punch_time
                            ELSE NULL
                        END
                    )) > ADDTIME(e.work_schedule_start, '00:10:00') THEN 'late'
                    ELSE 'on_time'
                END AS status
            FROM employees e
            LEFT JOIN time_punches tp ON e.id = tp.employee_id
            WHERE e.active = 1
            GROUP BY e.id, e.name, e.department, e.work_schedule_start
        ");

        // ==================== View: Employee Performance Summary ====================
        // Aggregates key metrics per employee

        $this->db->query("
            CREATE OR REPLACE VIEW v_employee_performance AS
            SELECT
                e.id AS employee_id,
                e.name AS employee_name,
                e.department,
                e.role,
                e.created_at AS hire_date,
                COUNT(DISTINCT DATE(tp.punch_time)) AS total_days_worked,
                COUNT(tp.id) AS total_punches,
                SUM(
                    CASE
                        WHEN tp.within_geofence = 0 THEN 1
                        ELSE 0
                    END
                ) AS out_of_geofence_count,
                COUNT(DISTINCT w.id) AS warning_count,
                COUNT(DISTINCT j.id) AS justification_count,
                SUM(
                    CASE
                        WHEN j.status = 'approved' THEN 1
                        ELSE 0
                    END
                ) AS approved_justifications,
                e.extra_hours_balance,
                e.owed_hours_balance
            FROM employees e
            LEFT JOIN time_punches tp ON e.id = tp.employee_id
            LEFT JOIN warnings w ON e.id = w.employee_id
            LEFT JOIN justifications j ON e.id = j.employee_id
            WHERE e.active = 1
            GROUP BY e.id, e.name, e.department, e.role, e.created_at,
                     e.extra_hours_balance, e.owed_hours_balance
        ");

        // ==================== View: Pending Approvals Summary ====================
        // Shows all items pending approval (justifications, etc.)

        $this->db->query("
            CREATE OR REPLACE VIEW v_pending_approvals AS
            SELECT
                'justification' AS item_type,
                j.id AS item_id,
                j.employee_id,
                e.name AS employee_name,
                e.department,
                j.justification_date AS item_date,
                j.justification_type,
                j.reason,
                j.created_at AS submitted_at,
                DATEDIFF(CURRENT_DATE, DATE(j.created_at)) AS days_pending
            FROM justifications j
            INNER JOIN employees e ON j.employee_id = e.id
            WHERE j.status = 'pending'

            UNION ALL

            SELECT
                'overtime_request' AS item_type,
                NULL AS item_id,
                NULL AS employee_id,
                NULL AS employee_name,
                NULL AS department,
                NULL AS item_date,
                NULL AS justification_type,
                NULL AS reason,
                NULL AS submitted_at,
                NULL AS days_pending
            FROM DUAL
            WHERE FALSE
        ");

        // ==================== View: Biometric Enrollment Status ====================
        // Shows which employees have biometric data enrolled

        $this->db->query("
            CREATE OR REPLACE VIEW v_biometric_status AS
            SELECT
                e.id AS employee_id,
                e.name AS employee_name,
                e.department,
                MAX(
                    CASE
                        WHEN bt.biometric_type = 'face' AND bt.active = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS has_facial,
                MAX(
                    CASE
                        WHEN bt.biometric_type = 'fingerprint' AND bt.active = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS has_fingerprint,
                COUNT(bt.id) AS total_templates,
                MAX(bt.created_at) AS last_enrollment_date
            FROM employees e
            LEFT JOIN biometric_templates bt ON e.id = bt.employee_id
            WHERE e.active = 1
            GROUP BY e.id, e.name, e.department
        ");

        log_message('info', 'Report views created successfully');
    }

    public function down()
    {
        // Drop all views
        $this->db->query('DROP VIEW IF EXISTS v_biometric_status');
        $this->db->query('DROP VIEW IF EXISTS v_pending_approvals');
        $this->db->query('DROP VIEW IF EXISTS v_employee_performance');
        $this->db->query('DROP VIEW IF EXISTS v_daily_attendance');
        $this->db->query('DROP VIEW IF EXISTS v_monthly_timesheet');

        log_message('info', 'Report views dropped');
    }
}
