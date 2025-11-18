<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EmployeeModel;
use App\Models\AuditLogModel;

/**
 * API Auth Controller
 *
 * Handles authentication for mobile/external applications
 */
class AuthController extends ResourceController
{
    protected $modelName = 'App\Models\EmployeeModel';
    protected $format = 'json';

    protected $employeeModel;
    protected $auditModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->auditModel = new AuditLogModel();
        helper(['security', 'format']);
    }

    /**
     * Login endpoint
     * POST /api/auth/login
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function login()
    {
        // Validate input
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // Find employee
        $employee = $this->employeeModel->findByEmail($email);

        if (!$employee) {
            // Log failed attempt
            $this->logFailedAttempt($email, 'Invalid credentials');

            return $this->fail('Credenciais inválidas.', 401);
        }

        // Check if active
        if (!$employee->active) {
            $this->logFailedAttempt($email, 'Inactive account');

            return $this->fail('Conta desativada. Entre em contato com o administrador.', 403);
        }

        // Verify password
        if (!password_verify($password, $employee->password)) {
            $this->logFailedAttempt($email, 'Invalid credentials');

            return $this->fail('Credenciais inválidas.', 401);
        }

        // Generate API token
        $token = $this->generateApiToken($employee->id);

        // Update last login
        $this->employeeModel->update($employee->id, [
            'last_login' => date('Y-m-d H:i:s'),
        ]);

        // Log successful login
        $this->auditModel->log(
            $employee->id,
            'API_LOGIN_SUCCESS',
            'employees',
            $employee->id,
            null,
            null,
            "Login via API: {$employee->name}",
            'info'
        );

        // Return response
        return $this->respond([
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 86400, // 24 hours
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'role' => $employee->role,
                    'department' => $employee->department,
                    'position' => $employee->position,
                    'unique_code' => $employee->unique_code,
                    'has_face_biometric' => $employee->has_face_biometric,
                    'has_fingerprint_biometric' => $employee->has_fingerprint_biometric,
                ],
            ],
        ], 200);
    }

    /**
     * Logout endpoint
     * POST /api/auth/logout
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function logout()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Invalidate token (in production, store tokens in database)
        // For now, just log the logout

        $this->auditModel->log(
            $employee->id,
            'API_LOGOUT',
            'employees',
            $employee->id,
            null,
            null,
            "Logout via API: {$employee->name}",
            'info'
        );

        return $this->respond([
            'success' => true,
            'message' => 'Logout realizado com sucesso.',
        ], 200);
    }

    /**
     * Refresh token endpoint
     * POST /api/auth/refresh
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function refresh()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Generate new token
        $token = $this->generateApiToken($employee->id);

        return $this->respond([
            'success' => true,
            'message' => 'Token renovado com sucesso.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 86400, // 24 hours
            ],
        ], 200);
    }

    /**
     * Get current user info
     * GET /api/auth/me
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function me()
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
                'phone' => $employee->phone,
                'admission_date' => $employee->admission_date,
                'daily_hours' => $employee->daily_hours,
                'weekly_hours' => $employee->weekly_hours,
                'work_start_time' => $employee->work_start_time,
                'work_end_time' => $employee->work_end_time,
                'hours_balance' => $employee->hours_balance,
                'has_face_biometric' => $employee->has_face_biometric,
                'has_fingerprint_biometric' => $employee->has_fingerprint_biometric,
                'active' => $employee->active,
            ],
        ], 200);
    }

    /**
     * Change password
     * POST /api/auth/change-password
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function changePassword()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Validate input
        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|strong_password',
            'new_password_confirm' => 'required|matches[new_password]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');

        // Verify current password
        if (!password_verify($currentPassword, $employee->password)) {
            return $this->fail('Senha atual incorreta.', 400);
        }

        // Update password
        $this->employeeModel->update($employee->id, [
            'password' => $newPassword, // Will be hashed by model
        ]);

        // Log password change
        $this->auditModel->log(
            $employee->id,
            'PASSWORD_CHANGED',
            'employees',
            $employee->id,
            null,
            null,
            "Senha alterada via API",
            'info'
        );

        return $this->respond([
            'success' => true,
            'message' => 'Senha alterada com sucesso.',
        ], 200);
    }

    /**
     * Generate API token
     *
     * @param int $employeeId
     * @return string
     */
    protected function generateApiToken(int $employeeId): string
    {
        // Generate secure token with HMAC signature
        $payload = [
            'employee_id' => $employeeId,
            'timestamp' => time(),
            'random' => bin2hex(random_bytes(16)),
        ];

        $payloadEncoded = base64_encode(json_encode($payload));

        // Get encryption key from env (validate it exists)
        $secret = env('encryption.key');
        if (empty($secret)) {
            throw new \RuntimeException('encryption.key not configured in environment');
        }

        // Generate HMAC signature to prevent tampering
        $signature = hash_hmac('sha256', $payloadEncoded, $secret);

        // Token format: payload.signature
        $token = $payloadEncoded . '.' . $signature;

        return $token;
    }

    /**
     * Get authenticated employee from token
     *
     * @return object|null
     */
    protected function getAuthenticatedEmployee(): ?object
    {
        // Get token from Authorization header
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return null;
        }

        // Extract token (Bearer <token>)
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];

        // Decode and validate token
        try {
            // Split token into payload and signature
            $parts = explode('.', $token);
            if (count($parts) !== 2) {
                log_message('warning', 'Invalid token format - missing signature');
                return null;
            }

            [$payloadEncoded, $providedSignature] = $parts;

            // Get encryption key and validate
            $secret = env('encryption.key');
            if (empty($secret)) {
                log_message('error', 'encryption.key not configured');
                return null;
            }

            // Verify HMAC signature to prevent tampering
            $expectedSignature = hash_hmac('sha256', $payloadEncoded, $secret);
            if (!hash_equals($expectedSignature, $providedSignature)) {
                log_message('warning', 'Invalid token signature - possible tampering attempt');
                return null;
            }

            // Decode payload
            $payload = json_decode(base64_decode($payloadEncoded), true);

            if (!$payload || !isset($payload['employee_id'])) {
                return null;
            }

            // Check expiration (24 hours)
            if (time() - $payload['timestamp'] > 86400) {
                return null;
            }

            // Get employee
            $employee = $this->employeeModel->find($payload['employee_id']);

            if (!$employee || !$employee->active) {
                return null;
            }

            return $employee;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Log failed login attempt
     *
     * @param string $email
     * @param string $reason
     * @return void
     */
    protected function logFailedAttempt(string $email, string $reason): void
    {
        $this->auditModel->log(
            null,
            'API_LOGIN_FAILED',
            'employees',
            null,
            null,
            ['email' => $email, 'reason' => $reason],
            "Tentativa de login via API falhou: {$email} - {$reason}",
            'warning'
        );
    }
}
