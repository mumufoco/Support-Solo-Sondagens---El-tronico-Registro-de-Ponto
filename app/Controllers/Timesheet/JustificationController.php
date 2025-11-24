<?php

namespace App\Controllers\Timesheet;

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
        $builder = $this->justificationModel->builder();

        // Apply same filters as main query for accurate counts
        if ($employee['role'] === 'funcionario') {
            $builder->where('employee_id', $employee['id']);
        } elseif ($employee['role'] === 'gestor') {
            $employeeIds = $this->employeeModel
                ->where('department', $employee['department'])
                ->findColumn('id');
            $builder->whereIn('employee_id', $employeeIds);
        }

        $counts = [
            'all' => (clone $builder)->countAllResults(false),
            'pending' => (clone $builder)->where('status', 'pendente')->countAllResults(false),
            'approved' => (clone $builder)->where('status', 'aprovado')->countAllResults(false),
            'rejected' => (clone $builder)->where('status', 'rejeitado')->countAllResults(false),
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
            'justification_date' => [
                'rules' => 'required|valid_date',
                'errors' => [
                    'required' => 'A data é obrigatória.',
                    'valid_date' => 'Data inválida.',
                ]
            ],
            'justification_type' => [
                'rules' => 'required|in_list[falta,atraso,saida-antecipada]',
                'errors' => [
                    'required' => 'O tipo de justificativa é obrigatório.',
                    'in_list' => 'Tipo de justificativa inválido.',
                ]
            ],
            'category' => [
                'rules' => 'required|in_list[doenca,compromisso-pessoal,emergencia-familiar,outro]',
                'errors' => [
                    'required' => 'A categoria é obrigatória.',
                    'in_list' => 'Categoria inválida.',
                ]
            ],
            'reason' => [
                'rules' => 'required|min_length[50]|max_length[500]',
                'errors' => [
                    'required' => 'O motivo é obrigatório.',
                    'min_length' => 'O motivo deve ter no mínimo 50 caracteres.',
                    'max_length' => 'O motivo deve ter no máximo 500 caracteres.',
                ]
            ],
        ];

        // Validate date is not in future
        $justificationDate = $this->request->getPost('justification_date');
        if ($justificationDate && strtotime($justificationDate) > strtotime(date('Y-m-d'))) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Não é permitido justificar datas futuras.');
        }

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Handle multiple file uploads (max 3)
        $files = $this->request->getFiles();
        $attachmentPaths = [];
        $uploadErrors = [];

        if (isset($files['attachments'])) {
            $uploadedFiles = $files['attachments'];
            $fileCount = 0;

            foreach ($uploadedFiles as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $fileCount++;

                    // Max 3 files
                    if ($fileCount > 3) {
                        $uploadErrors[] = 'Máximo de 3 arquivos permitidos.';
                        break;
                    }

                    // Validate file size (5MB max)
                    if ($file->getSize() > 5 * 1024 * 1024) {
                        $uploadErrors[] = "Arquivo '{$file->getName()}' excede 5MB.";
                        continue;
                    }

                    // Validate file type
                    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                    if (!in_array($file->getExtension(), $allowedExtensions)) {
                        $uploadErrors[] = "Tipo de arquivo '{$file->getExtension()}' não permitido. Use: PDF, JPG ou PNG.";
                        continue;
                    }

                    // Create directory structure: YYYY/MM/employee_id/
                    $year = date('Y');
                    $month = date('m');
                    $uploadPath = WRITEPATH . "uploads/justifications/{$year}/{$month}/{$employee['id']}";

                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }

                    // Generate unique filename
                    $newName = uniqid() . '_' . $file->getRandomName();

                    // Move file
                    if ($file->move($uploadPath, $newName)) {
                        $attachmentPaths[] = "uploads/justifications/{$year}/{$month}/{$employee['id']}/{$newName}";
                    } else {
                        $uploadErrors[] = "Erro ao fazer upload de '{$file->getName()}'.";
                    }
                }
            }
        }

        // Return errors if any upload failed
        if (!empty($uploadErrors)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode('<br>', $uploadErrors));
        }

        // Determine status based on role
        // Gestor/Admin can approve directly when creating for themselves
        $status = 'pendente';
        $approvedBy = null;
        $approvedAt = null;

        if (in_array($employee['role'], ['admin', 'gestor'])) {
            $status = 'aprovado';
            $approvedBy = $employee['id'];
            $approvedAt = date('Y-m-d H:i:s');
        }

        // Create justification
        $data = [
            'employee_id' => $employee['id'],
            'justification_date' => $justificationDate,
            'justification_type' => $this->request->getPost('justification_type'),
            'category' => $this->request->getPost('category'),
            'reason' => $this->request->getPost('reason'),
            'attachments' => $attachmentPaths, // Model will encode to JSON
            'status' => $status,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
            'submitted_by' => $employee['id'],
        ];

        $justificationId = $this->justificationModel->insert($data);

        if (!$justificationId) {
            // Delete uploaded files on error
            foreach ($attachmentPaths as $path) {
                if (file_exists(WRITEPATH . $path)) {
                    unlink(WRITEPATH . $path);
                }
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar justificativa.');
        }

        // Log in audit
        if (class_exists('\App\Models\AuditLogModel')) {
            $auditModel = new \App\Models\AuditLogModel();
            $auditModel->log(
                $employee['id'],
                'JUSTIFICATION_CREATED',
                'justifications',
                $justificationId,
                null,
                $data,
                "Justificativa criada para {$justificationDate} (tipo: {$data['justification_type']})",
                'info'
            );
        }

        // Notify managers if pending
        if ($status === 'pendente') {
            $this->notifyManagers($employee, $justificationId);
        }

        $message = $status === 'aprovado'
            ? 'Justificativa criada e aprovada automaticamente.'
            : 'Justificativa enviada com sucesso! Aguarde aprovação.';

        return redirect()->to('/justifications')
            ->with('success', $message);
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
            'status' => 'aprovado',
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
            'status' => 'rejeitado',
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
            if ($justification->employee_id !== $employee['id'] || $justification->status !== 'pendente') {
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
        if (!session()->has('user_id')) {
            return null;
        }

        $employeeId = session()->get('user_id');
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
