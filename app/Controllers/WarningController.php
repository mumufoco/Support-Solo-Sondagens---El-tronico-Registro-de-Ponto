<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\WarningModel;
use App\Models\AuditLogModel;
use App\Services\NotificationService;

/**
 * Warning Controller
 *
 * Handles employee warnings/disciplinary actions (Managers/Admins only)
 */
class WarningController extends BaseController
{
    protected $employeeModel;
    protected $warningModel;
    protected $auditModel;
    protected $notificationService;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->warningModel = new WarningModel();
        $this->auditModel = new AuditLogModel();
        $this->notificationService = new NotificationService();
        helper(['form', 'datetime', 'format']);
    }

    /**
     * List warnings
     * GET /warnings
     */
    public function index()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado. Apenas gestores e administradores podem acessar advertências.');
        }

        $perPage = 20;
        $severity = $this->request->getGet('severity') ?? 'all';

        // Build query
        $query = $this->warningModel;

        // Filter by department (managers)
        if ($employee['role'] === 'gestor') {
            $employeeIds = $this->employeeModel
                ->where('department', $employee['department'])
                ->findColumn('id');
            $query->whereIn('employee_id', $employeeIds);
        }

        // Filter by severity
        if ($severity !== 'all') {
            $query->where('severity', $severity);
        }

        $warnings = $query->orderBy('issued_at', 'DESC')
            ->paginate($perPage);

        // Get employee names
        foreach ($warnings as &$warning) {
            $emp = $this->employeeModel->find($warning->employee_id);
            $warning->employee_name = $emp ? $emp->name : 'Desconhecido';

            $issuer = $this->employeeModel->find($warning->issued_by);
            $warning->issuer_name = $issuer ? $issuer->name : 'Desconhecido';
        }

        // Count by severity
        $counts = [
            'all' => $this->warningModel->countAllResults(false),
            'verbal' => $this->warningModel->where('severity', 'verbal')->countAllResults(false),
            'written' => $this->warningModel->where('severity', 'written')->countAllResults(false),
            'suspension' => $this->warningModel->where('severity', 'suspension')->countAllResults(false),
            'termination' => $this->warningModel->where('severity', 'termination')->countAllResults(false),
        ];

        return view('warnings/index', [
            'employee' => $employee,
            'warnings' => $warnings,
            'pager' => $this->warningModel->pager,
            'severity' => $severity,
            'counts' => $counts,
        ]);
    }

    /**
     * Show create form
     * GET /warnings/create
     */
    public function create()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        // Get employees (based on role)
        $query = $this->employeeModel->where('active', true);

        if ($employee['role'] === 'gestor') {
            $query->where('department', $employee['department']);
        }

        $employees = $query->orderBy('name', 'ASC')->findAll();

        return view('warnings/create', [
            'employee' => $employee,
            'employees' => $employees,
        ]);
    }

    /**
     * Store new warning
     * POST /warnings
     */
    public function store()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        // Validation rules
        $rules = [
            'employee_id' => 'required|integer',
            'severity' => 'required|in_list[verbal,written,suspension,termination]',
            'reason' => 'required|min_length[10]|max_length[1000]',
            'description' => 'required|min_length[20]|max_length[2000]',
            'action_taken' => 'permit_empty|max_length[1000]',
            'acknowledge_required' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $employeeId = $this->request->getPost('employee_id');

        // Check if manager can warn this employee
        if ($employee['role'] === 'gestor') {
            $targetEmployee = $this->employeeModel->find($employeeId);
            if (!$targetEmployee || $targetEmployee->department !== $employee['department']) {
                return redirect()->back()
                    ->with('error', 'Você só pode advertir funcionários do seu departamento.');
            }
        }

        // Create warning
        $data = [
            'employee_id' => $employeeId,
            'issued_by' => $employee['id'],
            'severity' => $this->request->getPost('severity'),
            'reason' => $this->request->getPost('reason'),
            'description' => $this->request->getPost('description'),
            'action_taken' => $this->request->getPost('action_taken'),
            'acknowledge_required' => $this->request->getPost('acknowledge_required') ? true : false,
            'acknowledged' => false,
            'issued_at' => date('Y-m-d H:i:s'),
        ];

        $warningId = $this->warningModel->insert($data);

        if (!$warningId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar advertência.');
        }

        // Log warning
        $this->auditModel->log(
            $employee['id'],
            'WARNING_ISSUED',
            'warnings',
            $warningId,
            null,
            $data,
            "Advertência {$data['severity']} emitida para funcionário ID {$employeeId}",
            'warning'
        );

        // Notify employee
        $targetEmployee = $this->employeeModel->find($employeeId);
        if ($targetEmployee) {
            $this->notificationService->create(
                $employeeId,
                'Nova Advertência',
                "Você recebeu uma advertência ({$data['severity']}). Verifique os detalhes.",
                'danger',
                '/warnings/' . $warningId
            );
        }

        return redirect()->to('/warnings')
            ->with('success', 'Advertência emitida com sucesso.');
    }

    /**
     * Show warning details
     * GET /warnings/{id}
     */
    public function show($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $warning = $this->warningModel->find($id);

        if (!$warning) {
            return redirect()->to('/warnings')
                ->with('error', 'Advertência não encontrada.');
        }

        // Check permissions
        if ($employee['role'] === 'funcionario' && $warning->employee_id !== $employee['id']) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado.');
        }

        if ($employee['role'] === 'gestor') {
            $warningEmployee = $this->employeeModel->find($warning->employee_id);
            if ($warningEmployee->department !== $employee['department']) {
                return redirect()->to('/warnings')
                    ->with('error', 'Acesso negado.');
            }
        }

        // Get employee and issuer data
        $warningEmployee = $this->employeeModel->find($warning->employee_id);
        $issuer = $this->employeeModel->find($warning->issued_by);

        return view('warnings/show', [
            'employee' => $employee,
            'warning' => $warning,
            'warningEmployee' => $warningEmployee,
            'issuer' => $issuer,
        ]);
    }

    /**
     * Acknowledge warning (Employee)
     * POST /warnings/{id}/acknowledge
     */
    public function acknowledge($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $warning = $this->warningModel->find($id);

        if (!$warning) {
            return redirect()->to('/warnings')
                ->with('error', 'Advertência não encontrada.');
        }

        // Only the warned employee can acknowledge
        if ($warning->employee_id !== $employee['id']) {
            return redirect()->to('/warnings/' . $id)
                ->with('error', 'Apenas o funcionário advertido pode reconhecer a advertência.');
        }

        if ($warning->acknowledged) {
            return redirect()->to('/warnings/' . $id)
                ->with('info', 'Advertência já foi reconhecida.');
        }

        // Update warning
        $this->warningModel->update($id, [
            'acknowledged' => true,
            'acknowledged_at' => date('Y-m-d H:i:s'),
            'acknowledge_comments' => $this->request->getPost('comments'),
        ]);

        // Log acknowledgment
        $this->auditModel->log(
            $employee['id'],
            'WARNING_ACKNOWLEDGED',
            'warnings',
            $id,
            ['acknowledged' => false],
            ['acknowledged' => true],
            "Advertência ID {$id} reconhecida pelo funcionário",
            'info'
        );

        // Notify issuer
        $this->notificationService->create(
            $warning->issued_by,
            'Advertência Reconhecida',
            "{$employee['name']} reconheceu a advertência emitida.",
            'info',
            '/warnings/' . $id
        );

        return redirect()->to('/warnings/' . $id)
            ->with('success', 'Advertência reconhecida com sucesso.');
    }

    /**
     * Delete warning (Admin only)
     * DELETE /warnings/{id}
     */
    public function delete($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')
                ->with('error', 'Apenas administradores podem excluir advertências.');
        }

        $warning = $this->warningModel->find($id);

        if (!$warning) {
            return redirect()->to('/warnings')
                ->with('error', 'Advertência não encontrada.');
        }

        // Log deletion
        $this->auditModel->log(
            $employee['id'],
            'WARNING_DELETED',
            'warnings',
            $id,
            (array) $warning,
            null,
            "Advertência ID {$id} excluída",
            'warning'
        );

        $this->warningModel->delete($id);

        return redirect()->to('/warnings')
            ->with('success', 'Advertência excluída com sucesso.');
    }

    /**
     * Get warnings for specific employee
     * GET /warnings/employee/{employeeId}
     */
    public function byEmployee($employeeId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $targetEmployee = $this->employeeModel->find($employeeId);

        if (!$targetEmployee) {
            return redirect()->to('/warnings')
                ->with('error', 'Funcionário não encontrado.');
        }

        // Check department permission for managers
        if ($employee['role'] === 'gestor' && $targetEmployee->department !== $employee['department']) {
            return redirect()->to('/warnings')
                ->with('error', 'Acesso negado.');
        }

        $warnings = $this->warningModel
            ->where('employee_id', $employeeId)
            ->orderBy('issued_at', 'DESC')
            ->findAll();

        // Add issuer names
        foreach ($warnings as &$warning) {
            $issuer = $this->employeeModel->find($warning->issued_by);
            $warning->issuer_name = $issuer ? $issuer->name : 'Desconhecido';
        }

        return view('warnings/by_employee', [
            'employee' => $employee,
            'targetEmployee' => $targetEmployee,
            'warnings' => $warnings,
        ]);
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
