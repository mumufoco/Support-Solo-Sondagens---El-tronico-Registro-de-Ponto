<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\JustificationModel;
use App\Models\EmployeeModel;
use App\Models\NotificationModel;
use App\Services\NotificationService;

/**
 * Justification Controller
 *
 * Handles employee justifications for absences, late arrivals, or missing punches
 */
class JustificationController extends BaseController
{
    protected $justificationModel;
    protected $employeeModel;
    protected $notificationModel;
    protected $notificationService;

    public function __construct()
    {
        $this->justificationModel = new JustificationModel();
        $this->employeeModel = new EmployeeModel();
        $this->notificationModel = new NotificationModel();
        $this->notificationService = new NotificationService();
        helper(['form', 'datetime', 'format']);
    }

    /**
     * List justifications
     * GET /justifications
     */
    public function index()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $perPage = 20;
        $status = $this->request->getGet('status') ?? 'all';

        $query = $this->justificationModel;

        // Filter by role
        if ($employee['role'] === 'funcionario') {
            // Employees see only their own justifications
            $query->where('employee_id', $employee['id']);
        } elseif ($employee['role'] === 'gestor') {
            // Managers see justifications from their department
            $employeeIds = $this->employeeModel
                ->where('department', $employee['department'])
                ->findColumn('id');
            $query->whereIn('employee_id', $employeeIds);
        }
        // Admins see all justifications

        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $justifications = $query->orderBy('created_at', 'DESC')
            ->paginate($perPage);

        // Get employee names
        foreach ($justifications as &$justification) {
            $emp = $this->employeeModel->find($justification->employee_id);
            $justification->employee_name = $emp ? $emp->name : 'Desconhecido';
        }

        // Count by status
        $counts = [
            'all' => $this->justificationModel->countAllResults(false),
            'pending' => $this->justificationModel->where('status', 'pending')->countAllResults(false),
            'approved' => $this->justificationModel->where('status', 'approved')->countAllResults(false),
            'rejected' => $this->justificationModel->where('status', 'rejected')->countAllResults(false),
        ];

        return view('justifications/index', [
            'employee' => $employee,
            'justifications' => $justifications,
            'pager' => $this->justificationModel->pager,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    /**
     * Show create form
     * GET /justifications/create
     */
    public function create()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $date = $this->request->getGet('date');

        return view('justifications/create', [
            'employee' => $employee,
            'date' => $date,
        ]);
    }

    /**
     * Store new justification
     * POST /justifications
     */
    public function store()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        // Validation rules
        $rules = [
            'date' => 'required|valid_date',
            'type' => 'required|in_list[absence,late,early_leave,missing_punch,other]',
            'reason' => 'required|min_length[10]|max_length[1000]',
            'attachment' => 'permit_empty|uploaded[attachment]|max_size[attachment,5120]|ext_in[attachment,pdf,jpg,jpeg,png]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Handle file upload
        $attachmentPath = null;
        $file = $this->request->getFile('attachment');

        if ($file && $file->isValid()) {
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/justifications', $newName);
            $attachmentPath = 'uploads/justifications/' . $newName;
        }

        // Create justification
        $data = [
            'employee_id' => $employee['id'],
            'date' => $this->request->getPost('date'),
            'type' => $this->request->getPost('type'),
            'reason' => $this->request->getPost('reason'),
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ];

        $justificationId = $this->justificationModel->insert($data);

        if (!$justificationId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar justificativa.');
        }

        // Notify managers
        $this->notifyManagers($employee, $justificationId);

        return redirect()->to('/justifications')
            ->with('success', 'Justificativa enviada com sucesso! Aguarde aprovação.');
    }

    /**
     * Show justification details
     * GET /justifications/{id}
     */
    public function show($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $justification = $this->justificationModel->find($id);

        if (!$justification) {
            return redirect()->to('/justifications')
                ->with('error', 'Justificativa não encontrada.');
        }

        // Check permissions
        if ($employee['role'] === 'funcionario' && $justification->employee_id !== $employee['id']) {
            return redirect()->to('/justifications')
                ->with('error', 'Acesso negado.');
        }

        if ($employee['role'] === 'gestor') {
            $justificationEmployee = $this->employeeModel->find($justification->employee_id);
            if ($justificationEmployee->department !== $employee['department']) {
                return redirect()->to('/justifications')
                    ->with('error', 'Acesso negado.');
            }
        }

        // Get employee data
        $justificationEmployee = $this->employeeModel->find($justification->employee_id);

        // Get reviewer data if approved/rejected
        $reviewer = null;
        if ($justification->reviewed_by) {
            $reviewer = $this->employeeModel->find($justification->reviewed_by);
        }

        return view('justifications/show', [
            'employee' => $employee,
            'justification' => $justification,
            'justificationEmployee' => $justificationEmployee,
            'reviewer' => $reviewer,
        ]);
    }

    /**
     * Approve justification (Managers/Admins only)
     * POST /justifications/{id}/approve
     */
    public function approve($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/justifications')
                ->with('error', 'Acesso negado.');
        }

        $justification = $this->justificationModel->find($id);

        if (!$justification) {
            return redirect()->to('/justifications')
                ->with('error', 'Justificativa não encontrada.');
        }

        // Managers can only approve from their department
        if ($employee['role'] === 'gestor') {
            $justificationEmployee = $this->employeeModel->find($justification->employee_id);
            if ($justificationEmployee->department !== $employee['department']) {
                return redirect()->to('/justifications')
                    ->with('error', 'Acesso negado.');
            }
        }

        // Update justification
        $this->justificationModel->update($id, [
            'status' => 'approved',
            'reviewed_by' => $employee['id'],
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_notes' => $this->request->getPost('notes'),
        ]);

        // Notify employee
        $this->notificationService->create(
            $justification->employee_id,
            'Justificativa Aprovada',
            'Sua justificativa de ' . format_date_br($justification->date) . ' foi aprovada.',
            'success',
            '/justifications/' . $id
        );

        return redirect()->to('/justifications/' . $id)
            ->with('success', 'Justificativa aprovada com sucesso.');
    }

    /**
     * Reject justification (Managers/Admins only)
     * POST /justifications/{id}/reject
     */
    public function reject($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/justifications')
                ->with('error', 'Acesso negado.');
        }

        $justification = $this->justificationModel->find($id);

        if (!$justification) {
            return redirect()->to('/justifications')
                ->with('error', 'Justificativa não encontrada.');
        }

        // Managers can only reject from their department
        if ($employee['role'] === 'gestor') {
            $justificationEmployee = $this->employeeModel->find($justification->employee_id);
            if ($justificationEmployee->department !== $employee['department']) {
                return redirect()->to('/justifications')
                    ->with('error', 'Acesso negado.');
            }
        }

        // Validation
        $notes = $this->request->getPost('notes');
        if (empty($notes)) {
            return redirect()->back()
                ->with('error', 'Informe o motivo da rejeição.');
        }

        // Update justification
        $this->justificationModel->update($id, [
            'status' => 'rejected',
            'reviewed_by' => $employee['id'],
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_notes' => $notes,
        ]);

        // Notify employee
        $this->notificationService->create(
            $justification->employee_id,
            'Justificativa Rejeitada',
            'Sua justificativa de ' . format_date_br($justification->date) . ' foi rejeitada. Motivo: ' . $notes,
            'danger',
            '/justifications/' . $id
        );

        return redirect()->to('/justifications/' . $id)
            ->with('success', 'Justificativa rejeitada.');
    }

    /**
     * Delete justification
     * DELETE /justifications/{id}
     */
    public function delete($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $justification = $this->justificationModel->find($id);

        if (!$justification) {
            return redirect()->to('/justifications')
                ->with('error', 'Justificativa não encontrada.');
        }

        // Only employee (if pending) or admin can delete
        if ($employee['role'] !== 'admin') {
            if ($justification->employee_id !== $employee['id'] || $justification->status !== 'pending') {
                return redirect()->to('/justifications')
                    ->with('error', 'Você só pode excluir justificativas pendentes.');
            }
        }

        // Delete attachment file if exists
        if ($justification->attachment_path && file_exists(WRITEPATH . $justification->attachment_path)) {
            unlink(WRITEPATH . $justification->attachment_path);
        }

        $this->justificationModel->delete($id);

        return redirect()->to('/justifications')
            ->with('success', 'Justificativa excluída com sucesso.');
    }

    /**
     * Notify managers about new justification
     */
    protected function notifyManagers($employee, $justificationId)
    {
        // Get managers and admins
        $managers = $this->employeeModel
            ->whereIn('role', ['admin', 'gestor'])
            ->where('active', true)
            ->findAll();

        foreach ($managers as $manager) {
            // Managers only get notified about their department
            if ($manager->role === 'gestor' && $manager->department !== $employee['department']) {
                continue;
            }

            $this->notificationService->create(
                $manager->id,
                'Nova Justificativa',
                $employee['name'] . ' enviou uma justificativa para aprovação.',
                'warning',
                '/justifications/' . $justificationId
            );
        }
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
