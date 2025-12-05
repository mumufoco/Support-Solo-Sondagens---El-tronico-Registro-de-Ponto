<?php

namespace App\Controllers\Dashboard;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Models\JustificationModel;
use App\Models\WarningModel;
use App\Models\NotificationModel;
use App\Models\AuditLogModel;

class DashboardController extends BaseController
{
    protected $employeeModel;
    protected $timePunchModel;
    protected $justificationModel;
    protected $warningModel;
    protected $notificationModel;
    protected $auditModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->timePunchModel = new TimePunchModel();
        $this->justificationModel = new JustificationModel();
        $this->warningModel = new WarningModel();
        $this->notificationModel = new NotificationModel();
        $this->auditModel = new AuditLogModel();
    }

    /**
     * Main dashboard - redirects based on role
     */
    public function index()
    {
        $this->requireAuth();

        // Redirect to role-specific dashboard
        $role = $this->currentUser->role;

        switch ($role) {
            case 'admin':
                return redirect()->to('/dashboard/admin');
            case 'gestor':
                return redirect()->to('/dashboard/manager');
            case 'funcionario':
                return redirect()->to('/dashboard/employee');
            default:
                return redirect()->to('/dashboard/employee');
        }
    }

    /**
     * Admin dashboard
     */
    public function admin()
    {
        $this->requireRole('admin');

        // Get statistics
        $data = [
            'currentUser' => $this->currentUser,
            'statistics' => $this->getAdminStatistics(),
            'pendingApprovals' => $this->getPendingApprovals(),
            'recentActivities' => $this->getRecentActivities(),
            'systemAlerts' => $this->getSystemAlerts(),
            'notifications' => $this->getUserNotifications(),
        ];

        return view('dashboard/admin', $data);
    }

    /**
     * Manager dashboard
     */
    public function manager()
    {
        $this->requireRole('gestor');

        $department = $this->currentUser->department ?? 'default';
        $stats = $this->getManagerStatistics();

        // Get department employees
        $departmentEmployees = $this->employeeModel
            ->where('department', $department)
            ->where('active', true)
            ->findAll();

        // Calculate attendance rate
        $attendanceRate = count($departmentEmployees) > 0
            ? round(($stats['present_today'] / count($departmentEmployees)) * 100)
            : 0;

        $data = [
            'currentUser' => $this->currentUser,
            'teamStats' => [
                'total_employees' => count($departmentEmployees),
                'attendance_rate' => $attendanceRate,
                'pending_approvals' => $stats['pending_justifications'] ?? 0,
                'absent_today' => $stats['absent_today'] ?? 0,
            ],
            'pendingJustifications' => $this->getPendingJustifications(),
            'teamActivity' => $this->getTeamActivity($department),
            'alerts' => $this->getManagerAlerts($department),
            'notifications' => $this->getUserNotifications(),
        ];

        return view('dashboard/manager', $data);
    }

    /**
     * Employee dashboard
     */
    public function employee()
    {
        $this->requireAuth();

        $stats = $this->getEmployeeStatistics();
        $hoursBalance = $this->getHoursBalance();

        // Determine current status (clocked in/out)
        $todayPunches = $this->getTodayPunches();
        $lastPunch = !empty($todayPunches) ? end($todayPunches) : null;
        $currentStatus = ($lastPunch && $lastPunch->punch_type === 'entrada') ? 'clocked_in' : 'clocked_out';

        // Format hours balance
        $balanceNumeric = $hoursBalance['current_balance'];
        $balanceFormatted = ($balanceNumeric >= 0 ? '+' : '') . round($balanceNumeric, 1) . 'h';

        // Get employee's own data
        $data = [
            'currentUser' => $this->currentUser,
            'employeeData' => [
                'current_status' => $currentStatus,
            ],
            'employeeStats' => [
                'hours_worked_month' => round($stats['hours_worked_month'], 1) . 'h',
                'balance_hours' => $balanceFormatted,
                'balance_hours_numeric' => $balanceNumeric,
                'attendance_rate' => $this->calculateAttendanceRate($this->currentUser->id),
                'pending_justifications' => $stats['pending_justifications'] ?? 0,
            ],
            'todayPunches' => $this->formatTodayPunches($todayPunches),
            'weekSummary' => $this->getWeekSummary($this->currentUser->id),
            'upcomingEvents' => $this->getUpcomingEvents($this->currentUser->id),
            'notifications' => $this->formatNotifications($this->getUserNotifications()),
        ];

        return view('dashboard/employee', $data);
    }

    /**
     * Get admin statistics
     */
    protected function getAdminStatistics(): array
    {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');

        return [
            'total_employees' => $this->employeeModel->where('active', true)->countAllResults(),
            'total_inactive' => $this->employeeModel->where('active', false)->countAllResults(),
            'pending_registrations' => $this->employeeModel
                ->where('active', false)
                ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
                ->countAllResults(),
            'punches_today' => $this->timePunchModel->where('DATE(punch_time)', $today)->countAllResults(),
            'punches_month' => $this->timePunchModel->where('DATE(punch_time) LIKE', $thisMonth . '%')->countAllResults(),
            'pending_justifications' => $this->justificationModel->where('status', 'pending')->countAllResults(),
            'active_warnings' => $this->warningModel
                ->where('acknowledged', false)
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->countAllResults(),
            'employees_present' => $this->getEmployeesPresent(),
        ];
    }

    /**
     * Get manager statistics
     */
    protected function getManagerStatistics(): array
    {
        $today = date('Y-m-d');
        $department = $this->currentUser->department;

        return [
            'team_size' => $this->employeeModel
                ->where('department', $department)
                ->where('active', true)
                ->countAllResults(),
            'present_today' => $this->getEmployeesPresent($department),
            'absent_today' => $this->getAbsentEmployees($department),
            'late_today' => $this->getLateEmployees($department),
            'pending_justifications' => $this->justificationModel
                ->join('employees', 'employees.id = justifications.employee_id')
                ->where('employees.department', $department)
                ->where('justifications.status', 'pending')
                ->countAllResults(),
            'team_hours_month' => $this->getDepartmentHoursMonth($department),
        ];
    }

    /**
     * Get employee statistics
     */
    protected function getEmployeeStatistics(): array
    {
        $employeeId = $this->currentUser->id;
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');

        return [
            'punches_today' => $this->timePunchModel
                ->where('employee_id', $employeeId)
                ->where('DATE(punch_time)', $today)
                ->countAllResults(),
            'punches_month' => $this->timePunchModel
                ->where('employee_id', $employeeId)
                ->where('DATE(punch_time) LIKE', $thisMonth . '%')
                ->countAllResults(),
            'hours_worked_month' => $this->getHoursWorkedMonth($employeeId),
            'hours_balance' => $this->currentUser->hours_balance ?? 0,
            'pending_justifications' => $this->justificationModel
                ->where('employee_id', $employeeId)
                ->where('status', 'pending')
                ->countAllResults(),
            'late_count_month' => $this->getLateCountMonth($employeeId),
        ];
    }

    /**
     * Get pending approvals (admin)
     */
    protected function getPendingApprovals(): array
    {
        return [
            'employees' => $this->employeeModel
                ->where('active', false)
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->find(),
            'justifications' => $this->justificationModel
                ->select('justifications.*, employees.name as employee_name')
                ->join('employees', 'employees.id = justifications.employee_id')
                ->where('justifications.status', 'pending')
                ->orderBy('justifications.created_at', 'DESC')
                ->limit(5)
                ->find(),
        ];
    }

    /**
     * Get recent activities (admin)
     */
    protected function getRecentActivities(): array
    {
        return $this->auditModel
            ->select('audit_logs.*, employees.name as user_name')
            ->join('employees', 'employees.id = audit_logs.user_id', 'left')
            ->orderBy('audit_logs.created_at', 'DESC')
            ->limit(10)
            ->find();
    }

    /**
     * Get system alerts (admin)
     */
    protected function getSystemAlerts(): array
    {
        $alerts = [];

        // Check for critical errors in last 24 hours
        $criticalErrors = $this->auditModel
            ->where('level', 'critical')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->countAllResults();

        if ($criticalErrors > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$criticalErrors} erro(s) crítico(s) nas últimas 24 horas.",
                'link' => '/admin/logs?level=critical',
            ];
        }

        // Check for employees without biometric registration
        $noBiometric = $this->employeeModel
            ->where('active', true)
            ->where('has_face_biometric', false)
            ->where('has_fingerprint_biometric', false)
            ->countAllResults();

        if ($noBiometric > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$noBiometric} funcionário(s) sem cadastro biométrico.",
                'link' => '/employees?filter=no_biometric',
            ];
        }

        return $alerts;
    }

    /**
     * Get pending justifications (manager)
     */
    protected function getPendingJustifications(): array
    {
        return $this->justificationModel
            ->select('justifications.*, employees.name as employee_name, employees.position')
            ->join('employees', 'employees.id = justifications.employee_id')
            ->where('employees.department', $this->currentUser->department)
            ->where('justifications.status', 'pending')
            ->orderBy('justifications.created_at', 'ASC')
            ->limit(10)
            ->find();
    }

    /**
     * Get today's department punches (manager)
     */
    protected function getTodayDepartmentPunches(): array
    {
        $today = date('Y-m-d');

        return $this->timePunchModel
            ->select('time_punches.*, employees.name as employee_name, employees.position')
            ->join('employees', 'employees.id = time_punches.employee_id')
            ->where('employees.department', $this->currentUser->department)
            ->where('DATE(time_punches.punch_time)', $today)
            ->orderBy('time_punches.punch_time', 'DESC')
            ->limit(20)
            ->find();
    }

    /**
     * Get today's punches (employee)
     */
    protected function getTodayPunches(): array
    {
        $today = date('Y-m-d');

        return $this->timePunchModel
            ->where('employee_id', $this->currentUser->id)
            ->where('DATE(punch_time)', $today)
            ->orderBy('punch_time', 'ASC')
            ->findAll();
    }

    /**
     * Get hours balance
     */
    protected function getHoursBalance(): array
    {
        return [
            'current_balance' => $this->currentUser->hours_balance ?? 0,
            'extra_hours' => $this->currentUser->extra_hours_balance ?? 0,
            'owed_hours' => $this->currentUser->owed_hours_balance ?? 0,
        ];
    }

    /**
     * Get recent justifications
     */
    protected function getRecentJustifications(): array
    {
        return $this->justificationModel
            ->where('employee_id', $this->currentUser->id)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();
    }

    /**
     * Get active warnings
     */
    protected function getActiveWarnings(): array
    {
        return $this->warningModel
            ->where('employee_id', $this->currentUser->id)
            ->where('acknowledged', false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get user notifications
     */
    protected function getUserNotifications(): array
    {
        return $this->notificationModel
            ->where('employee_id', $this->currentUser->id)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();
    }

    /**
     * Get number of employees present
     */
    protected function getEmployeesPresent(?string $department = null): int
    {
        $today = date('Y-m-d');

        // Get employees who punched in today but haven't punched out yet
        $builder = $this->timePunchModel
            ->select('DISTINCT employee_id')
            ->where('DATE(punch_time)', $today)
            ->where('punch_type', 'entrada');

        if ($department) {
            $builder->join('employees', 'employees.id = time_punches.employee_id')
                ->where('employees.department', $department);
        }

        return $builder->countAllResults();
    }

    /**
     * Get number of absent employees
     */
    protected function getAbsentEmployees(string $department): int
    {
        $today = date('Y-m-d');

        // Get total active employees in department
        $totalEmployees = $this->employeeModel
            ->where('department', $department)
            ->where('active', true)
            ->countAllResults();

        // Subtract those who are present
        $present = $this->getEmployeesPresent($department);

        return max(0, $totalEmployees - $present);
    }

    /**
     * Get number of late employees today
     */
    protected function getLateEmployees(string $department): int
    {
        $today = date('Y-m-d');

        // Get employees who arrived after work_start_time
        return $this->timePunchModel
            ->select('DISTINCT time_punches.employee_id')
            ->join('employees', 'employees.id = time_punches.employee_id')
            ->where('employees.department', $department)
            ->where('DATE(time_punches.punch_time)', $today)
            ->where('time_punches.punch_type', 'entrada')
            ->where('TIME(time_punches.punch_time) > employees.work_start_time')
            ->countAllResults();
    }

    /**
     * Get total hours worked this month
     */
    protected function getHoursWorkedMonth(int $employeeId): float
    {
        $thisMonth = date('Y-m');

        $punches = $this->timePunchModel
            ->where('employee_id', $employeeId)
            ->where('DATE(punch_time) LIKE', $thisMonth . '%')
            ->orderBy('punch_time', 'ASC')
            ->findAll();

        return $this->timePunchModel->calculateTotalHours($punches);
    }

    /**
     * Get department total hours this month
     */
    protected function getDepartmentHoursMonth(string $department): float
    {
        $thisMonth = date('Y-m');

        $punches = $this->timePunchModel
            ->select('time_punches.*')
            ->join('employees', 'employees.id = time_punches.employee_id')
            ->where('employees.department', $department)
            ->where('DATE(time_punches.punch_time) LIKE', $thisMonth . '%')
            ->orderBy('time_punches.punch_time', 'ASC')
            ->findAll();

        return $this->timePunchModel->calculateTotalHours($punches);
    }

    /**
     * Get late count this month
     */
    protected function getLateCountMonth(int $employeeId): int
    {
        $thisMonth = date('Y-m');

        $employee = $this->employeeModel->find($employeeId);

        return $this->timePunchModel
            ->where('employee_id', $employeeId)
            ->where('DATE(punch_time) LIKE', $thisMonth . '%')
            ->where('punch_type', 'entrada')
            ->where('TIME(punch_time) >', $employee->work_start_time)
            ->countAllResults();
    }

    /**
     * Get team activity for manager dashboard
     */
    protected function getTeamActivity(string $department): array
    {
        $today = date('Y-m-d');

        $activities = $this->timePunchModel
            ->select('time_punches.*, employees.name as employee_name')
            ->join('employees', 'employees.id = time_punches.employee_id')
            ->where('employees.department', $department)
            ->where('DATE(time_punches.punch_time)', $today)
            ->orderBy('time_punches.punch_time', 'DESC')
            ->limit(10)
            ->findAll();

        return array_map(function ($activity) {
            return [
                'employee_name' => $activity->employee_name ?? 'Unknown',
                'action' => $this->formatPunchAction($activity->punch_type ?? 'entrada'),
                'timestamp' => $activity->punch_time ?? date('Y-m-d H:i:s'),
                'status' => 'active',
            ];
        }, $activities);
    }

    /**
     * Get manager alerts
     */
    protected function getManagerAlerts(string $department): array
    {
        $alerts = [];

        // Check for pending justifications
        $pendingCount = $this->justificationModel
            ->join('employees', 'employees.id = justifications.employee_id')
            ->where('employees.department', $department)
            ->where('justifications.status', 'pending')
            ->countAllResults();

        if ($pendingCount > 5) {
            $alerts[] = [
                'message' => "Você tem {$pendingCount} justificativas pendentes de aprovação.",
                'type' => 'warning',
            ];
        }

        return $alerts;
    }

    /**
     * Format today's punches for employee dashboard
     */
    protected function formatTodayPunches(array $punches): array
    {
        return array_map(function ($punch) {
            return [
                'type' => $punch->punch_type ?? 'entrada',
                'timestamp' => $punch->punch_time ?? date('Y-m-d H:i:s'),
                'location' => $punch->location ?? null,
                'status' => 'active',
            ];
        }, $punches);
    }

    /**
     * Get week summary for employee
     */
    protected function getWeekSummary(int $employeeId): array
    {
        $weekDays = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
        $summary = [];
        $today = date('N'); // 1 (Monday) to 7 (Sunday)

        for ($i = 1; $i <= 7; $i++) {
            $date = date('Y-m-d', strtotime("monday this week +{$i} days -1 day"));
            $punches = $this->timePunchModel
                ->where('employee_id', $employeeId)
                ->where('DATE(punch_time)', $date)
                ->findAll();

            $hours = !empty($punches) ? $this->timePunchModel->calculateTotalHours($punches) : 0;

            $summary[] = [
                'name' => $weekDays[$i - 1],
                'hours' => $hours > 0 ? round($hours, 1) . 'h' : '',
                'is_today' => ($i === $today),
            ];
        }

        return $summary;
    }

    /**
     * Get upcoming events for employee
     */
    protected function getUpcomingEvents(int $employeeId): array
    {
        // This is a placeholder - in production, you would fetch from a calendar/events table
        return [
            // ['title' => 'Reunião de equipe', 'date' => date('Y-m-d', strtotime('+2 days'))],
            // ['title' => 'Treinamento de segurança', 'date' => date('Y-m-d', strtotime('+5 days'))],
        ];
    }

    /**
     * Calculate attendance rate for employee
     */
    protected function calculateAttendanceRate(int $employeeId): string
    {
        $thisMonth = date('Y-m');
        $workDays = $this->getWorkDaysInMonth();

        $presentDays = $this->timePunchModel
            ->select('DISTINCT DATE(punch_time) as punch_date')
            ->where('employee_id', $employeeId)
            ->where('DATE(punch_time) LIKE', $thisMonth . '%')
            ->countAllResults();

        $rate = $workDays > 0 ? round(($presentDays / $workDays) * 100) : 100;

        return $rate . '%';
    }

    /**
     * Get work days in current month (excluding weekends)
     */
    protected function getWorkDaysInMonth(): int
    {
        $month = date('m');
        $year = date('Y');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $workDays = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayOfWeek = date('N', strtotime("$year-$month-$day"));
            if ($dayOfWeek < 6) { // Monday = 1, Friday = 5
                $workDays++;
            }
        }

        return $workDays;
    }

    /**
     * Format notifications for dashboard
     */
    protected function formatNotifications(array $notifications): array
    {
        return array_map(function ($notification) {
            return [
                'message' => $notification->message ?? 'Notification',
                'type' => $notification->type ?? 'info',
            ];
        }, array_slice($notifications, 0, 3));
    }

    /**
     * Format punch action for display
     */
    protected function formatPunchAction(string $punchType): string
    {
        $actions = [
            'entrada' => 'Registrou entrada',
            'saida' => 'Registrou saída',
            'intervalo_inicio' => 'Iniciou intervalo',
            'intervalo_fim' => 'Finalizou intervalo',
        ];

        return $actions[$punchType] ?? 'Registrou ponto';
    }
}
