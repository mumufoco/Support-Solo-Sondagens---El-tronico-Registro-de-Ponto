<?php

namespace App\Controllers\Employee;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Models\BiometricTemplateModel;
use App\Models\JustificationModel;

class EmployeeController extends BaseController
{
    protected $employeeModel;
    protected $timePunchModel;
    protected $biometricModel;
    protected $justificationModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->timePunchModel = new TimePunchModel();
        $this->biometricModel = new BiometricTemplateModel();
        $this->justificationModel = new JustificationModel();
    }

    /**
     * List all employees
     */
    public function index()
    {
        $this->requireManager();

        // Get filter parameters
        $department = $this->request->getGet('department');
        $role = $this->request->getGet('role');
        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');
        $filter = $this->request->getGet('filter');

        // Build query
        $query = $this->employeeModel;

        // Department filter (managers see only their department)
        if ($this->hasRole('gestor')) {
            $query->where('department', $this->currentUser->department);
        } elseif ($department) {
            $query->where('department', $department);
        }

        // Role filter
        if ($role) {
            $query->where('role', $role);
        }

        // Status filter
        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }

        // Special filters
        if ($filter === 'no_biometric') {
            $query->where('has_face_biometric', false)
                ->where('has_fingerprint_biometric', false);
        } elseif ($filter === 'pending_approval') {
            $query->where('active', false);
        }

        // Search
        if ($search) {
            $query->groupStart()
                ->like('name', $search)
                ->orLike('email', $search)
                ->orLike('cpf', $search)
                ->orLike('unique_code', $search)
                ->groupEnd();
        }

        // Paginate
        $employees = $query->orderBy('name', 'ASC')->paginate(20);

        $data = [
            'employees' => $employees,
            'pager' => $this->employeeModel->pager,
            'filters' => [
                'department' => $department,
                'role' => $role,
                'status' => $status,
                'search' => $search,
                'filter' => $filter,
            ],
        ];

        return view('employees/index', $data);
    }

    /**
     * Show employee details
     */
    public function show(int $id)
    {
        $this->requireManager();

        $employee = $this->employeeModel->find($id);

        if (!$employee) {
            $this->setError('Funcionário não encontrado.');
            return redirect()->to('/employees');
        }

        // Check department access for managers
        if ($this->hasRole('gestor') && $employee->department !== $this->currentUser->department) {
            $this->setError('Você não tem permissão para visualizar este funcionário.');
            return redirect()->to('/employees');
        }

        // Get additional data
        $data = [
            'employee' => $employee,
            'statistics' => $this->getEmployeeStatistics($id),
            'recentPunches' => $this->getRecentPunches($id),
            'biometricTemplates' => $this->getBiometricTemplates($id),
            'recentJustifications' => $this->getRecentJustifications($id),
        ];

        return view('employees/show', $data);
    }

    /**
     * Show create employee form
     */
    public function create()
    {
        $this->requireManager();

        return view('employees/create');
    }

    /**
     * Store new employee
     */
    public function store()
    {
        $this->requireManager();

        // Validate input
        $rules = [
            'name'       => 'required|min_length[3]|max_length[255]',
            'email'      => 'required|valid_email|is_unique[employees.email]',
            'cpf'        => 'required|exact_length[14]|is_unique[employees.cpf]',
            'password'   => 'required|min_length[8]',
            'role'       => 'required|in_list[admin,gestor,funcionario]',
            'department' => 'required|min_length[2]',
            'position'   => 'required|min_length[2]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Check if trying to create admin and user is not admin
        $role = $this->request->getPost('role');
        if ($role === 'admin' && !$this->hasRole('admin')) {
            $this->setError('Apenas administradores podem criar outros administradores.');
            return redirect()->back()->withInput();
        }

        // Prepare data
        $data = [
            'name'                 => $this->request->getPost('name'),
            'email'                => $this->request->getPost('email'),
            'cpf'                  => preg_replace('/[^0-9]/', '', $this->request->getPost('cpf')),
            'password'             => $this->request->getPost('password'),
            'role'                 => $role,
            'department'           => $this->request->getPost('department'),
            'position'             => $this->request->getPost('position'),
            'phone'                => $this->request->getPost('phone'),
            'admission_date'       => $this->request->getPost('admission_date'),
            'daily_hours'          => $this->request->getPost('daily_hours') ?: 8.00,
            'weekly_hours'         => $this->request->getPost('weekly_hours') ?: 44.00,
            'work_start_time'      => $this->request->getPost('work_start_time') ?: '08:00:00',
            'work_end_time'        => $this->request->getPost('work_end_time') ?: '18:00:00',
            'lunch_start_time'     => $this->request->getPost('lunch_start_time') ?: '12:00:00',
            'lunch_end_time'       => $this->request->getPost('lunch_end_time') ?: '13:00:00',
            'active'               => true,
        ];

        // Create employee
        $employeeId = $this->employeeModel->insert($data);

        if (!$employeeId) {
            $this->setError('Erro ao criar funcionário.');
            return redirect()->back()->withInput();
        }

        // Log creation
        $this->logAudit(
            'EMPLOYEE_CREATED',
            'employees',
            $employeeId,
            null,
            $data,
            "Funcionário criado: {$data['name']} ({$data['email']})"
        );

        $this->setSuccess('Funcionário criado com sucesso!');
        return redirect()->to('/employees/' . $employeeId);
    }

    /**
     * Show edit employee form
     */
    public function edit(int $id)
    {
        $this->requireManager();

        $employee = $this->employeeModel->find($id);

        if (!$employee) {
            $this->setError('Funcionário não encontrado.');
            return redirect()->to('/employees');
        }

        // Check department access for managers
        if ($this->hasRole('gestor') && $employee->department !== $this->currentUser->department) {
            $this->setError('Você não tem permissão para editar este funcionário.');
            return redirect()->to('/employees');
        }

        $data = [
            'employee' => $employee,
        ];

        return view('employees/edit', $data);
    }

    /**
     * Update employee
     */
    public function update(int $id)
    {
        $this->requireManager();

        $employee = $this->employeeModel->find($id);

        if (!$employee) {
            $this->setError('Funcionário não encontrado.');
            return redirect()->to('/employees');
        }

        // Check department access for managers
        if ($this->hasRole('gestor') && $employee->department !== $this->currentUser->department) {
            $this->setError('Você não tem permissão para editar este funcionário.');
            return redirect()->to('/employees');
        }

        // Validate input
        $rules = [
            'name'       => 'required|min_length[3]|max_length[255]',
            'email'      => "required|valid_email|is_unique[employees.email,id,{$id}]",
            'cpf'        => "required|exact_length[14]|is_unique[employees.cpf,id,{$id}]",
            'role'       => 'required|in_list[admin,gestor,funcionario]',
            'department' => 'required|min_length[2]',
            'position'   => 'required|min_length[2]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Check if trying to change to admin and user is not admin
        $role = $this->request->getPost('role');
        if ($role === 'admin' && !$this->hasRole('admin') && $employee->role !== 'admin') {
            $this->setError('Apenas administradores podem promover outros usuários a administrador.');
            return redirect()->back()->withInput();
        }

        // Prepare old values for audit
        $oldValues = [
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => $employee->role,
            'department' => $employee->department,
            'position' => $employee->position,
            'active' => $employee->active,
        ];

        // Prepare new data
        $data = [
            'name'                 => $this->request->getPost('name'),
            'email'                => $this->request->getPost('email'),
            'cpf'                  => preg_replace('/[^0-9]/', '', $this->request->getPost('cpf')),
            'role'                 => $role,
            'department'           => $this->request->getPost('department'),
            'position'             => $this->request->getPost('position'),
            'phone'                => $this->request->getPost('phone'),
            'admission_date'       => $this->request->getPost('admission_date'),
            'daily_hours'          => $this->request->getPost('daily_hours'),
            'weekly_hours'         => $this->request->getPost('weekly_hours'),
            'work_start_time'      => $this->request->getPost('work_start_time'),
            'work_end_time'        => $this->request->getPost('work_end_time'),
            'lunch_start_time'     => $this->request->getPost('lunch_start_time'),
            'lunch_end_time'       => $this->request->getPost('lunch_end_time'),
            'active'               => $this->request->getPost('active') === '1',
        ];

        // Update password if provided
        $password = $this->request->getPost('password');
        if ($password && strlen($password) >= 8) {
            $data['password'] = $password;
        }

        // Update employee
        $updated = $this->employeeModel->update($id, $data);

        if (!$updated) {
            $this->setError('Erro ao atualizar funcionário.');
            return redirect()->back()->withInput();
        }

        // Log update
        $this->logAudit(
            'EMPLOYEE_UPDATED',
            'employees',
            $id,
            $oldValues,
            $data,
            "Funcionário atualizado: {$data['name']}"
        );

        $this->setSuccess('Funcionário atualizado com sucesso!');
        return redirect()->to('/employees/' . $id);
    }

    /**
     * Delete employee (soft delete)
     */
    public function delete(int $id)
    {
        $this->requireRole('admin'); // Only admin can delete

        $employee = $this->employeeModel->find($id);

        if (!$employee) {
            return $this->respondError('Funcionário não encontrado.', null, 404);
        }

        // Cannot delete yourself
        if ($id === $this->currentUser->id) {
            return $this->respondError('Você não pode excluir sua própria conta.', null, 403);
        }

        // Soft delete
        $deleted = $this->employeeModel->delete($id);

        if (!$deleted) {
            return $this->respondError('Erro ao excluir funcionário.', null, 500);
        }

        // Log deletion
        $this->logAudit(
            'EMPLOYEE_DELETED',
            'employees',
            $id,
            ['name' => $employee->name, 'email' => $employee->email],
            null,
            "Funcionário excluído: {$employee->name} ({$employee->email})"
        );

        return $this->respondSuccess(null, 'Funcionário excluído com sucesso.');
    }

    /**
     * Activate employee
     */
    public function activate(int $id)
    {
        $this->requireManager();

        $employee = $this->employeeModel->find($id);

        if (!$employee) {
            return $this->respondError('Funcionário não encontrado.', null, 404);
        }

        // Update status
        $this->employeeModel->update($id, ['active' => true]);

        // Log activation
        $this->logAudit(
            'EMPLOYEE_ACTIVATED',
            'employees',
            $id,
            ['active' => false],
            ['active' => true],
            "Funcionário ativado: {$employee->name}"
        );

        return $this->respondSuccess(null, 'Funcionário ativado com sucesso.');
    }

    /**
     * Deactivate employee
     */
    public function deactivate(int $id)
    {
        $this->requireManager();

        $employee = $this->employeeModel->find($id);

        if (!$employee) {
            return $this->respondError('Funcionário não encontrado.', null, 404);
        }

        // Cannot deactivate yourself
        if ($id === $this->currentUser->id) {
            return $this->respondError('Você não pode desativar sua própria conta.', null, 403);
        }

        // Update status
        $this->employeeModel->update($id, ['active' => false]);

        // Log deactivation
        $this->logAudit(
            'EMPLOYEE_DEACTIVATED',
            'employees',
            $id,
            ['active' => true],
            ['active' => false],
            "Funcionário desativado: {$employee->name}"
        );

        return $this->respondSuccess(null, 'Funcionário desativado com sucesso.');
    }

    /**
     * My profile
     */
    public function profile()
    {
        $this->requireAuth();

        $data = [
            'employee' => $this->currentUser,
            'statistics' => $this->getEmployeeStatistics($this->currentUser->id),
            'recentPunches' => $this->getRecentPunches($this->currentUser->id),
        ];

        return view('employees/profile', $data);
    }

    /**
     * Update my profile
     */
    public function updateProfile()
    {
        $this->requireAuth();

        // Validate input (limited fields for self-update)
        $rules = [
            'phone' => 'permit_empty|max_length[20]',
            'password' => 'permit_empty|min_length[8]',
            'password_confirm' => 'permit_empty|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'phone' => $this->request->getPost('phone'),
        ];

        // Update password if provided
        $password = $this->request->getPost('password');
        if ($password && strlen($password) >= 8) {
            $data['password'] = $password;
        }

        // Update profile
        $updated = $this->employeeModel->update($this->currentUser->id, $data);

        if (!$updated) {
            $this->setError('Erro ao atualizar perfil.');
            return redirect()->back()->withInput();
        }

        // Log update
        $this->logAudit(
            'PROFILE_UPDATED',
            'employees',
            $this->currentUser->id,
            null,
            $data,
            'Perfil atualizado'
        );

        $this->setSuccess('Perfil atualizado com sucesso!');
        return redirect()->to('/employees/profile');
    }

    /**
     * Export employee data (LGPD compliance)
     */
    public function exportData(int $id)
    {
        $this->requireAuth();

        // Only allow exporting own data unless admin
        if ($id !== $this->currentUser->id && !$this->hasRole('admin')) {
            return $this->respondError('Você não tem permissão para exportar esses dados.', null, 403);
        }

        $employee = $this->employeeModel->find($id);

        if (!$employee) {
            return $this->respondError('Funcionário não encontrado.', null, 404);
        }

        // Get all related data
        $data = [
            'employee' => $employee,
            'punches' => $this->timePunchModel->where('employee_id', $id)->findAll(),
            'justifications' => $this->justificationModel->where('employee_id', $id)->findAll(),
            'biometric_templates' => $this->biometricModel->where('employee_id', $id)->findAll(),
        ];

        // Log export
        $this->logAudit(
            'DATA_EXPORTED',
            'employees',
            $id,
            null,
            null,
            "Dados exportados (LGPD): {$employee->name}"
        );

        // Return JSON
        return $this->respondSuccess($data, 'Dados exportados com sucesso.');
    }

    /**
     * Get employee statistics
     */
    protected function getEmployeeStatistics(int $employeeId): array
    {
        $thisMonth = date('Y-m');

        return [
            'total_punches' => $this->timePunchModel->where('employee_id', $employeeId)->countAllResults(),
            'punches_this_month' => $this->timePunchModel
                ->where('employee_id', $employeeId)
                ->where('DATE(punch_time) LIKE', $thisMonth . '%')
                ->countAllResults(),
            'total_justifications' => $this->justificationModel->where('employee_id', $employeeId)->countAllResults(),
            'pending_justifications' => $this->justificationModel
                ->where('employee_id', $employeeId)
                ->where('status', 'pending')
                ->countAllResults(),
            'has_biometric' => $this->biometricModel
                ->where('employee_id', $employeeId)
                ->where('is_active', true)
                ->countAllResults() > 0,
        ];
    }

    /**
     * Get recent punches
     */
    protected function getRecentPunches(int $employeeId): array
    {
        return $this->timePunchModel
            ->where('employee_id', $employeeId)
            ->orderBy('punch_time', 'DESC')
            ->limit(10)
            ->findAll();
    }

    /**
     * Get biometric templates
     */
    protected function getBiometricTemplates(int $employeeId): array
    {
        return $this->biometricModel
            ->where('employee_id', $employeeId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get recent justifications
     */
    protected function getRecentJustifications(int $employeeId): array
    {
        return $this->justificationModel
            ->where('employee_id', $employeeId)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();
    }
}
