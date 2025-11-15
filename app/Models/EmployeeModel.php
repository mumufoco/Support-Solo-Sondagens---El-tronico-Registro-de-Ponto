<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table            = 'employees';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'email',
        'password',
        'cpf',
        'unique_code',
        'role',
        'department',
        'position',
        'expected_hours_daily',
        'work_schedule_start',
        'work_schedule_end',
        'active',
        'extra_hours_balance',
        'owed_hours_balance',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'name'     => 'required|min_length[3]|max_length[255]',
        'email'    => 'required|valid_email|is_unique[employees.email,id,{id}]',
        'password' => 'required|min_length[8]',
        'cpf'      => 'required|exact_length[14]|is_unique[employees.cpf,id,{id}]',
        'role'     => 'required|in_list[admin,gestor,funcionario]',
    ];

    protected $validationMessages = [
        'email' => [
            'required'    => 'O e-mail é obrigatório.',
            'valid_email' => 'Insira um e-mail válido.',
            'is_unique'   => 'Este e-mail já está cadastrado.',
        ],
        'cpf' => [
            'required'     => 'O CPF é obrigatório.',
            'exact_length' => 'O CPF deve ter 14 caracteres (XXX.XXX.XXX-XX).',
            'is_unique'    => 'Este CPF já está cadastrado.',
        ],
        'password' => [
            'required'   => 'A senha é obrigatória.',
            'min_length' => 'A senha deve ter no mínimo 8 caracteres.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword', 'generateUniqueCode'];
    protected $beforeUpdate   = ['hashPassword'];

    /**
     * Hash password before insert/update
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash(
                $data['data']['password'],
                PASSWORD_ARGON2ID
            );
        }

        return $data;
    }

    /**
     * Generate unique code before insert
     */
    protected function generateUniqueCode(array $data): array
    {
        if (!isset($data['data']['unique_code']) || empty($data['data']['unique_code'])) {
            do {
                $code = strtoupper(bin2hex(random_bytes(4)));
                $exists = $this->where('unique_code', $code)->first();
            } while ($exists);

            $data['data']['unique_code'] = $code;
        }

        return $data;
    }

    /**
     * Find employee by email
     */
    public function findByEmail(string $email): ?object
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Find employee by CPF
     */
    public function findByCPF(string $cpf): ?object
    {
        return $this->where('cpf', $cpf)->first();
    }

    /**
     * Find employee by unique code
     */
    public function findByCode(string $code): ?object
    {
        return $this->where('unique_code', $code)
            ->where('active', true)
            ->first();
    }

    /**
     * Get active employees
     */
    public function getActive(): array
    {
        return $this->where('active', true)->findAll();
    }

    /**
     * Get employees by role
     */
    public function getByRole(string $role): array
    {
        return $this->where('role', $role)
            ->where('active', true)
            ->findAll();
    }

    /**
     * Get employees by department
     */
    public function getByDepartment(string $department): array
    {
        return $this->where('department', $department)
            ->where('active', true)
            ->findAll();
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Update hours balance
     */
    public function updateBalance(int $employeeId, float $extraHours = 0, float $owedHours = 0): bool
    {
        $employee = $this->find($employeeId);

        if (!$employee) {
            return false;
        }

        return $this->update($employeeId, [
            'extra_hours_balance' => $employee->extra_hours_balance + $extraHours,
            'owed_hours_balance'  => $employee->owed_hours_balance + $owedHours,
        ]);
    }

    /**
     * Get employees with negative balance
     */
    public function getWithNegativeBalance(float $threshold = 0): array
    {
        return $this->where('owed_hours_balance >', $threshold)
            ->where('active', true)
            ->findAll();
    }

    /**
     * Get employees with positive balance
     */
    public function getWithPositiveBalance(float $threshold = 0): array
    {
        return $this->where('extra_hours_balance >', $threshold)
            ->where('active', true)
            ->findAll();
    }

    /**
     * Deactivate employee (soft delete alternative)
     */
    public function deactivate(int $employeeId): bool
    {
        return $this->update($employeeId, ['active' => false]);
    }

    /**
     * Activate employee
     */
    public function activate(int $employeeId): bool
    {
        return $this->update($employeeId, ['active' => true]);
    }

    /**
     * Get total active employees count
     */
    public function getTotalActive(): int
    {
        return $this->where('active', true)->countAllResults();
    }

    /**
     * Search employees by name, email or CPF
     */
    public function search(string $query): array
    {
        return $this->like('name', $query)
            ->orLike('email', $query)
            ->orLike('cpf', $query)
            ->where('active', true)
            ->findAll();
    }
}
