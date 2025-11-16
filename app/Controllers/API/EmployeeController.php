<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Models\JustificationModel;
use App\Services\TimesheetService;

/**
 * API Employee Controller
 *
 * Handles employee data via API
 */
class EmployeeController extends ResourceController
{
    protected $modelName = 'App\Models\EmployeeModel';
    protected $format = 'json';

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
        helper(['format', 'datetime']);
    }

    /**
     * Get employee profile
     * GET /api/employee/profile
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function profile()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'cpf' => format_cpf($employee->cpf),
                'role' => $employee->role,
                'department' => $employee->department,
                'position' => $employee->position,
                'unique_code' => $employee->unique_code,
                'phone' => $employee->phone ? format_phone_br($employee->phone) : null,
                'admission_date' => $employee->admission_date ? format_date_br($employee->admission_date) : null,
                'daily_hours' => $employee->daily_hours,
                'weekly_hours' => $employee->weekly_hours,
                'work_schedule' => [
                    'start' => format_time($employee->work_start_time),
                    'end' => format_time($employee->work_end_time),
                    'lunch_start' => format_time($employee->lunch_start_time),
                    'lunch_end' => format_time($employee->lunch_end_time),
                ],
                'biometric' => [
                    'has_face' => $employee->has_face_biometric,
                    'has_fingerprint' => $employee->has_fingerprint_biometric,
                ],
                'active' => $employee->active,
            ],
        ], 200);
    }

    /**
     * Get employee balance
     * GET /api/employee/balance
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function balance()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $balanceData = format_balance($employee->hours_balance ?? 0);

        return $this->respond([
            'success' => true,
            'data' => [
                'hours_balance' => $employee->hours_balance ?? 0,
                'hours_balance_formatted' => $balanceData['formatted'],
                'extra_hours_balance' => $employee->extra_hours_balance ?? 0,
                'owed_hours_balance' => $employee->owed_hours_balance ?? 0,
            ],
        ], 200);
    }

    /**
     * Get employee statistics
     * GET /api/employee/statistics?month=2024-01
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function statistics()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $month = $this->request->getGet('month') ?: date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Get hours calculation
        $calculation = $this->timesheetService->calculateHoursWorked(
            $employee->id,
            $startDate,
            $endDate
        );

        // Get late arrivals
        $lateArrivals = $this->timesheetService->findLateArrivals(
            $employee->id,
            $startDate,
            $endDate
        );

        // Get missing punches
        $missingPunches = $this->timesheetService->findMissingPunches(
            $employee->id,
            $startDate,
            $endDate
        );

        // Get justifications
        $justifications = $this->justificationModel
            ->where('employee_id', $employee->id)
            ->where('DATE(date) >=', $startDate)
            ->where('DATE(date) <=', $endDate)
            ->findAll();

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => [
                    'month' => format_month_year_br($month),
                    'start' => format_date_br($startDate),
                    'end' => format_date_br($endDate),
                ],
                'hours' => [
                    'worked' => $calculation['total_hours'],
                    'expected' => $calculation['expected_hours'],
                    'balance' => $calculation['balance'],
                    'average_per_day' => $calculation['average_hours_per_day'],
                ],
                'attendance' => [
                    'days_worked' => $calculation['total_days'],
                    'late_arrivals' => count($lateArrivals),
                    'missing_days' => count($missingPunches),
                ],
                'justifications' => [
                    'total' => count($justifications),
                    'pending' => count(array_filter($justifications, fn($j) => $j->status === 'pending')),
                    'approved' => count(array_filter($justifications, fn($j) => $j->status === 'approved')),
                    'rejected' => count(array_filter($justifications, fn($j) => $j->status === 'rejected')),
                ],
            ],
        ], 200);
    }

    /**
     * Update profile
     * PUT /api/employee/profile
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function updateProfile()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Validate input (limited fields for self-update)
        $rules = [
            'phone' => 'permit_empty|valid_phone_br',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        $data = [
            'phone' => $this->request->getPost('phone'),
        ];

        // Update profile
        $updated = $this->employeeModel->update($employee->id, $data);

        if (!$updated) {
            return $this->fail('Erro ao atualizar perfil.', 500);
        }

        return $this->respond([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso.',
        ], 200);
    }

    /**
     * Get team members (for managers)
     * GET /api/employee/team
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function team()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Only managers and admins
        if (!in_array($employee->role, ['admin', 'gestor'])) {
            return $this->fail('Acesso negado.', 403);
        }

        $query = $this->employeeModel->where('active', true);

        // Managers see only their department
        if ($employee->role === 'gestor') {
            $query->where('department', $employee->department);
        }

        $teamMembers = $query->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'success' => true,
            'data' => array_map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->role,
                    'department' => $member->department,
                    'position' => $member->position,
                    'unique_code' => $member->unique_code,
                ];
            }, $teamMembers),
        ], 200);
    }

    /**
     * Get employee by unique code (for managers)
     * GET /api/employee/by-code/{code}
     *
     * @param string $code
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function byCode($code = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Only managers and admins
        if (!in_array($employee->role, ['admin', 'gestor'])) {
            return $this->fail('Acesso negado.', 403);
        }

        $targetEmployee = $this->employeeModel->findByCode($code);

        if (!$targetEmployee) {
            return $this->fail('Funcionário não encontrado.', 404);
        }

        // Managers can only see employees from their department
        if ($employee->role === 'gestor' && $targetEmployee->department !== $employee->department) {
            return $this->fail('Acesso negado.', 403);
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'id' => $targetEmployee->id,
                'name' => $targetEmployee->name,
                'email' => $targetEmployee->email,
                'role' => $targetEmployee->role,
                'department' => $targetEmployee->department,
                'position' => $targetEmployee->position,
                'unique_code' => $targetEmployee->unique_code,
                'active' => $targetEmployee->active,
            ],
        ], 200);
    }

    /**
     * Get authenticated employee from AuthController
     *
     * @return object|null
     */
    protected function getAuthenticatedEmployee(): ?object
    {
        $authController = new \App\Controllers\API\AuthController();
        return $authController->getAuthenticatedEmployee();
    }
}
