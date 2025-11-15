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
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadString, env('encryption.key'));

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
}
