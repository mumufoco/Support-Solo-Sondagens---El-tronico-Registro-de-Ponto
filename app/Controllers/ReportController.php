<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Models\JustificationModel;
use App\Services\TimesheetService;

/**
 * Report Controller
 *
 * Handles generation of various reports (timesheet, attendance, etc.)
 */
class ReportController extends BaseController
{
    protected $employeeModel;
    protected $timePunchModel;
    protected $justificationModel;
    protected $timesheetService;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->timePunchModel = new TimePunchModel();
        $this->justificationModel = new JustificationModel();
        $this->timesheetService = new TimesheetService();
        helper(['datetime', 'format']);
    }

    /**
     * Reports dashboard
     * GET /reports
     */
    public function index()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado. Apenas gestores e administradores podem acessar relatórios.');
        }

        return view('reports/index', [
            'employee' => $employee,
        ]);
    }

    /**
     * Monthly timesheet report
     * GET /reports/timesheet
     */
    public function timesheet()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $month = $this->request->getGet('month') ?: date('Y-m');
        $employeeId = $this->request->getGet('employee_id');
        $department = $this->request->getGet('department');

        // Build query
        $query = $this->employeeModel->where('active', true);

        // Filter by department (managers see only their department)
        if ($employee['role'] === 'gestor') {
            $query->where('department', $employee['department']);
        } elseif ($department) {
            $query->where('department', $department);
        }

        // Filter by specific employee
        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        $employees = $query->findAll();

        // Generate timesheet for each employee
        $timesheets = [];
        foreach ($employees as $emp) {
            $timesheet = $this->timesheetService->generateMonthlyTimesheet($emp->id, $month);

            if ($timesheet['success']) {
                $timesheets[] = [
                    'employee' => $emp,
                    'summary' => $timesheet['summary'],
                    'daily_records' => $timesheet['daily_records'],
                ];
            }
        }

        // Get departments for filter
        $departments = $this->employeeModel
            ->distinct()
            ->select('department')
            ->where('active', true)
            ->findColumn('department');

        return view('reports/timesheet', [
            'employee' => $employee,
            'timesheets' => $timesheets,
            'month' => $month,
            'selectedEmployee' => $employeeId,
            'selectedDepartment' => $department,
            'departments' => $departments,
        ]);
    }

    /**
     * Attendance report
     * GET /reports/attendance
     */
    public function attendance()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $month = $this->request->getGet('month') ?: date('Y-m');
        $department = $this->request->getGet('department');

        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Build query
        $query = $this->employeeModel->where('active', true);

        if ($employee['role'] === 'gestor') {
            $query->where('department', $employee['department']);
        } elseif ($department) {
            $query->where('department', $department);
        }

        $employees = $query->findAll();

        // Calculate attendance for each employee
        $attendanceData = [];
        foreach ($employees as $emp) {
            $calculation = $this->timesheetService->calculateHoursWorked(
                $emp->id,
                $startDate,
                $endDate
            );

            $lateArrivals = $this->timesheetService->findLateArrivals(
                $emp->id,
                $startDate,
                $endDate
            );

            $missingPunches = $this->timesheetService->findMissingPunches(
                $emp->id,
                $startDate,
                $endDate
            );

            $attendanceData[] = [
                'employee' => $emp,
                'days_worked' => $calculation['total_days'],
                'hours_worked' => $calculation['total_hours'],
                'expected_hours' => $calculation['expected_hours'],
                'balance' => $calculation['balance'],
                'late_arrivals' => count($lateArrivals),
                'missing_punches' => count($missingPunches),
                'attendance_rate' => $calculation['expected_hours'] > 0
                    ? round(($calculation['total_hours'] / $calculation['expected_hours']) * 100, 1)
                    : 0,
            ];
        }

        // Sort by attendance rate (lowest first)
        usort($attendanceData, function($a, $b) {
            return $a['attendance_rate'] <=> $b['attendance_rate'];
        });

        // Get departments
        $departments = $this->employeeModel
            ->distinct()
            ->select('department')
            ->where('active', true)
            ->findColumn('department');

        return view('reports/attendance', [
            'employee' => $employee,
            'attendanceData' => $attendanceData,
            'month' => $month,
            'selectedDepartment' => $department,
            'departments' => $departments,
        ]);
    }

    /**
     * Late arrivals report
     * GET /reports/late-arrivals
     */
    public function lateArrivals()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $month = $this->request->getGet('month') ?: date('Y-m');
        $department = $this->request->getGet('department');

        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Build query
        $query = $this->employeeModel->where('active', true);

        if ($employee['role'] === 'gestor') {
            $query->where('department', $employee['department']);
        } elseif ($department) {
            $query->where('department', $department);
        }

        $employees = $query->findAll();

        // Find late arrivals for each employee
        $lateArrivalsData = [];
        foreach ($employees as $emp) {
            $lateArrivals = $this->timesheetService->findLateArrivals(
                $emp->id,
                $startDate,
                $endDate
            );

            if (!empty($lateArrivals)) {
                $lateArrivalsData[] = [
                    'employee' => $emp,
                    'late_arrivals' => $lateArrivals,
                    'total_count' => count($lateArrivals),
                ];
            }
        }

        // Sort by count (highest first)
        usort($lateArrivalsData, function($a, $b) {
            return $b['total_count'] <=> $a['total_count'];
        });

        // Get departments
        $departments = $this->employeeModel
            ->distinct()
            ->select('department')
            ->where('active', true)
            ->findColumn('department');

        return view('reports/late_arrivals', [
            'employee' => $employee,
            'lateArrivalsData' => $lateArrivalsData,
            'month' => $month,
            'selectedDepartment' => $department,
            'departments' => $departments,
        ]);
    }

    /**
     * Justifications report
     * GET /reports/justifications
     */
    public function justifications()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $month = $this->request->getGet('month') ?: date('Y-m');
        $department = $this->request->getGet('department');
        $status = $this->request->getGet('status') ?: 'all';

        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Build query
        $query = $this->justificationModel
            ->where('date >=', $startDate)
            ->where('date <=', $endDate);

        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by department
        if ($employee['role'] === 'gestor') {
            $employeeIds = $this->employeeModel
                ->where('department', $employee['department'])
                ->findColumn('id');
            $query->whereIn('employee_id', $employeeIds);
        } elseif ($department) {
            $employeeIds = $this->employeeModel
                ->where('department', $department)
                ->findColumn('id');
            $query->whereIn('employee_id', $employeeIds);
        }

        $justifications = $query->orderBy('created_at', 'DESC')->findAll();

        // Add employee data
        foreach ($justifications as &$justification) {
            $emp = $this->employeeModel->find($justification->employee_id);
            $justification->employee_name = $emp ? $emp->name : 'Desconhecido';
            $justification->employee_department = $emp ? $emp->department : 'N/A';
        }

        // Get departments
        $departments = $this->employeeModel
            ->distinct()
            ->select('department')
            ->where('active', true)
            ->findColumn('department');

        // Summary statistics
        $summary = [
            'total' => count($justifications),
            'pending' => count(array_filter($justifications, fn($j) => $j->status === 'pending')),
            'approved' => count(array_filter($justifications, fn($j) => $j->status === 'approved')),
            'rejected' => count(array_filter($justifications, fn($j) => $j->status === 'rejected')),
        ];

        return view('reports/justifications', [
            'employee' => $employee,
            'justifications' => $justifications,
            'summary' => $summary,
            'month' => $month,
            'selectedDepartment' => $department,
            'selectedStatus' => $status,
            'departments' => $departments,
        ]);
    }

    /**
     * Export timesheet to Excel
     * GET /reports/export/timesheet
     */
    public function exportTimesheet()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        // TODO: Implement Excel export using PhpSpreadsheet
        return redirect()->back()->with('info', 'Exportação para Excel em desenvolvimento.');
    }

    /**
     * Export to PDF
     * GET /reports/export/pdf
     */
    public function exportPDF()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        // TODO: Implement PDF export using TCPDF or mPDF
        return redirect()->back()->with('info', 'Exportação para PDF em desenvolvimento.');
    }

    /**
     * Get authenticated employee from session
     */
    protected function getAuthenticatedEmployee(): ?array
    {
        if (!session()->has('employee_id')) {
            return null;
        }

        $employeeId = session()->get('employee_id');
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return null;
        }

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => $employee->role,
            'department' => $employee->department,
        ];
    }
}
