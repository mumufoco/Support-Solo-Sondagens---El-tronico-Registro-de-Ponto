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
        'manager_id',
        'department',
        'position',
        'expected_hours_daily',
        'work_schedule_start',
        'work_schedule_end',
        'active',
        'extra_hours_balance',
        'owed_hours_balance',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_backup_codes',
        'two_factor_verified_at',
        'has_face_biometric',
        'has_fingerprint_biometric',
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
        'password' => 'required|min_length[12]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/]',
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
            'min_length' => 'A senha deve ter no mínimo 12 caracteres.',
            'regex_match' => 'A senha deve conter: letras maiúsculas, minúsculas, números e caracteres especiais (@$!%*?&).',
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

    /**
     * Generate QR Code for employee
     *
     * Creates a QR Code with signed payload for time punch registration
     *
     * @param int $employeeId
     * @return array ['success' => bool, 'qr_path' => string, 'qr_url' => string, 'qr_data' => string, 'expires_at' => string]
     */
    public function generateQRCode(int $employeeId): array
    {
        $employee = $this->find($employeeId);

        if (!$employee) {
            return [
                'success'  => false,
                'error'    => 'Funcionário não encontrado.',
                'qr_path'  => null,
                'qr_url'   => null,
                'qr_data'  => null,
            ];
        }

        // Create payload
        $timestamp = time();
        $payload = [
            'employee_id'  => $employee->id,
            'unique_code'  => $employee->unique_code,
            'generated_at' => $timestamp,
        ];

        // Generate HMAC signature
        // SECURITY FIX: Validate encryption key exists before generating signature
        $encryptionKey = env('encryption.key');
        if (empty($encryptionKey)) {
            log_message('error', 'QR Code generation failed: encryption.key not configured');
            throw new \RuntimeException('Sistema de criptografia não configurado. Contate o administrador.');
        }

        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadString, $encryptionKey);

        // QR data format: EMP-{id}-{timestamp}-{signature}
        $qrData = "EMP-{$employee->id}-{$timestamp}-{$signature}";

        // Create QR Code directory if not exists
        $qrDir = WRITEPATH . 'qrcodes';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }

        // Generate QR Code using chillerlan/php-qrcode
        $options = new \chillerlan\QRCode\QROptions([
            'version'          => 5,
            'outputType'       => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'         => \chillerlan\QRCode\QRCode::ECC_L, // Low error correction
            'scale'            => 10, // 400x400px approx
            'imageBase64'      => false,
        ]);

        $qrcode = new \chillerlan\QRCode\QRCode($options);
        $qrPath = $qrDir . "/employee_{$employee->id}.png";
        $qrcode->render($qrData, $qrPath);

        // Calculate expiration (5 minutes from now)
        $expiresAt = date('Y-m-d H:i:s', $timestamp + 300);

        return [
            'success'     => true,
            'qr_path'     => $qrPath,
            'qr_url'      => base_url('qrcode/' . $employee->id),
            'qr_data'     => $qrData,
            'expires_at'  => $expiresAt,
            'employee_id' => $employee->id,
            'unique_code' => $employee->unique_code,
        ];
    }

    /**
     * Get QR Code path for employee
     *
     * @param int $employeeId
     * @return string|null
     */
    public function getQRCodePath(int $employeeId): ?string
    {
        $qrPath = WRITEPATH . "qrcodes/employee_{$employeeId}.png";

        if (file_exists($qrPath)) {
            return $qrPath;
        }

        return null;
    }

    // ==================== Manager Hierarchy Methods ====================

    /**
     * Get all direct subordinates of a manager
     *
     * @param int $managerId Manager's employee ID
     * @param bool $activeOnly Only active employees (default: true)
     * @return array List of subordinate employees
     */
    public function getDirectSubordinates(int $managerId, bool $activeOnly = true): array
    {
        $builder = $this->where('manager_id', $managerId);

        if ($activeOnly) {
            $builder->where('active', true);
        }

        return $builder->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Get all subordinates recursively (entire hierarchy below manager)
     *
     * Uses recursive CTE (Common Table Expression) for efficient hierarchical query
     *
     * @param int $managerId Manager's employee ID
     * @param bool $activeOnly Only active employees (default: true)
     * @return array List of all subordinates (direct + indirect)
     */
    public function getAllSubordinates(int $managerId, bool $activeOnly = true): array
    {
        // Build conditions and params array for secure parameterized query
        $params = [$managerId];
        $baseActiveCondition = '';
        $recursiveActiveCondition = '';

        if ($activeOnly) {
            $baseActiveCondition = 'AND e.active = ?';
            $recursiveActiveCondition = 'AND e.active = ?';
            $params[] = 1;  // for base case
            $params[] = 1;  // for recursive case
        }

        // Recursive CTE to get entire hierarchy - SAFE: All params are bound
        $sql = "
            WITH RECURSIVE subordinates AS (
                -- Base case: direct reports
                SELECT
                    id, name, email, role, department, position, manager_id, active, 1 as level
                FROM employees e
                WHERE manager_id = ? {$baseActiveCondition}

                UNION ALL

                -- Recursive case: reports of reports
                SELECT
                    e.id, e.name, e.email, e.role, e.department, e.position, e.manager_id, e.active, s.level + 1
                FROM employees e
                INNER JOIN subordinates s ON e.manager_id = s.id
                WHERE 1=1 {$recursiveActiveCondition}
            )
            SELECT * FROM subordinates
            ORDER BY level, name
        ";

        $query = $this->db->query($sql, $params);

        return $query->getResultArray();
    }

    /**
     * Get IDs of all subordinates (useful for WHERE IN queries)
     *
     * @param int $managerId Manager's employee ID
     * @param bool $activeOnly Only active employees (default: true)
     * @return array Array of employee IDs
     */
    public function getSubordinateIds(int $managerId, bool $activeOnly = true): array
    {
        $subordinates = $this->getAllSubordinates($managerId, $activeOnly);

        return array_column($subordinates, 'id');
    }

    /**
     * Check if an employee is subordinate to a manager (directly or indirectly)
     *
     * @param int $employeeId Employee to check
     * @param int $managerId Manager to check against
     * @return bool True if employee is subordinate
     */
    public function isSubordinateTo(int $employeeId, int $managerId): bool
    {
        $subordinateIds = $this->getSubordinateIds($managerId, false);

        return in_array($employeeId, $subordinateIds);
    }

    /**
     * Get manager chain for an employee (up to top of hierarchy)
     *
     * @param int $employeeId Employee ID
     * @return array Array of managers from direct to top-level
     */
    public function getManagerChain(int $employeeId): array
    {
        $sql = "
            WITH RECURSIVE managers AS (
                -- Base case: employee's direct manager
                SELECT
                    e2.id, e2.name, e2.email, e2.role, e2.department, e2.manager_id, 1 as level
                FROM employees e1
                LEFT JOIN employees e2 ON e1.manager_id = e2.id
                WHERE e1.id = ? AND e2.id IS NOT NULL

                UNION ALL

                -- Recursive case: manager's manager
                SELECT
                    e.id, e.name, e.email, e.role, e.department, e.manager_id, m.level + 1
                FROM employees e
                INNER JOIN managers m ON e.id = m.manager_id
            )
            SELECT * FROM managers
            ORDER BY level
        ";

        $query = $this->db->query($sql, [$employeeId]);

        return $query->getResultArray();
    }

    /**
     * Get team statistics for a manager
     *
     * @param int $managerId Manager ID
     * @return array Statistics about team
     */
    public function getTeamStats(int $managerId): array
    {
        $subordinateIds = $this->getSubordinateIds($managerId, true);

        if (empty($subordinateIds)) {
            return [
                'total_count' => 0,
                'by_department' => [],
                'by_role' => [],
                'active_count' => 0,
            ];
        }

        $subordinates = $this->whereIn('id', $subordinateIds)->findAll();

        // Count by department
        $byDepartment = [];
        $byRole = [];

        foreach ($subordinates as $employee) {
            // Count by department
            $dept = $employee->department ?? 'Sem Departamento';
            $byDepartment[$dept] = ($byDepartment[$dept] ?? 0) + 1;

            // Count by role
            $byRole[$employee->role] = ($byRole[$employee->role] ?? 0) + 1;
        }

        return [
            'total_count' => count($subordinates),
            'by_department' => $byDepartment,
            'by_role' => $byRole,
            'active_count' => count($subordinates), // All are active due to filter
        ];
    }

    // ==================== Eager Loading Methods ====================

    /**
     * Get employees with their related data (avoid N+1 queries)
     *
     * Uses JOIN to load related data in a single query instead of
     * multiple queries per employee
     *
     * @param array|null $employeeIds Optional list of employee IDs to filter
     * @return array List of employees with relations
     */
    public function getWithRelations(?array $employeeIds = null): array
    {
        $builder = $this->db->table('employees e')
            ->select('
                e.*,
                m.name as manager_name,
                m.email as manager_email,
                COUNT(DISTINCT tp.id) as total_punches,
                COUNT(DISTINCT j.id) as total_justifications,
                COUNT(DISTINCT w.id) as total_warnings,
                COUNT(DISTINCT bt.id) as biometric_templates_count
            ')
            ->join('employees m', 'e.manager_id = m.id', 'left')
            ->join('time_punches tp', 'e.id = tp.employee_id', 'left')
            ->join('justifications j', 'e.id = j.employee_id', 'left')
            ->join('warnings w', 'e.id = w.employee_id', 'left')
            ->join('biometric_templates bt', 'e.id = bt.employee_id AND bt.active = 1', 'left')
            ->where('e.active', 1)
            ->groupBy('e.id');

        if ($employeeIds !== null && !empty($employeeIds)) {
            $builder->whereIn('e.id', $employeeIds);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get employees with punch statistics for a date range
     *
     * Eager loads punch data to avoid multiple queries
     *
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param array|null $employeeIds Optional filter
     * @return array Employees with punch statistics
     */
    public function getWithPunchStats(string $startDate, string $endDate, ?array $employeeIds = null): array
    {
        $builder = $this->db->table('employees e')
            ->select('
                e.id,
                e.name,
                e.department,
                e.position,
                e.expected_hours_daily,
                COUNT(DISTINCT DATE(tp.punch_time)) as days_worked,
                COUNT(tp.id) as total_punches,
                MIN(
                    CASE
                        WHEN tp.punch_type = "entrada"
                        THEN tp.punch_time
                        ELSE NULL
                    END
                ) as first_punch,
                MAX(
                    CASE
                        WHEN tp.punch_type = "saida"
                        THEN tp.punch_time
                        ELSE NULL
                    END
                ) as last_punch
            ')
            ->join('time_punches tp', 'e.id = tp.employee_id', 'left')
            ->where('e.active', 1)
            ->where('DATE(tp.punch_time) >=', $startDate)
            ->where('DATE(tp.punch_time) <=', $endDate)
            ->groupBy('e.id');

        if ($employeeIds !== null && !empty($employeeIds)) {
            $builder->whereIn('e.id', $employeeIds);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get active employees with department info (optimized)
     *
     * @param string|null $department Filter by department
     * @return array
     */
    public function getActiveWithDepartment(?string $department = null): array
    {
        $builder = $this->select('
                id,
                name,
                email,
                role,
                department,
                position,
                work_schedule_start,
                work_schedule_end,
                expected_hours_daily
            ')
            ->where('active', 1)
            ->orderBy('department', 'ASC')
            ->orderBy('name', 'ASC');

        if ($department !== null) {
            $builder->where('department', $department);
        }

        return $builder->findAll();
    }
}
