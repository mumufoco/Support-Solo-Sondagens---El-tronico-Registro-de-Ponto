<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Models\JustificationModel;
use App\Models\TimesheetConsolidatedModel;
use App\Models\WarningModel;
use App\Models\AuditLogModel;
use App\Services\TimesheetService;
use App\Services\PDFService;
use App\Services\ExcelService;
use App\Services\CSVService;

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
    protected $consolidatedModel;
    protected $warningModel;
    protected $auditModel;
    protected $timesheetService;
    protected $pdfService;
    protected $excelService;
    protected $csvService;
    protected $cacheDir;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->timePunchModel = new TimePunchModel();
        $this->justificationModel = new JustificationModel();
        $this->consolidatedModel = new TimesheetConsolidatedModel();
        $this->warningModel = new WarningModel();
        $this->auditModel = new AuditLogModel();
        $this->timesheetService = new TimesheetService();
        $this->pdfService = new PDFService();
        $this->excelService = new ExcelService();
        $this->csvService = new CSVService();
        $this->cacheDir = WRITEPATH . 'cache/reports/';

        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

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
     * Generate report
     * POST /reports/generate
     */
    public function generate()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Acesso negado.'
            ])->setStatusCode(403);
        }

        // Get parameters
        $type = $this->request->getPost('type');
        $format = $this->request->getPost('format') ?? 'html';
        $filters = $this->request->getPost('filters') ?? [];

        // Validate type
        $validTypes = ['folha-ponto', 'horas-extras', 'faltas-atrasos', 'banco-horas',
                      'consolidado-mensal', 'justificativas', 'advertencias', 'personalizado'];

        if (!in_array($type, $validTypes)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Tipo de relatório inválido'
            ])->setStatusCode(400);
        }

        // Check cache
        $cacheKey = $this->getCacheKey($type, $filters);
        $cached = $this->getFromCache($cacheKey);

        if ($cached && $format === 'html') {
            return $this->response->setJSON([
                'success' => true,
                'data' => $cached['data'],
                'cached' => true
            ]);
        }

        // Generate data based on type
        $result = $this->generateReportData($type, $filters);

        if (!$result['success']) {
            return $this->response->setJSON($result)->setStatusCode(500);
        }

        $data = $result['data'];

        // Check if needs queue (>10,000 records)
        if (count($data) > 10000 && in_array($format, ['pdf', 'excel', 'csv'])) {
            // TODO: Implement queue for large reports
            return $this->response->setJSON([
                'success' => true,
                'queued' => true,
                'message' => 'Relatório muito grande. Será processado em background. Você receberá um email quando estiver pronto.',
                'job_id' => uniqid('report_')
            ]);
        }

        // Format output
        $output = $this->format($type, $data, $format, $filters);

        // Cache for 1 hour
        if ($format === 'html') {
            $this->saveToCache($cacheKey, ['data' => $data, 'filters' => $filters]);
        }

        // Log generation
        $this->auditModel->log(
            $employee['id'],
            'REPORT_GENERATED',
            'reports',
            null,
            null,
            ['type' => $type, 'format' => $format, 'filters' => $filters],
            "Relatório gerado: {$type} ({$format})",
            'info'
        );

        return $output;
    }

    /**
     * Format report output
     */
    protected function format(string $type, array $data, string $format, array $filters)
    {
        switch ($format) {
            case 'pdf':
                $result = $this->pdfService->generateReport($type, $data, $filters);
                if ($result['success']) {
                    return $this->response->download($result['filepath'], null)->setFileName($result['filename']);
                }
                return $this->response->setJSON($result)->setStatusCode(500);

            case 'excel':
                $result = $this->excelService->generateReport($type, $data, $filters);
                if ($result['success']) {
                    return $this->response->download($result['filepath'], null)->setFileName($result['filename']);
                }
                return $this->response->setJSON($result)->setStatusCode(500);

            case 'csv':
                $result = $this->csvService->generateReport($type, $data, $filters);
                if ($result['success']) {
                    return $this->response->download($result['filepath'], null)->setFileName($result['filename']);
                }
                return $this->response->setJSON($result)->setStatusCode(500);

            case 'json':
                return $this->response->setJSON([
                    'success' => true,
                    'data' => $data,
                    'filters' => $filters,
                    'generated_at' => date('Y-m-d H:i:s'),
                    'total_records' => count($data)
                ]);

            case 'html':
            default:
                return $this->response->setJSON([
                    'success' => true,
                    'data' => $data,
                    'filters' => $filters
                ]);
        }
    }

    /**
     * Generate report data based on type
     */
    protected function generateReportData(string $type, array $filters): array
    {
        try {
            switch ($type) {
                case 'folha-ponto':
                    return $this->generateTimesheetReport($filters);
                case 'horas-extras':
                    return $this->generateOvertimeReport($filters);
                case 'faltas-atrasos':
                    return $this->generateAbsenceReport($filters);
                case 'banco-horas':
                    return $this->generateBankHoursReport($filters);
                case 'consolidado-mensal':
                    return $this->generateMonthlyConsolidatedReport($filters);
                case 'justificativas':
                    return $this->generateJustificationsReport($filters);
                case 'advertencias':
                    return $this->generateWarningsReport($filters);
                case 'personalizado':
                    return $this->generateCustomReport($filters);
                default:
                    return ['success' => false, 'error' => 'Tipo inválido'];
            }
        } catch (\Exception $e) {
            log_message('error', 'Report generation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao gerar relatório',
                'details' => $e->getMessage()
            ];
        }
    }

    protected function generateTimesheetReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-t');

        $query = $this->consolidatedModel
            ->select('timesheet_consolidated.*, employees.name as employee_name, employees.department')
            ->join('employees', 'employees.id = timesheet_consolidated.employee_id')
            ->where('date >=', $startDate)
            ->where('date <=', $endDate);

        if (!empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }
        if (!empty($filters['department'])) {
            $query->where('employees.department', $filters['department']);
        }

        $data = $query->findAll();

        return ['success' => true, 'data' => $data];
    }

    protected function generateOvertimeReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-t');

        $query = $this->consolidatedModel
            ->select('timesheet_consolidated.*, employees.name as employee_name, employees.department')
            ->join('employees', 'employees.id = timesheet_consolidated.employee_id')
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->where('extra >', 0);

        if (!empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }
        if (!empty($filters['department'])) {
            $query->where('employees.department', $filters['department']);
        }

        $records = $query->findAll();

        // Add weekend detection
        foreach ($records as &$record) {
            $dayOfWeek = date('w', strtotime($record->date));
            $record->is_weekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
        }

        return ['success' => true, 'data' => $records];
    }

    protected function generateAbsenceReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-t');

        $query = $this->consolidatedModel
            ->select('timesheet_consolidated.*, employees.name as employee_name, employees.department')
            ->join('employees', 'employees.id = timesheet_consolidated.employee_id')
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->groupStart()
                ->where('incomplete', true)
                ->orWhere('owed >', 0)
            ->groupEnd();

        if (!empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }
        if (!empty($filters['department'])) {
            $query->where('employees.department', $filters['department']);
        }

        $records = $query->findAll();

        // Transform to absence format
        $data = [];
        foreach ($records as $record) {
            $type = $record->incomplete ? 'falta' : 'atraso';
            $data[] = [
                'date' => $record->date,
                'employee_name' => $record->employee_name,
                'department' => $record->department,
                'type' => $type,
                'punch_time' => $record->first_punch,
                'expected_time' => '08:00',
                'delay_minutes' => $record->owed * 60,
                'justified' => $record->justified
            ];
        }

        return ['success' => true, 'data' => $data];
    }

    protected function generateBankHoursReport(array $filters): array
    {
        $query = $this->employeeModel
            ->select('id, name, department, extra_hours_balance, owed_hours_balance')
            ->where('active', true);

        if (!empty($filters['employee_ids'])) {
            $query->whereIn('id', $filters['employee_ids']);
        }
        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        $records = $query->findAll();

        $data = [];
        foreach ($records as $record) {
            $data[] = [
                'employee_name' => $record->name,
                'department' => $record->department,
                'extra_hours_balance' => (float)$record->extra_hours_balance,
                'owed_hours_balance' => (float)$record->owed_hours_balance
            ];
        }

        return ['success' => true, 'data' => $data];
    }

    protected function generateMonthlyConsolidatedReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-t');

        $query = $this->consolidatedModel
            ->select('employee_id,
                     COUNT(*) as days_worked,
                     SUM(total_worked) as total_worked,
                     SUM(expected) as total_expected,
                     SUM(extra) as extra,
                     SUM(owed) as owed')
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->groupBy('employee_id');

        if (!empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }

        $records = $query->findAll();

        $data = [];
        foreach ($records as $record) {
            $employee = $this->employeeModel->find($record->employee_id);
            if ($employee) {
                $data[] = [
                    'employee_name' => $employee->name,
                    'department' => $employee->department,
                    'days_worked' => (int)$record->days_worked,
                    'total_worked' => (float)$record->total_worked,
                    'total_expected' => (float)$record->total_expected,
                    'extra' => (float)$record->extra,
                    'owed' => (float)$record->owed,
                    'late_count' => 0, // TODO: count from late_arrivals
                    'absence_count' => 0 // TODO: count from absences
                ];
            }
        }

        return ['success' => true, 'data' => $data];
    }

    protected function generateJustificationsReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-t');

        $query = $this->justificationModel
            ->where('justification_date >=', $startDate)
            ->where('justification_date <=', $endDate);

        if (!empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $records = $query->findAll();

        $data = [];
        foreach ($records as $record) {
            $employee = $this->employeeModel->find($record->employee_id);
            $data[] = [
                'justification_date' => $record->justification_date,
                'employee_name' => $employee ? $employee->name : 'Desconhecido',
                'justification_type' => $record->justification_type,
                'category' => $record->category,
                'reason' => $record->reason,
                'status' => $record->status,
                'has_attachments' => !empty($record->attachments),
                'created_at' => $record->created_at
            ];
        }

        return ['success' => true, 'data' => $data];
    }

    protected function generateWarningsReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-t');

        // Note: WarningModel might not exist yet, return empty for now
        $data = [];

        return ['success' => true, 'data' => $data];
    }

    protected function generateCustomReport(array $filters): array
    {
        // Custom SQL query from filters
        $data = [];

        return ['success' => true, 'data' => $data];
    }

    /**
     * Get cache key for filters
     */
    protected function getCacheKey(string $type, array $filters): string
    {
        return 'report_' . $type . '_' . md5(json_encode($filters));
    }

    /**
     * Get from cache
     */
    protected function getFromCache(string $key): ?array
    {
        $filepath = $this->cacheDir . $key . '.cache';

        if (!file_exists($filepath)) {
            return null;
        }

        // Check if expired (1 hour TTL)
        if (time() - filemtime($filepath) > 3600) {
            unlink($filepath);
            return null;
        }

        $content = file_get_contents($filepath);
        return json_decode($content, true);
    }

    /**
     * Save to cache
     */
    protected function saveToCache(string $key, array $data): void
    {
        $filepath = $this->cacheDir . $key . '.cache';
        file_put_contents($filepath, json_encode($data));
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
