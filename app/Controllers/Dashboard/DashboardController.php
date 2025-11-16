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

        // Get department employees
        $departmentEmployees = $this->employeeModel
            ->where('department', $this->currentUser->department)
            ->where('active', true)
            ->findAll();

        $data = [
            'currentUser' => $this->currentUser,
            'statistics' => $this->getManagerStatistics(),
            'departmentEmployees' => $departmentEmployees,
            'pendingJustifications' => $this->getPendingJustifications(),
            'todayPunches' => $this->getTodayDepartmentPunches(),
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

        // Get employee's own data
        $data = [
            'currentUser' => $this->currentUser,
            'statistics' => $this->getEmployeeStatistics(),
            'todayPunches' => $this->getTodayPunches(),
            'hoursBalance' => $this->getHoursBalance(),
            'recentJustifications' => $this->getRecentJustifications(),
            'warnings' => $this->getActiveWarnings(),
            'notifications' => $this->getUserNotifications(),
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
}
