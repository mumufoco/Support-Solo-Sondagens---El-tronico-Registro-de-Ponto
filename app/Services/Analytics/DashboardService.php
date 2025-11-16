<?php

namespace App\Services\Analytics;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Config\Services;

/**
 * Dashboard Analytics Service
 *
 * Provides KPIs and analytics data for dashboard
 *
 * Features:
 * - Employee statistics
 * - Timesheet analytics
 * - Punch activity tracking
 * - Department analytics
 * - Period-based filtering
 * - Real-time KPIs
 *
 * @package App\Services\Analytics
 */
class DashboardService
{
    /**
     * Database connection
     * @var ConnectionInterface
     */
    protected ConnectionInterface $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Services::database()->connect();
    }

    /**
     * Get dashboard overview KPIs
     *
     * @param string|null $startDate Start date (Y-m-d)
     * @param string|null $endDate End date (Y-m-d)
     * @param int|null $departmentId Department filter
     * @return array
     */
    public function getOverviewKPIs(?string $startDate = null, ?string $endDate = null, ?int $departmentId = null): array
    {
        // Default to today
        if (!$startDate) {
            $startDate = date('Y-m-d');
        }
        if (!$endDate) {
            $endDate = date('Y-m-d');
        }

        return [
            'total_employees' => $this->getTotalEmployees($departmentId),
            'active_employees' => $this->getActiveEmployees($departmentId),
            'punches_today' => $this->getPunchesCount($startDate, $endDate, $departmentId),
            'total_hours' => $this->getTotalHoursWorked($startDate, $endDate, $departmentId),
            'pending_approvals' => $this->getPendingApprovals($departmentId),
            'departments_count' => $this->getDepartmentsCount(),
            'avg_hours_per_employee' => $this->getAverageHoursPerEmployee($startDate, $endDate, $departmentId),
        ];
    }

    /**
     * Get total employees count
     *
     * @param int|null $departmentId
     * @return int
     */
    public function getTotalEmployees(?int $departmentId = null): int
    {
        $builder = $this->db->table('employees');

        if ($departmentId) {
            $builder->where('department_id', $departmentId);
        }

        return $builder->countAllResults();
    }

    /**
     * Get active employees count
     *
     * @param int|null $departmentId
     * @return int
     */
    public function getActiveEmployees(?int $departmentId = null): int
    {
        $builder = $this->db->table('employees')
            ->where('active', true);

        if ($departmentId) {
            $builder->where('department_id', $departmentId);
        }

        return $builder->countAllResults();
    }

    /**
     * Get punches count for period
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $departmentId
     * @return int
     */
    public function getPunchesCount(string $startDate, string $endDate, ?int $departmentId = null): int
    {
        $builder = $this->db->table('timesheets t')
            ->where('DATE(t.punch_time) >=', $startDate)
            ->where('DATE(t.punch_time) <=', $endDate);

        if ($departmentId) {
            $builder->join('employees e', 'e.id = t.employee_id')
                ->where('e.department_id', $departmentId);
        }

        return $builder->countAllResults();
    }

    /**
     * Get total hours worked in period
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $departmentId
     * @return float
     */
    public function getTotalHoursWorked(string $startDate, string $endDate, ?int $departmentId = null): float
    {
        $builder = $this->db->table('timesheets t')
            ->select('SUM(TIMESTAMPDIFF(SECOND, t.punch_time, t.punch_out_time) / 3600) as total_hours')
            ->where('DATE(t.punch_time) >=', $startDate)
            ->where('DATE(t.punch_time) <=', $endDate)
            ->where('t.punch_out_time IS NOT NULL');

        if ($departmentId) {
            $builder->join('employees e', 'e.id = t.employee_id')
                ->where('e.department_id', $departmentId);
        }

        $result = $builder->get()->getRow();

        return round((float) ($result->total_hours ?? 0), 2);
    }

    /**
     * Get pending approvals count
     *
     * @param int|null $departmentId
     * @return int
     */
    public function getPendingApprovals(?int $departmentId = null): int
    {
        $builder = $this->db->table('timesheets t')
            ->where('t.status', 'pending');

        if ($departmentId) {
            $builder->join('employees e', 'e.id = t.employee_id')
                ->where('e.department_id', $departmentId);
        }

        return $builder->countAllResults();
    }

    /**
     * Get departments count
     *
     * @return int
     */
    public function getDepartmentsCount(): int
    {
        return $this->db->table('departments')
            ->where('active', true)
            ->countAllResults();
    }

    /**
     * Get average hours per employee
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $departmentId
     * @return float
     */
    public function getAverageHoursPerEmployee(string $startDate, string $endDate, ?int $departmentId = null): float
    {
        $totalHours = $this->getTotalHoursWorked($startDate, $endDate, $departmentId);
        $activeEmployees = $this->getActiveEmployees($departmentId);

        if ($activeEmployees === 0) {
            return 0;
        }

        return round($totalHours / $activeEmployees, 2);
    }

    /**
     * Get punches by hour (for line chart)
     *
     * @param string $date Date (Y-m-d)
     * @param int|null $departmentId
     * @return array
     */
    public function getPunchesByHour(string $date, ?int $departmentId = null): array
    {
        $builder = $this->db->table('timesheets t')
            ->select('HOUR(t.punch_time) as hour, COUNT(*) as count')
            ->where('DATE(t.punch_time)', $date)
            ->groupBy('HOUR(t.punch_time)')
            ->orderBy('hour', 'ASC');

        if ($departmentId) {
            $builder->join('employees e', 'e.id = t.employee_id')
                ->where('e.department_id', $departmentId);
        }

        $results = $builder->get()->getResult();

        // Fill all hours (0-23) with 0 if no data
        $data = array_fill(0, 24, 0);

        foreach ($results as $row) {
            $data[(int) $row->hour] = (int) $row->count;
        }

        return [
            'labels' => array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23)),
            'data' => array_values($data),
        ];
    }

    /**
     * Get hours by department (for bar chart)
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getHoursByDepartment(string $startDate, string $endDate): array
    {
        $results = $this->db->table('timesheets t')
            ->select('d.name as department, SUM(TIMESTAMPDIFF(SECOND, t.punch_time, t.punch_out_time) / 3600) as hours')
            ->join('employees e', 'e.id = t.employee_id')
            ->join('departments d', 'd.id = e.department_id')
            ->where('DATE(t.punch_time) >=', $startDate)
            ->where('DATE(t.punch_time) <=', $endDate)
            ->where('t.punch_out_time IS NOT NULL')
            ->groupBy('d.id')
            ->orderBy('hours', 'DESC')
            ->get()
            ->getResult();

        $labels = [];
        $data = [];

        foreach ($results as $row) {
            $labels[] = $row->department;
            $data[] = round((float) $row->hours, 2);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get employee status distribution (for pie chart)
     *
     * @param int|null $departmentId
     * @return array
     */
    public function getEmployeeStatusDistribution(?int $departmentId = null): array
    {
        // Get employees currently working (have punch in but no punch out today)
        $workingBuilder = $this->db->table('employees e')
            ->select('COUNT(*) as count')
            ->join('timesheets t', 't.employee_id = e.id')
            ->where('DATE(t.punch_time)', date('Y-m-d'))
            ->where('t.punch_out_time IS NULL')
            ->where('e.active', true);

        if ($departmentId) {
            $workingBuilder->where('e.department_id', $departmentId);
        }

        $working = $workingBuilder->get()->getRow()->count ?? 0;

        // Get inactive employees
        $inactiveBuilder = $this->db->table('employees')
            ->where('active', false);

        if ($departmentId) {
            $inactiveBuilder->where('department_id', $departmentId);
        }

        $inactive = $inactiveBuilder->countAllResults();

        // Calculate available (active but not working)
        $totalActive = $this->getActiveEmployees($departmentId);
        $available = $totalActive - $working;

        return [
            'labels' => ['Trabalhando', 'Disponível', 'Inativo'],
            'data' => [$working, $available, $inactive],
            'colors' => ['#4CAF50', '#2196F3', '#9E9E9E'],
        ];
    }

    /**
     * Get recent activity
     *
     * @param int $limit
     * @param int|null $departmentId
     * @return array
     */
    public function getRecentActivity(int $limit = 10, ?int $departmentId = null): array
    {
        $builder = $this->db->table('timesheets t')
            ->select('t.id, t.employee_id, t.punch_time, t.punch_out_time, t.punch_type, e.name as employee_name, d.name as department_name')
            ->join('employees e', 'e.id = t.employee_id')
            ->join('departments d', 'd.id = e.department_id')
            ->orderBy('t.punch_time', 'DESC')
            ->limit($limit);

        if ($departmentId) {
            $builder->where('e.department_id', $departmentId);
        }

        $results = $builder->get()->getResult();

        return array_map(function ($row) {
            return [
                'id' => $row->id,
                'employee_id' => $row->employee_id,
                'employee_name' => $row->employee_name,
                'department' => $row->department_name,
                'punch_time' => $row->punch_time,
                'punch_out_time' => $row->punch_out_time,
                'punch_type' => $row->punch_type,
                'formatted_time' => date('d/m/Y H:i', strtotime($row->punch_time)),
            ];
        }, $results);
    }

    /**
     * Get top employees by hours worked
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @param int|null $departmentId
     * @return array
     */
    public function getTopEmployeesByHours(string $startDate, string $endDate, int $limit = 10, ?int $departmentId = null): array
    {
        $builder = $this->db->table('timesheets t')
            ->select('e.id, e.name, d.name as department, SUM(TIMESTAMPDIFF(SECOND, t.punch_time, t.punch_out_time) / 3600) as total_hours')
            ->join('employees e', 'e.id = t.employee_id')
            ->join('departments d', 'd.id = e.department_id')
            ->where('DATE(t.punch_time) >=', $startDate)
            ->where('DATE(t.punch_time) <=', $endDate)
            ->where('t.punch_out_time IS NOT NULL')
            ->groupBy('e.id')
            ->orderBy('total_hours', 'DESC')
            ->limit($limit);

        if ($departmentId) {
            $builder->where('e.department_id', $departmentId);
        }

        $results = $builder->get()->getResult();

        return array_map(function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'department' => $row->department,
                'total_hours' => round((float) $row->total_hours, 2),
            ];
        }, $results);
    }

    /**
     * Get attendance rate for period
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $departmentId
     * @return float Percentage (0-100)
     */
    public function getAttendanceRate(string $startDate, string $endDate, ?int $departmentId = null): float
    {
        // Calculate expected working days
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        $days = $interval->days + 1;

        // Get active employees
        $employeesCount = $this->getActiveEmployees($departmentId);

        if ($employeesCount === 0) {
            return 0;
        }

        // Expected punches (assuming 1 punch per day per employee)
        $expectedPunches = $employeesCount * $days;

        // Actual punches
        $actualPunches = $this->getPunchesCount($startDate, $endDate, $departmentId);

        // Calculate percentage
        $rate = ($actualPunches / $expectedPunches) * 100;

        return round(min($rate, 100), 2);
    }

    /**
     * Get all departments for filter
     *
     * @return array
     */
    public function getDepartments(): array
    {
        $results = $this->db->table('departments')
            ->select('id, name')
            ->where('active', true)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResult();

        return array_map(function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
            ];
        }, $results);
    }

    /**
     * Get complete dashboard data
     *
     * @param array $filters Filters: startDate, endDate, departmentId
     * @return array
     */
    public function getDashboardData(array $filters = []): array
    {
        $startDate = $filters['startDate'] ?? date('Y-m-d');
        $endDate = $filters['endDate'] ?? date('Y-m-d');
        $departmentId = $filters['departmentId'] ?? null;

        return [
            'kpis' => $this->getOverviewKPIs($startDate, $endDate, $departmentId),
            'charts' => [
                'punches_by_hour' => $this->getPunchesByHour($startDate, $departmentId),
                'hours_by_department' => $this->getHoursByDepartment($startDate, $endDate),
                'employee_status' => $this->getEmployeeStatusDistribution($departmentId),
            ],
            'recent_activity' => $this->getRecentActivity(10, $departmentId),
            'top_employees' => $this->getTopEmployeesByHours($startDate, $endDate, 10, $departmentId),
            'attendance_rate' => $this->getAttendanceRate($startDate, $endDate, $departmentId),
            'departments' => $this->getDepartments(),
            'filters' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'departmentId' => $departmentId,
            ],
        ];
    }
}
